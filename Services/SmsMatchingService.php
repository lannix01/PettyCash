<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\PettyTokenPaymentRequest;
use App\Modules\PettyCash\Models\PettyTokenSmsLog;
use App\Modules\PettyCash\Support\PettyDatabase;
use App\Modules\PettyCash\Support\TokenPaymentStatus;
use Illuminate\Support\Carbon;

class SmsMatchingService
{
    public function processIncomingLog(PettyTokenSmsLog $smsLog): ?PettyTokenPaymentRequest
    {
        $request = $this->bestMatchForLog($smsLog);
        if (!$request) {
            return null;
        }

        $this->attachLogToRequest($request, $smsLog, null);

        return $request->fresh(['matchedMpesaSms', 'matchedKplcSms']);
    }

    public function bestMatchForLog(PettyTokenSmsLog $smsLog): ?PettyTokenPaymentRequest
    {
        $meter = trim((string) $smsLog->parsed_meter_number);
        if ($meter === '') {
            return null;
        }

        $query = PettyTokenPaymentRequest::query()
            ->where('meter_number', $meter)
            ->whereNotIn('status', [TokenPaymentStatus::TOKEN_SENT, TokenPaymentStatus::CANCELLED])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($smsLog->gateway_device_id) {
            $query->where(function ($w) use ($smsLog) {
                $w->whereNull('gateway_device_id')
                    ->orWhere('gateway_device_id', $smsLog->gateway_device_id);
            });
        }

        $candidates = $query->get();
        if ($candidates->isEmpty()) {
            return null;
        }

        $exact = $this->bestExactMatch($candidates, $smsLog);
        if ($exact) {
            $smsLog->forceFill([
                'matched_confidence' => 100,
            ])->save();

            return $exact;
        }

        $best = null;
        $bestScore = -1.0;
        foreach ($candidates as $candidate) {
            $score = $this->score($candidate, $smsLog);
            if ($score > $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        if (!$best || $bestScore < 55) {
            return null;
        }

        $smsLog->forceFill([
            'matched_confidence' => $bestScore,
        ])->save();

        return $best;
    }

    private function bestExactMatch($candidates, PettyTokenSmsLog $smsLog): ?PettyTokenPaymentRequest
    {
        if ($smsLog->parsed_amount === null) {
            return null;
        }

        $requestTime = $smsLog->sms_received_at ?: $smsLog->created_at;

        $exact = $candidates
            ->filter(function (PettyTokenPaymentRequest $candidate) use ($smsLog) {
                return (string) $candidate->meter_number === (string) $smsLog->parsed_meter_number
                    && abs((float) $candidate->amount - (float) $smsLog->parsed_amount) < 0.01;
            })
            ->sortBy(function (PettyTokenPaymentRequest $candidate) use ($requestTime) {
                if (!$requestTime) {
                    return PHP_INT_MAX;
                }

                $candidateTime = $candidate->payment_started_at ?: $candidate->sent_to_phone_at ?: $candidate->created_at;
                if (!$candidateTime) {
                    return PHP_INT_MAX;
                }

                return abs(Carbon::parse($candidateTime)->diffInMinutes(Carbon::parse($requestTime), false));
            })
            ->values();

        return $exact->first();
    }

    public function attachLogToRequest(PettyTokenPaymentRequest $request, PettyTokenSmsLog $smsLog, ?int $userId = null): void
    {
        PettyDatabase::transaction(function () use ($request, $smsLog, $userId) {
            $kind = (string) $smsLog->sms_kind;

            $smsLog->forceFill([
                'payment_request_id' => $request->id,
                'is_matched' => true,
                'matched_by_user_id' => $userId,
                'matched_at' => now(),
            ])->save();

            if ($kind === 'mpesa') {
                $request->matched_mpesa_sms_log_id = $smsLog->id;
                $request->mpesa_received_at = $smsLog->sms_received_at ?: now();
            } elseif ($kind === 'kplc') {
                $request->matched_kplc_sms_log_id = $smsLog->id;
                $request->token_received_at = $smsLog->sms_received_at ?: now();
            }

            $request->status = $this->deriveStatus($request);
            if (in_array($request->status, [TokenPaymentStatus::MATCHED, TokenPaymentStatus::READY_FOR_CONFIRMATION], true)) {
                $request->matched_at = now();
            }

            $request->save();
        });
    }

    public function deriveStatus(PettyTokenPaymentRequest $request): string
    {
        $hasMpesa = !empty($request->matched_mpesa_sms_log_id);
        $hasKplc = !empty($request->matched_kplc_sms_log_id);

        if ($hasMpesa && $hasKplc) {
            return TokenPaymentStatus::READY_FOR_CONFIRMATION;
        }

        if ($hasMpesa) {
            return TokenPaymentStatus::MPESA_SMS_RECEIVED;
        }

        if ($hasKplc) {
            return TokenPaymentStatus::TOKEN_SMS_RECEIVED;
        }

        return $request->status ?: TokenPaymentStatus::AWAITING_PAYMENT;
    }

    private function score(PettyTokenPaymentRequest $request, PettyTokenSmsLog $smsLog): float
    {
        $score = 0.0;

        if ((string) $request->meter_number === (string) $smsLog->parsed_meter_number) {
            $score += 50;
        }

        if ($smsLog->parsed_amount !== null && abs((float) $request->amount - (float) $smsLog->parsed_amount) < 0.01) {
            $score += 25;
        }

        if ($smsLog->parsed_payment_type && $smsLog->parsed_payment_type === $request->payment_type) {
            $score += 10;
        }

        if ($smsLog->gateway_device_id && $request->gateway_device_id && (int) $smsLog->gateway_device_id === (int) $request->gateway_device_id) {
            $score += 10;
        }

        $requestTime = $request->sent_to_phone_at ?: $request->created_at;
        $smsTime = $smsLog->sms_received_at ?: $smsLog->created_at;
        if ($requestTime && $smsTime) {
            $minutes = abs(Carbon::parse($requestTime)->diffInMinutes(Carbon::parse($smsTime), false));
            if ($minutes <= 15) {
                $score += 10;
            } elseif ($minutes <= 60) {
                $score += 5;
            }
        }

        return min($score, 100);
    }
}
