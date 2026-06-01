<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\PettyNotificationSetting;
use App\Services\Sms\AdvantaSmsService;
use App\Services\Sms\AmazonsSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Support\PettyAccess;

class RespondentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = strtolower(trim((string) $request->query('status', '')));
        $category = trim((string) $request->query('category', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'latest')));
        $allowedSorts = ['latest', 'oldest', 'name_asc', 'name_desc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $respondentsQuery = Respondent::query()
            ->when($status !== '', fn ($query) => $query->where('status', Respondent::normalizeStatus($status)))
            ->when($category !== '', fn ($query) => $query->where('category', Respondent::normalizeCategory($category)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%' . $q . '%')
                        ->orWhere('phone', 'like', '%' . $q . '%')
                        ->orWhere('staff_id', 'like', '%' . $q . '%')
                        ->orWhere('category', 'like', '%' . $q . '%')
                        ->orWhere('profile_email', 'like', '%' . $q . '%')
                        ->orWhere('profile_title', 'like', '%' . $q . '%');
                });
            });

        match ($sort) {
            'oldest' => $respondentsQuery->orderBy('id'),
            'name_asc' => $respondentsQuery->orderBy('name')->orderByDesc('id'),
            'name_desc' => $respondentsQuery->orderByDesc('name')->orderByDesc('id'),
            default => $respondentsQuery->orderByDesc('id'),
        };

        $respondents = $respondentsQuery->paginate(20)->withQueryString();

        return view('pettycash::respondents.index', [
            'respondents' => $respondents,
            'q' => $q,
            'status' => $status,
            'category' => $category,
            'sort' => $sort,
            'statusOptions' => Respondent::statusOptions(),
            'categoryOptions' => Respondent::categoryOptions(),
        ]);
    }

    public function create()
    {
        return view('pettycash::respondents.create', [
            'statusOptions' => Respondent::statusOptions(),
            'categoryOptions' => Respondent::categoryOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProfilePayload($request, false);

        $payload = collect($data)->except(['profile_photo', 'remove_photo'])->toArray();
        $payload['profile_photo_path'] = $this->storeProfilePhotoIfPresent($request, null);
        $payload['status'] = Respondent::normalizeStatus($data['status'] ?? null);
        $payload['category'] = Respondent::normalizeCategory($data['category'] ?? null);

        $respondent = Respondent::create($payload);
        $this->ensureStaffId($respondent, $data['staff_id'] ?? null);

        return redirect()->route('petty.respondents.index')->with('success', 'Respondent added.');
    }

    public function show(Respondent $respondent)
    {
        $photoPreviewDataUri = $this->photoDataUri($respondent->profile_photo_path);
        $publicCardUrl = $respondent->card_public_token
            ? route('petty.respondents.card.public.show', ['token' => $respondent->card_public_token])
            : null;

        return view('pettycash::respondents.show', compact(
            'respondent',
            'photoPreviewDataUri',
            'publicCardUrl'
        ) + [
            'statusOptions' => Respondent::statusOptions(),
            'categoryOptions' => Respondent::categoryOptions(),
        ]);
    }

    public function edit(Respondent $respondent)
    {
        return redirect()->route('petty.respondents.show', $respondent->id);
    }

    public function update(Request $request, Respondent $respondent)
    {
        $data = $this->validateProfilePayload($request, true);

        $payload = collect($data)->except(['profile_photo', 'remove_photo'])->toArray();
        $payload['profile_photo_path'] = $respondent->profile_photo_path;

        if ((bool)($data['remove_photo'] ?? false) && !empty($respondent->profile_photo_path)) {
            Storage::disk('public')->delete($respondent->profile_photo_path);
            $payload['profile_photo_path'] = null;
        }

        $newPhotoPath = $this->storeProfilePhotoIfPresent($request, $respondent->profile_photo_path);
        if ($newPhotoPath !== null) {
            $payload['profile_photo_path'] = $newPhotoPath;
        }
        $payload['status'] = Respondent::normalizeStatus($data['status'] ?? null);
        $payload['category'] = Respondent::normalizeCategory($data['category'] ?? null);

        $respondent->update($payload);
        $this->ensureStaffId($respondent, $data['staff_id'] ?? null);

        return redirect()->route('petty.respondents.show', $respondent->id)->with('success', 'Respondent profile updated.');
    }

    public function destroy(Respondent $respondent)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);

        $hasSpendings = Spending::query()->where('respondent_id', $respondent->id)->exists();
        if ($hasSpendings) {
            $respondent->update([
                'status' => Respondent::STATUS_DECOMMISSIONED,
            ]);

            return redirect()
                ->route('petty.respondents.index')
                ->with('success', 'Respondent decommissioned. Historical spendings remain visible, but this person is hidden from new selections.');
        }

        if (!empty($respondent->profile_photo_path)) {
            Storage::disk('public')->delete((string) $respondent->profile_photo_path);
        }
        if (!empty($respondent->card_file_path)) {
            Storage::disk('public')->delete((string) $respondent->card_file_path);
        }

        $respondent->delete();

        return redirect()->route('petty.respondents.index')->with('success', 'Respondent deleted.');
    }

    public function generateCard(Respondent $respondent)
    {
        $normalizedPhone = $this->normalizePhone((string) $respondent->phone);
        if ($normalizedPhone === '') {
            return back()->with('error', 'Phone number is required before generating a downloadable verification card.');
        }

        $this->ensureStaffId($respondent, $respondent->staff_id);

        $photoDataUri = $this->photoDataUri($respondent->profile_photo_path);
        $token = (string) ($respondent->card_public_token ?: $this->generatePublicToken());
        $publicCardUrl = route('petty.respondents.card.public.short', ['token' => $token]);
        $logoDataUri = $this->logoDataUri();
        $qrBinary = $this->qrBinary($publicCardUrl);
        $qrDataUri = $qrBinary ? 'data:image/png;base64,' . base64_encode($qrBinary) : null;
        $stamp = now()->format('Ymd-His');
        $relativePdfPath = 'apps/respondents/cards/respondent-card-' . $respondent->id . '-' . $stamp . '.pdf';
        $relativePngPath = 'apps/respondents/cards/respondent-card-' . $respondent->id . '-' . $stamp . '.png';
        $expiresAt = now()->addYear();
        $theme = $respondent->cardTheme();

        $pdf = Pdf::loadView('pettycash::respondents.card_pdf', [
            'respondent' => $respondent,
            'photoDataUri' => $photoDataUri,
            'logoDataUri' => $logoDataUri,
            'qrDataUri' => $qrDataUri,
            'theme' => $theme,
            'publicCardUrl' => $publicCardUrl,
            'generatedAt' => now(),
            'expiresAt' => $expiresAt,
        ])->setPaper('a4', 'portrait');

        if (!empty($respondent->card_file_path) && Storage::disk('public')->exists((string) $respondent->card_file_path)) {
            Storage::disk('public')->delete((string) $respondent->card_file_path);
        }
        if (!empty($respondent->card_png_path) && Storage::disk('public')->exists((string) $respondent->card_png_path)) {
            Storage::disk('public')->delete((string) $respondent->card_png_path);
        }

        Storage::disk('public')->put($relativePdfPath, $pdf->output());
        Storage::disk('public')->put($relativePngPath, $this->renderCardPng($respondent, $publicCardUrl, $qrBinary, $theme, $expiresAt));

        $respondent->update([
            'card_public_token' => $token,
            'card_file_path' => $relativePdfPath,
            'card_png_path' => $relativePngPath,
            'card_generated_at' => now(),
            'card_expires_at' => $expiresAt,
        ]);

        return back()->with('success', 'Verification card generated in PDF and PNG formats.');
    }

    public function sendCardLinkSms(Respondent $respondent)
    {
        if (empty($respondent->card_public_token) || empty($respondent->card_file_path)) {
            return back()->with('error', 'Generate the verification card first.');
        }

        if (!Storage::disk('public')->exists((string) $respondent->card_file_path)) {
            return back()->with('error', 'Card file not found. Generate the card again.');
        }

        $phone = $this->normalizePhone((string) $respondent->phone);
        if ($phone === '') {
            return back()->with('error', 'Phone number is required to send SMS.');
        }

        $settings = PettyNotificationSetting::current();
        if (!$settings->sms_enabled) {
            return back()->with('error', 'SMS is currently disabled in PettyCash notification settings.');
        }

        $gateway = strtolower((string) ($settings->sms_gateway ?: 'advanta'));
        $service = $gateway === 'amazons'
            ? app(AmazonsSmsService::class)
            : app(AdvantaSmsService::class);

        $publicLink = route('petty.respondents.card.public.short', ['token' => $respondent->card_public_token]);
        $message = sprintf(
            'Hello %s, your SKYBRIX staff card is ready. Verify/download: %s. Password is your phone number.',
            trim((string) $respondent->name) !== '' ? trim((string) $respondent->name) : 'Respondent',
            $publicLink
        );

        try {
            $response = $service->send($phone, $message);
            if (!$this->isSmsSuccess((array) $response)) {
                return back()->with('error', 'SMS gateway rejected the message. Please try again.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send SMS: ' . $e->getMessage());
        }

        $respondent->update(['card_sms_sent_at' => now()]);

        return back()->with('success', 'Card link SMS sent successfully.');
    }

    public function publicCard(string $token)
    {
        $respondent = $this->findRespondentByCardToken($token);

        return view('pettycash::respondents.public_card', [
            'respondent' => $respondent,
            'token' => $token,
            'photoPreviewDataUri' => $this->photoDataUri($respondent->profile_photo_path),
        ]);
    }

    public function publicCardDownload(Request $request, string $token)
    {
        $respondent = $this->findRespondentByCardToken($token);

        $data = $request->validate([
            'password' => ['required', 'string', 'max:40'],
            'format' => ['required', 'in:pdf,png'],
        ]);

        $provided = $this->normalizePhone((string) $data['password']);
        $expected = $this->normalizePhone((string) $respondent->phone);

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return back()->withInput()->with('error', 'Invalid password. Use the registered phone number.');
        }

        $format = (string) $data['format'];
        $relativePath = $format === 'png'
            ? (string) $respondent->card_png_path
            : (string) $respondent->card_file_path;
        if ($relativePath === '' || !Storage::disk('public')->exists($relativePath)) {
            throw new NotFoundHttpException('Card file not found.');
        }

        $nameForFile = trim((string) $respondent->name);
        if ($nameForFile === '') {
            $nameForFile = 'respondent-' . $respondent->id;
        }
        $downloadName = 'skybrix-verified-card-' . Str::slug($nameForFile) . '.' . $format;

        return response()->download(
            Storage::disk('public')->path($relativePath),
            $downloadName,
            ['Content-Type' => $format === 'png' ? 'image/png' : 'application/pdf']
        );
    }

    private function validateProfilePayload(Request $request, bool $isUpdate): array
    {
        $rules = [
            'name' => ['required','string','max:150'],
            'phone' => ['nullable','string','max:50'],
            'category' => ['required', 'string', 'in:' . implode(',', Respondent::categoryOptions())],
            'staff_id' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(Respondent::statusOptions()))],
            'profile_title' => ['nullable', 'string', 'max:120'],
            'profile_email' => ['nullable', 'email', 'max:160'],
            'profile_location' => ['nullable', 'string', 'max:180'],
            'profile_notes' => ['nullable', 'string', 'max:1500'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        if ($isUpdate) {
            $rules['remove_photo'] = ['nullable', 'boolean'];
        }

        return $request->validate($rules);
    }

    private function storeProfilePhotoIfPresent(Request $request, ?string $oldPath): ?string
    {
        if (!$request->hasFile('profile_photo')) {
            return null;
        }

        $file = $request->file('profile_photo');
        if (!$file) {
            return null;
        }

        $base = Str::slug(pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME));
        if ($base === '') {
            $base = 'respondent-photo';
        }

        $filename = $base . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . strtolower((string) $file->getClientOriginalExtension());
        $path = $file->storeAs('apps/respondents/photos', $filename, 'public');

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }

    private function findRespondentByCardToken(string $token): Respondent
    {
        $respondent = Respondent::query()
            ->where('card_public_token', $token)
            ->first();

        if (!$respondent) {
            throw new NotFoundHttpException('Card link is invalid or expired.');
        }

        return $respondent;
    }

    private function generatePublicToken(): string
    {
        do {
            $token = Str::upper(Str::random(12));
        } while (Respondent::query()->where('card_public_token', $token)->exists());

        return $token;
    }

    private function ensureStaffId(Respondent $respondent, ?string $requestedStaffId): void
    {
        $requested = strtoupper(trim((string) $requestedStaffId));
        $candidate = $requested !== '' ? $requested : $this->buildStaffId($respondent);

        if ($candidate === $respondent->staff_id) {
            return;
        }

        $baseCandidate = $candidate;
        $suffix = 1;
        while (Respondent::query()
            ->where('id', '!=', $respondent->id)
            ->where('staff_id', $candidate)
            ->exists()) {
            $candidate = $baseCandidate . '-' . $suffix;
            $suffix++;
        }

        $respondent->forceFill(['staff_id' => $candidate])->save();
    }

    private function buildStaffId(Respondent $respondent): string
    {
        $prefix = match (Respondent::normalizeCategory($respondent->category)) {
            Respondent::CATEGORY_EXECUTIVE => 'EXE',
            Respondent::CATEGORY_CONSULTANT => 'CON',
            Respondent::CATEGORY_TECHNICIAN => 'TEC',
            Respondent::CATEGORY_CUSTOMER_SERVICE => 'CSR',
            Respondent::CATEGORY_FIBER_TECHNICIAN => 'FBR',
            Respondent::CATEGORY_VISITOR => 'VST',
            Respondent::CATEGORY_TRAINEE => 'TRN',
            Respondent::CATEGORY_ATTACHEE => 'ATT',
            Respondent::CATEGORY_INTERN => 'INT',
            default => 'STF',
        };

        return $prefix . '-' . str_pad((string) $respondent->id, 4, '0', STR_PAD_LEFT);
    }

    private function photoDataUri(?string $relativePath): ?string
    {
        $path = trim((string) $relativePath);
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $binary = Storage::disk('public')->get($path);
        if ($binary === '') {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function logoDataUri(): ?string
    {
        $candidates = [
            base_path('skybrix-logo.png'),
            public_path('logo.png'),
            public_path('assets/images/logo.png'),
            public_path('assets/logo.png'),
            public_path('assets/images/avatar.png'),
        ];

        foreach ($candidates as $absolutePath) {
            if (!is_file($absolutePath)) {
                continue;
            }

            $binary = @file_get_contents($absolutePath);
            if ($binary === false || $binary === '') {
                continue;
            }

            $mime = mime_content_type($absolutePath) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        return null;
    }

    private function qrDataUri(string $value): ?string
    {
        $binary = $this->qrBinary($value);

        return $binary ? 'data:image/png;base64,' . base64_encode($binary) : null;
    }

    private function qrBinary(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $urls = [
            'https://quickchart.io/qr?size=220&margin=1&text=' . rawurlencode($value),
            'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($value),
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(8)->accept('image/png')->get($url);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$response->successful()) {
                continue;
            }

            $binary = $response->body();
            if ($binary === '') {
                continue;
            }

            return $binary;
        }

        return null;
    }

    private function renderCardPng(Respondent $respondent, string $publicCardUrl, ?string $qrBinary, array $theme, Carbon $expiresAt): string
    {
        $width = 1200;
        $height = 690;
        $image = imagecreatetruecolor($width, $height);
        imageantialias($image, true);

        [$bgR, $bgG, $bgB] = $this->hexToRgb('#edf2f8');
        $background = imagecolorallocate($image, $bgR, $bgG, $bgB);
        imagefill($image, 0, 0, $background);

        [$accentDarkR, $accentDarkG, $accentDarkB] = $this->hexToRgb($theme['accent_dark']);
        [$badgeR, $badgeG, $badgeB] = $this->hexToRgb($theme['badge_bg']);

        $white = imagecolorallocate($image, 255, 255, 255);
        [$accentR, $accentG, $accentB] = $this->hexToRgb($theme['accent']);
        $accent = imagecolorallocate($image, $accentR, $accentG, $accentB);
        $accentDark = imagecolorallocate($image, $accentDarkR, $accentDarkG, $accentDarkB);
        $ink = imagecolorallocate($image, 11, 18, 32);
        $muted = imagecolorallocate($image, 90, 102, 122);
        $badge = imagecolorallocate($image, $badgeR, $badgeG, $badgeB);
        $line = imagecolorallocate($image, 213, 220, 230);
        $photoBg = imagecolorallocate($image, 238, 243, 251);
        $secondaryInk = imagecolorallocate($image, 51, 65, 85);

        imagefilledrectangle($image, 70, 44, 1130, 646, $white);
        imagerectangle($image, 70, 44, 1130, 646, $line);
        imagefilledrectangle($image, 70, 44, 1130, 148, $white);
        imageline($image, 70, 148, 1130, 148, $line);
        imagefilledrectangle($image, 70, 148, 1130, 202, $accentDark);

        $fontRegular = $this->resolveFontPath(false);
        $fontBold = $this->resolveFontPath(true);

        $this->placeLogo($image, $fontBold, $secondaryInk);

        $stripText = 'STAFF IDENTIFICATION CARD';
        $stripSize = 22;
        $stripWidth = $this->measureTextWidth($stripSize, $fontBold, $stripText);
        $stripX = 70 + (int) floor((1060 - $stripWidth) / 2);
        $this->drawText($image, $stripSize, $stripX, 186, $white, $fontBold, $stripText);
        imagefilledrectangle($image, 920, 158, 1090, 192, $badge);
        $this->drawText($image, 12, 945, 180, $accentDark, $fontBold, strtoupper($respondent->statusLabel()));

        imagefilledrectangle($image, 100, 244, 320, 504, $photoBg);
        imagerectangle($image, 100, 244, 320, 504, $line);
        $this->placePhotoBox($image, $respondent, 112, 256, 196, 228);
        $this->drawFittedText($image, 20, 110, 538, 200, $secondaryInk, $fontBold, $this->formatPhoneForCard((string) ($respondent->phone ?: '-')), 12);

        $this->drawText($image, 34, 372, 286, $ink, $fontBold, strtoupper((string) $respondent->name));
        $this->drawText($image, 20, 372, 324, $accentDark, $fontBold, strtoupper((string) ($respondent->category ?: Respondent::CATEGORY_OTHER_STAFF)));

        $meta = [
            ['Staff ID', (string) ($respondent->staff_id ?: '-')],
            ['Email', (string) ($respondent->profile_email ?: '-')],
        ];

        $y = 382;
        foreach ($meta as [$label, $value]) {
            $this->drawText($image, 14, 372, $y, $muted, $fontBold, strtoupper($label));
            $valueFont = $label === 'Staff ID' ? $fontBold : $fontRegular;
            $valueSize = $label === 'Staff ID' ? 23 : 21;
            $this->drawText($image, $valueSize, 372, $y + 30, $secondaryInk, $valueFont, $value);
            $y += 76;
        }

        imagefilledrectangle($image, 916, 270, 1040, 394, $white);
        imagerectangle($image, 916, 270, 1040, 394, $line);
        $this->placeQr($image, $qrBinary, 926, 280, 104);
        $this->drawFittedText($image, 12, 910, 420, 146, $ink, $fontBold, 'SCAN TO VERIFY', 10);
        $issuedY = 534;
        $this->drawText($image, 14, 372, $issuedY, $muted, $fontBold, 'ISSUED');
        $this->drawText($image, 21, 372, $issuedY + 30, $secondaryInk, $fontRegular, optional($generatedAt ?: now())->format('Y-m-d'));
        $this->drawText($image, 14, 590, $issuedY, $muted, $fontBold, 'EXPIRY');
        $this->drawText($image, 21, 590, $issuedY + 30, $secondaryInk, $fontRegular, $expiresAt->format('Y-m-d'));
        imagefilledrectangle($image, 70, 640, 1130, 646, $accent);

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function resolveFontPath(bool $bold): ?string
    {
        $candidates = $bold
            ? [
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            ]
            : [
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/dejavu/DejaVuSans.ttf',
            ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function drawText($image, int $size, int $x, int $y, int $color, ?string $font, string $text): void
    {
        if ($text === '') {
            return;
        }

        if ($font && function_exists('imagettftext')) {
            imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
            return;
        }

        imagestring($image, 5, $x, max(0, $y - 18), $text, $color);
    }

    private function drawWrappedText($image, int $size, int $x, int $y, int $maxWidth, int $color, ?string $font, string $text): void
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $line = '';
        $currentY = $y;

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            if ($this->measureTextWidth($size, $font, $candidate) > $maxWidth && $line !== '') {
                $this->drawText($image, $size, $x, $currentY, $color, $font, $line);
                $line = $word;
                $currentY += $size + 10;
                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $this->drawText($image, $size, $x, $currentY, $color, $font, $line);
        }
    }

    private function measureTextWidth(int $size, ?string $font, string $text): int
    {
        if ($text === '') {
            return 0;
        }

        if ($font && function_exists('imagettfbbox')) {
            $box = imagettfbbox($size, 0, $font, $text);
            return (int) abs(($box[2] ?? 0) - ($box[0] ?? 0));
        }

        return imagefontwidth(5) * strlen($text);
    }

    private function drawFittedText($image, int $size, int $x, int $y, int $maxWidth, int $color, ?string $font, string $text, int $minSize = 10): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $currentSize = $size;
        while ($currentSize > $minSize && $this->measureTextWidth($currentSize, $font, $text) > $maxWidth) {
            $currentSize--;
        }

        $this->drawText($image, $currentSize, $x, $y, $color, $font, $text);
    }

    private function placePhoto($image, Respondent $respondent): void
    {
        $this->placePhotoBox($image, $respondent, 95, 225, 230, 300);
    }

    private function placePhotoBox($image, Respondent $respondent, int $x, int $y, int $targetWidth, int $targetHeight): void
    {
        $photoPath = trim((string) $respondent->profile_photo_path);
        if ($photoPath !== '' && Storage::disk('public')->exists($photoPath)) {
            $binary = Storage::disk('public')->get($photoPath);
            $source = @imagecreatefromstring($binary);
            if ($source !== false) {
                imagecopyresampled($image, $source, $x, $y, 0, 0, $targetWidth, $targetHeight, imagesx($source), imagesy($source));
                imagedestroy($source);
                return;
            }
        }

        $fallback = imagecolorallocate($image, 232, 238, 247);
        $ink = imagecolorallocate($image, 24, 73, 169);
        imagefilledrectangle($image, $x, $y, $x + $targetWidth, $y + $targetHeight, $fallback);
        $this->drawText($image, 92, $x + 48, $y + 148, $ink, $this->resolveFontPath(true), strtoupper(substr((string) $respondent->name, 0, 1)));
    }

    private function placeQr($image, ?string $qrBinary, int $x = 885, int $y = 122, int $size = 200): void
    {
        if ($qrBinary) {
            $source = @imagecreatefromstring($qrBinary);
            if ($source !== false) {
                imagecopyresampled($image, $source, $x, $y, 0, 0, $size, $size, imagesx($source), imagesy($source));
                imagedestroy($source);
                return;
            }
        }

        $fallback = imagecolorallocate($image, 248, 250, 252);
        $ink = imagecolorallocate($image, 71, 84, 103);
        imagefilledrectangle($image, $x, $y, $x + $size, $y + $size, $fallback);
        $this->drawText($image, 18, $x + 28, $y + 54, $ink, $this->resolveFontPath(true), 'QR');
    }

    private function placeLogo($image, ?string $fontBold, int $ink): void
    {
        $logoPath = $this->logoAbsolutePath();
        if ($logoPath && is_file($logoPath)) {
            $binary = @file_get_contents($logoPath);
            if ($binary !== false && $binary !== '') {
                $source = @imagecreatefromstring($binary);
                if ($source !== false) {
                    $sourceWidth = imagesx($source);
                    $sourceHeight = imagesy($source);
                    $maxWidth = 760;
                    $maxHeight = 82;
                    $scale = min($maxWidth / max($sourceWidth, 1), $maxHeight / max($sourceHeight, 1));
                    $targetWidth = max(1, (int) round($sourceWidth * $scale));
                    $targetHeight = max(1, (int) round($sourceHeight * $scale));
                    $targetX = 70 + (int) floor((1060 - $targetWidth) / 2);
                    $targetY = 54 + (int) floor((94 - $targetHeight) / 2);
                    imagecopyresampled($image, $source, $targetX, $targetY, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
                    imagedestroy($source);
                    return;
                }
            }
        }

        $this->drawText($image, 40, 400, 112, $ink, $fontBold, 'SKYBRIX');
    }

    private function logoAbsolutePath(): ?string
    {
        $candidates = [
            base_path('skybrix-logo.png'),
            public_path('logo.png'),
            public_path('assets/images/logo.png'),
            public_path('assets/logo.png'),
            public_path('assets/images/avatar.png'),
        ];

        foreach ($candidates as $absolutePath) {
            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    }

    private function formatPhoneForCard(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '-';
        }

        return preg_replace('/\s+/', ' ', $phone) ?: $phone;
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function hexToRgb(string $hex): array
    {
        $value = ltrim($hex, '#');
        if (strlen($value) === 3) {
            $value = preg_replace('/(.)/', '$1$1', $value) ?: '000000';
        }

        return [
            hexdec(substr($value, 0, 2)),
            hexdec(substr($value, 2, 2)),
            hexdec(substr($value, 4, 2)),
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (preg_match('/^07\d{8}$/', $phone)) {
            return '254' . substr($phone, 1);
        }
        if (preg_match('/^01\d{8}$/', $phone)) {
            return '254' . substr($phone, 1);
        }

        return $phone;
    }

    private function isSmsSuccess(array $response): bool
    {
        if (array_key_exists('success', $response)) {
            return (bool) $response['success'];
        }

        if (isset($response['responses'][0]['response-code'])) {
            return (string) $response['responses'][0]['response-code'] === '200';
        }

        if (isset($response['response-code'])) {
            return (string) $response['response-code'] === '200';
        }

        if (isset($response['status'])) {
            return in_array(strtolower((string) $response['status']), ['ok', 'success', 'sent'], true);
        }

        return false;
    }
}
