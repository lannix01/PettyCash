<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\HostelPendingCredit;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\PettyGatewayDevice;
use App\Modules\PettyCash\Models\PettyTokenSmsLog;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Services\GatewayOutboxService;
use App\Modules\PettyCash\Services\MpesaQrService;
use App\Modules\PettyCash\Services\OntDirectoryService;
use App\Modules\PettyCash\Services\SmsParsingService;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Modules\PettyCash\Support\PettyDatabase;
use App\Modules\PettyCash\Support\TabularExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $sortDue = strtolower(trim((string) $request->get('sort_due', 'asc')));
        $dueFilter = strtolower(trim((string) $request->get('due_filter', 'all')));
        $dueInDays = 0;
        $supportsOntSiteColumns = $this->hostelOntColumnsAvailable();
        $supportsAgreementFamilyColumns = $this->hostelAgreementFamilyColumnsAvailable();
        $supportsChainedColumns = $this->hostelChainedColumnsAvailable();
        $terminationSupported = $this->hostelAgreementTerminationColumnsAvailable();
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }
        if (!in_array($sortDue, ['asc', 'desc'], true)) {
            $sortDue = 'asc';
        }
        if (!in_array($dueFilter, ['all', 'overdue', 'due_today', 'due_tomorrow', 'due_in_2_days', 'due_in_3_days', 'no_payments', 'terminated'], true)) {
            $dueFilter = 'all';
        }

        $today = Carbon::today();
        $todayDate = $today->toDateString();

        $totalHostels = $supportsAgreementFamilyColumns
            ? Hostel::query()->whereNull('agreement_parent_hostel_id')->count()
            : Hostel::count();

        $lastPaySub = Payment::query();
        if ($supportsAgreementFamilyColumns) {
            $lastPaySub
                ->join('petty_hostels as payment_hostels', 'payment_hostels.id', '=', 'petty_payments.hostel_id')
                ->selectRaw('COALESCE(payment_hostels.agreement_parent_hostel_id, payment_hostels.id) as root_hostel_id')
                ->selectRaw('MAX(petty_payments.date) as last_payment_date')
                ->groupBy('root_hostel_id');
        } else {
            $lastPaySub
                ->selectRaw('hostel_id as root_hostel_id')
                ->selectRaw('MAX(date) as last_payment_date')
                ->groupBy('hostel_id');
        }

        $dueDateExpr = "CASE
            WHEN petty_hostels.is_due_immediately AND lp.last_payment_date IS NULL THEN CURDATE()
            WHEN lp.last_payment_date IS NULL THEN NULL
            WHEN petty_hostels.stake = 'semester' THEN DATE_ADD(lp.last_payment_date, INTERVAL 4 MONTH)
            ELSE DATE_ADD(lp.last_payment_date, INTERVAL 1 MONTH)
        END";

        $hostels = Hostel::query()
            ->when($supportsAgreementFamilyColumns, function ($qq) {
                $qq->whereNull('petty_hostels.agreement_parent_hostel_id');
            })
            ->leftJoinSub($lastPaySub, 'lp', function ($join) {
                $join->on('lp.root_hostel_id', '=', 'petty_hostels.id');
            })
            ->select('petty_hostels.*', 'lp.last_payment_date')
            ->selectRaw("$dueDateExpr as due_date_sort")
            ->when($q !== '', function ($qq) use ($q, $supportsOntSiteColumns, $supportsAgreementFamilyColumns, $supportsChainedColumns) {
                $like = '%' . str_replace('%', '\\%', $q) . '%';

                $qq->where(function ($w) use ($like, $supportsOntSiteColumns, $supportsAgreementFamilyColumns, $supportsChainedColumns) {
                    $w->where('hostel_name', 'like', $like)
                        ->orWhere('contact_person', 'like', $like)
                        ->orWhere('meter_no', 'like', $like)
                        ->orWhere('phone_no', 'like', $like);

                    if ($supportsOntSiteColumns) {
                        $w->orWhere('ont_site_sn', 'like', $like)
                            ->orWhere('ont_site_id', 'like', $like);
                    }

                    if ($supportsAgreementFamilyColumns) {
                        $w->orWhereExists(function ($childQuery) use ($like, $supportsOntSiteColumns) {
                            $childQuery
                                ->selectRaw('1')
                                ->from('petty_hostels as child_hostels')
                                ->whereColumn('child_hostels.agreement_parent_hostel_id', 'petty_hostels.id')
                                ->where(function ($childWhere) use ($like, $supportsOntSiteColumns) {
                                    $childWhere
                                        ->where('child_hostels.hostel_name', 'like', $like)
                                        ->orWhere('child_hostels.contact_person', 'like', $like)
                                        ->orWhere('child_hostels.meter_no', 'like', $like)
                                        ->orWhere('child_hostels.phone_no', 'like', $like);

                                    if ($supportsOntSiteColumns) {
                                        $childWhere
                                            ->orWhere('child_hostels.ont_site_sn', 'like', $like)
                                            ->orWhere('child_hostels.ont_site_id', 'like', $like);
                                    }
                                });
                        });
                    }

                    if ($supportsChainedColumns) {
                        $w->orWhereExists(function ($chainQuery) use ($like, $supportsOntSiteColumns) {
                            $chainQuery
                                ->selectRaw('1')
                                ->from('petty_hostels as chain_hostels')
                                ->whereColumn('chain_hostels.id', 'petty_hostels.chained_from_hostel_id')
                                ->where(function ($chainWhere) use ($like, $supportsOntSiteColumns) {
                                    $chainWhere
                                        ->where('chain_hostels.hostel_name', 'like', $like)
                                        ->orWhere('chain_hostels.contact_person', 'like', $like);

                                    if ($supportsOntSiteColumns) {
                                        $chainWhere
                                            ->orWhere('chain_hostels.ont_site_sn', 'like', $like)
                                            ->orWhere('chain_hostels.ont_site_id', 'like', $like);
                                    }
                                });
                        });
                    }
                });
            })
            ->when($dueFilter !== 'all', function ($qq) use ($dueFilter, $dueInDays, $terminationSupported, $todayDate, $dueDateExpr, $today) {
                if ($dueFilter === 'terminated') {
                    if ($terminationSupported) {
                        $qq->whereNotNull('petty_hostels.agreement_terminated_at');
                    } else {
                        $qq->whereRaw('1 = 0');
                    }

                    return;
                }

                if ($terminationSupported) {
                    $qq->whereNull('petty_hostels.agreement_terminated_at');
                }

                if ($dueFilter === 'no_payments') {
                    $qq->whereNull('lp.last_payment_date');
                    return;
                }

                $targetDate = match ($dueFilter) {
                    'due_today' => $todayDate,
                    'due_tomorrow' => $today->copy()->addDay()->toDateString(),
                    'due_in_2_days' => $today->copy()->addDays(2)->toDateString(),
                    'due_in_3_days' => $today->copy()->addDays(3)->toDateString(),
                    default => null,
                };

                $qq->whereNotNull('lp.last_payment_date');

                if ($dueFilter === 'overdue') {
                    $qq->whereRaw("DATE($dueDateExpr) < ?", [$todayDate]);
                    return;
                }

                if ($targetDate !== null) {
                    $qq->whereRaw("DATE($dueDateExpr) = ?", [$targetDate]);
                }
            })
            ->when($terminationSupported, function ($qq) {
                $qq->orderByRaw('CASE WHEN petty_hostels.agreement_terminated_at IS NULL THEN 0 ELSE 1 END ASC');
            })
            ->when($sortDue === 'desc', function ($qq) {
                $qq->orderByRaw('due_date_sort IS NULL ASC')
                    ->orderBy('due_date_sort', 'desc')
                    ->orderBy('hostel_name');
            }, function ($qq) {
                $qq->orderByRaw('due_date_sort IS NULL ASC')
                    ->orderBy('due_date_sort', 'asc')
                    ->orderBy('hostel_name');
            });

        // Build reminders from full unfiltered dataset (not affected by due_filter)
        // This gives a complete picture regardless of what due_filter is currently applied
        $remindersBase = Hostel::query()
            ->when($supportsAgreementFamilyColumns, function ($qq) {
                $qq->whereNull('petty_hostels.agreement_parent_hostel_id');
            })
            ->leftJoinSub($lastPaySub, 'lp', function ($join) {
                $join->on('lp.root_hostel_id', '=', 'petty_hostels.id');
            })
            ->select('petty_hostels.*', 'lp.last_payment_date')
            ->selectRaw("$dueDateExpr as due_date_sort")
            ->when($q !== '', function ($qq) use ($q, $supportsOntSiteColumns, $supportsAgreementFamilyColumns, $supportsChainedColumns) {
                $like = '%' . str_replace('%', '\\%', $q) . '%';
                $qq->where(function ($w) use ($like, $supportsOntSiteColumns, $supportsAgreementFamilyColumns, $supportsChainedColumns) {
                    $w->where('hostel_name', 'like', $like)
                        ->orWhere('contact_person', 'like', $like)
                        ->orWhere('meter_no', 'like', $like)
                        ->orWhere('phone_no', 'like', $like);
                    if ($supportsOntSiteColumns) {
                        $w->orWhere('ont_site_sn', 'like', $like)
                            ->orWhere('ont_site_id', 'like', $like);
                    }
                    if ($supportsAgreementFamilyColumns) {
                        $w->orWhereExists(function ($childQuery) use ($like, $supportsOntSiteColumns) {
                            $childQuery
                                ->selectRaw('1')
                                ->from('petty_hostels as child_hostels')
                                ->whereColumn('child_hostels.agreement_parent_hostel_id', 'petty_hostels.id')
                                ->where(function ($childWhere) use ($like, $supportsOntSiteColumns) {
                                    $childWhere
                                        ->where('child_hostels.hostel_name', 'like', $like)
                                        ->orWhere('child_hostels.contact_person', 'like', $like)
                                        ->orWhere('child_hostels.meter_no', 'like', $like)
                                        ->orWhere('child_hostels.phone_no', 'like', $like);
                                    if ($supportsOntSiteColumns) {
                                        $childWhere
                                            ->orWhere('child_hostels.ont_site_sn', 'like', $like)
                                            ->orWhere('child_hostels.ont_site_id', 'like', $like);
                                    }
                                });
                        });
                    }
                    if ($supportsChainedColumns) {
                        $w->orWhereExists(function ($chainQuery) use ($like, $supportsOntSiteColumns) {
                            $chainQuery
                                ->selectRaw('1')
                                ->from('petty_hostels as chain_hostels')
                                ->whereColumn('chain_hostels.id', 'petty_hostels.chained_from_hostel_id')
                                ->where(function ($chainWhere) use ($like, $supportsOntSiteColumns) {
                                    $chainWhere
                                        ->where('chain_hostels.hostel_name', 'like', $like)
                                        ->orWhere('chain_hostels.contact_person', 'like', $like);
                                    if ($supportsOntSiteColumns) {
                                        $chainWhere
                                            ->orWhere('chain_hostels.ont_site_sn', 'like', $like)
                                            ->orWhere('chain_hostels.ont_site_id', 'like', $like);
                                    }
                                });
                        });
                    }
                });
            })
            ->when($terminationSupported, function ($qq) {
                $qq->whereNull('petty_hostels.agreement_terminated_at');
            });

        $todayStr = $today->toDateString();
        $tomorrowStr = $today->copy()->addDay()->toDateString();
        $in2DaysStr = $today->copy()->addDays(2)->toDateString();
        $in3DaysStr = $today->copy()->addDays(3)->toDateString();

        $remindersDue = $remindersBase->clone()->whereRaw("DATE($dueDateExpr) = ?", [$todayStr])->get();
        $reminders1 = $remindersBase->clone()->whereRaw("DATE($dueDateExpr) = ?", [$tomorrowStr])->get();
        $reminders2 = $remindersBase->clone()->whereRaw("DATE($dueDateExpr) = ?", [$in2DaysStr])->get();
        $reminders3 = $remindersBase->clone()->whereRaw("DATE($dueDateExpr) = ?", [$in3DaysStr])->get();
        $remindersOv = $remindersBase->clone()->whereRaw("DATE($dueDateExpr) < ?", [$todayStr])->get();
        $remindersNoPay = $remindersBase->clone()->whereNull('lp.last_payment_date')->get();

        // Now paginate the filtered hostels for display
        $hostels = $hostels
            ->paginate($perPage)
            ->withQueryString();

        $pageHostels = $hostels->getCollection();
        $familyIdsByRoot = [];
        $hostelToRootId = [];

        foreach ($pageHostels as $pageHostel) {
            $familyIds = $this->familyHostelIdsForRoot($pageHostel);
            $familyIdsByRoot[(int) $pageHostel->id] = $familyIds;

            foreach ($familyIds as $familyHostelId) {
                $hostelToRootId[(int) $familyHostelId] = (int) $pageHostel->id;
            }
        }

        $routerTotalsByRoot = collect();
        if (!empty($hostelToRootId)) {
            $routerTotals = Hostel::query()
                ->whereIn('id', array_keys($hostelToRootId))
                ->select('id', 'no_of_routers')
                ->get();

            foreach ($routerTotals as $routerRow) {
                $rootId = (int) ($hostelToRootId[(int) $routerRow->id] ?? 0);
                if ($rootId <= 0) {
                    continue;
                }

                $routerTotalsByRoot->put(
                    $rootId,
                    (int) $routerTotalsByRoot->get($rootId, 0) + (int) ($routerRow->no_of_routers ?? 0)
                );
            }
        }

        $chainParentNames = collect();
        if ($supportsChainedColumns) {
            $chainParentIds = $pageHostels
                ->pluck('chained_from_hostel_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (!empty($chainParentIds)) {
                $chainParentNames = Hostel::query()
                    ->whereIn('id', $chainParentIds)
                    ->pluck('hostel_name', 'id');
            }
        }

        $lastPayments = collect();
        if (!empty($hostelToRootId)) {
            $paymentRows = Payment::query()
                ->whereIn('hostel_id', array_keys($hostelToRootId))
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            foreach ($paymentRows as $paymentRow) {
                $rootId = (int) ($hostelToRootId[(int) $paymentRow->hostel_id] ?? 0);
                if ($rootId <= 0 || $lastPayments->has($rootId)) {
                    continue;
                }

                $lastPayments->put($rootId, $paymentRow);

                if ($lastPayments->count() >= count($familyIdsByRoot)) {
                    break;
                }
            }
        }

        $pageHostels = $pageHostels->map(function ($h) use ($today, $lastPayments, $terminationSupported, $familyIdsByRoot, $supportsChainedColumns, $chainParentNames, $routerTotalsByRoot) {
            $last = $lastPayments->get((int) $h->id);
            $familyIds = $familyIdsByRoot[(int) $h->id] ?? [(int) $h->id];

            $h->last_payment_amount = $last ? (float) $last->amount : null;
            $h->last_payment_date = $last?->date?->format('Y-m-d');
            $h->merged_child_count = max(0, count($familyIds) - 1);
            $h->family_router_total = (int) $routerTotalsByRoot->get((int) $h->id, (int) ($h->no_of_routers ?? 0));
            $h->is_chained = $supportsChainedColumns && !empty($h->chained_from_hostel_id);
            $h->chained_from_name = $h->is_chained
                ? (string) ($chainParentNames->get((int) $h->chained_from_hostel_id) ?? '')
                : null;

            $lastDate = $last?->date ? Carbon::parse($last->date)->startOfDay() : null;

            if ($terminationSupported && !empty($h->agreement_terminated_at)) {
                $h->next_due_date = null;
                $h->days_to_due = null;
                $h->due_badge = 'Agreement terminated';
                $h->due_status = 'terminated';
            } elseif ($lastDate) {
                $stake = $h->stake ?: 'monthly';

                if ($stake === 'semester') {
                    // CHANGE 4 -> 6 if semester should be 6 months
                    $due = $lastDate->copy()->addMonthsNoOverflow(4)->startOfDay();
                } else {
                    $due = $lastDate->copy()->addMonthNoOverflow()->startOfDay();
                }

                $h->next_due_date = $due->format('Y-m-d');
                $h->days_to_due = $today->diffInDays($due, false);

                $d = (int) $h->days_to_due;
                if ($d === 3) { $h->due_badge = 'Due in 3 days'; $h->due_status = 'upcoming'; }
                elseif ($d === 2) { $h->due_badge = 'Due in 2 days'; $h->due_status = 'upcoming'; }
                elseif ($d === 1) { $h->due_badge = 'Due tomorrow'; $h->due_status = 'upcoming'; }
                elseif ($d === 0) { $h->due_badge = 'Due today'; $h->due_status = 'due_today'; }
                elseif ($d < 0) { $h->due_badge = 'Overdue by ' . abs($d) . ' day' . (abs($d) === 1 ? '' : 's'); $h->due_status = 'overdue'; }
                else { $h->due_badge = 'Due in ' . $d . ' days'; $h->due_status = 'upcoming'; }
            } else {
                // No payment history
                if ((bool) ($h->is_due_immediately ?? false)) {
                    // Marked as due immediately with no payments = due today
                    $h->next_due_date = $today->format('Y-m-d');
                    $h->days_to_due = 0;
                    $h->due_badge = 'Due today';
                    $h->due_status = 'due_today';
                } else {
                    $h->next_due_date = null;
                    $h->days_to_due = null;
                    $h->due_badge = 'No payments yet';
                    $h->due_status = 'unknown';
                }
            }

            return $h;
        });
        $hostels->setCollection($pageHostels);

        // Buckets for reminders banner (using full dataset stats, not just current page)
        $reminders = [
            'due_today' => $remindersDue,
            'due_1'     => $reminders1,
            'due_2'     => $reminders2,
            'due_3'     => $reminders3,
            'overdue'   => $remindersOv,
            'no_payments' => $remindersNoPay,
        ];

        $canCreateHostel = PettyAccess::allows(auth('petty')->user(), 'tokens.create_hostel');
        $canManageAgreements = $canCreateHostel || PettyAccess::allows(auth('petty')->user(), 'tokens.edit_hostel');
        $ontCatalog = $canCreateHostel
            ? $this->ontCatalogForUi()
            : ['available' => false, 'message' => '', 'hostels' => []];
        $chainingSupported = $supportsChainedColumns && $supportsOntSiteColumns;
        $agreementHistorySuggestions = collect();
        $agreementHistorySummary = [
            'scanned' => 0,
            'suggested' => 0,
            'token' => 0,
            'send_money' => 0,
            'package' => 0,
        ];
        $shouldOpenAgreementSyncModal = $canManageAgreements
            && $this->hostelAgreementColumnsAvailable()
            && (
                $request->boolean('agreement_scan')
                || strtolower((string) $request->query('modal', '')) === 'agreement-history-sync'
                || old('form_context') === 'agreement_history_apply'
            );

        if ($shouldOpenAgreementSyncModal) {
            $agreementHistorySuggestions = $this->detectAgreementHistorySuggestions();
            $agreementHistorySummary = [
                'scanned' => Hostel::query()
                    ->when($supportsAgreementFamilyColumns, fn ($query) => $query->whereNull('agreement_parent_hostel_id'))
                    ->count(),
                'suggested' => $agreementHistorySuggestions->count(),
                'token' => $agreementHistorySuggestions->where('suggested.agreement_type', 'token')->count(),
                'send_money' => $agreementHistorySuggestions->where('suggested.agreement_type', 'send_money')->count(),
                'package' => $agreementHistorySuggestions->where('suggested.agreement_type', 'package')->count(),
            ];
        }

        return view('pettycash::spendings.tokens.index', compact(
            'hostels',
            'q',
            'sortDue',
            'dueFilter',
            'dueInDays',
            'reminders',
            'today',
            'totalHostels',
            'perPage',
            'perPageOptions',
            'ontCatalog',
            'terminationSupported',
            'chainingSupported',
            'agreementHistorySuggestions',
            'agreementHistorySummary',
            'shouldOpenAgreementSyncModal'
        ));
    }

    public function create()
    {
        $ontCatalog = $this->ontCatalogForUi();
        $chainingSupported = $this->hostelChainedColumnsAvailable() && $this->hostelOntColumnsAvailable();

        return view('pettycash::spendings.tokens.create', compact('ontCatalog', 'chainingSupported'));
    }

    public function searchOnts(Request $request)
    {
        $user = auth('petty')->user();
        $canSearch = PettyAccess::allows($user, 'tokens.create_hostel')
            || PettyAccess::allows($user, 'tokens.edit_hostel');

        abort_unless($canSearch, 403);

        $q = trim((string) $request->query('q', ''));
        $limit = max(5, min(100, (int) $request->integer('limit', 30)));

        $catalog = $this->ontCatalogForUi($q, $limit);
        $status = (bool) ($catalog['available'] ?? false) ? 200 : 503;

        return response()->json([
            'available' => (bool) ($catalog['available'] ?? false),
            'message' => (string) ($catalog['message'] ?? ''),
            'fetched_at' => $catalog['fetched_at'] ?? null,
            'source_count' => (int) ($catalog['source_count'] ?? 0),
            'onts' => array_values((array) ($catalog['hostels'] ?? [])),
        ], $status);
    }

    public function storeHostel(Request $request)
    {
        $data = $request->validate([
            'create_mode' => ['required', 'in:ont_site,chained_hostel'],
            'hostel_name' => ['nullable', 'string', 'max:255'],
            'ont_key' => ['nullable', 'string', 'max:120'],
            'chained_from_hostel_id' => ['nullable', 'integer', 'exists:petty_hostels,id'],
            'chained_from_ont_key' => ['nullable', 'string', 'max:120'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone_no' => ['required', 'string', 'max:255'],
            'no_of_routers' => ['required', 'integer', 'min:1'],
        ]);

        $createMode = strtolower(trim((string) ($data['create_mode'] ?? 'ont_site')));
        if (!in_array($createMode, ['ont_site', 'chained_hostel'], true)) {
            $createMode = 'ont_site';
        }

        if ($createMode === 'chained_hostel') {
            if (!$this->hostelChainedColumnsAvailable() || !$this->hostelOntColumnsAvailable()) {
                return back()->withErrors([
                    'create_mode' => 'Chained hostel mode is not available until the latest hostel migrations are run.',
                ])->withInput();
            }

            $manualHostelName = trim((string) ($data['hostel_name'] ?? ''));
            if ($manualHostelName === '') {
                return back()->withErrors([
                    'hostel_name' => 'Hostel name is required for chained hostels.',
                ])->withInput();
            }

            [$chainParent, $chainParentError] = $this->resolveChainedFromSelection(
                (int) ($data['chained_from_hostel_id'] ?? 0),
                (string) ($data['chained_from_ont_key'] ?? '')
            );
            if (!$chainParent) {
                return back()->withErrors([
                    'chained_from_hostel_id' => $chainParentError ?? 'Select the main ONT/site this chained hostel depends on.',
                ])->withInput();
            }

            $payload = [
                'hostel_name' => $manualHostelName,
                'contact_person' => $data['contact_person'],
                'meter_no' => null,
                'phone_no' => $data['phone_no'],
                'no_of_routers' => $this->resolveRoutersInput($data),
                'stake' => 'monthly',
                'amount_due' => 0,
                'chained_from_hostel_id' => (int) $chainParent->id,
            ];
            if ($this->hostelOntColumnsAvailable()) {
                $payload['ont_site_id'] = $chainParent->ont_site_id ?? null;
                $payload['ont_site_sn'] = $chainParent->ont_site_sn ?? null;
            }
            if ($this->hostelAgreementColumnsAvailable()) {
                $payload['agreement_type'] = 'none';
                $payload['agreement_label'] = null;
            }
            if ($this->hostelOntMergedColumnAvailable()) {
                $payload['ont_merged'] = false;
            }

            $hostel = Hostel::create($payload);

            return redirect()
                ->route('petty.tokens.hostels.agreement', ['hostel' => $hostel->id, 'setup' => 1])
                ->with('success', 'Chained hostel created. Now set the hostel agreement.');
        }

        [$candidate, $validationError] = $this->resolveOntCandidate(
            (string) ($data['hostel_name'] ?? ''),
            (string) ($data['ont_key'] ?? '')
        );
        if ($validationError !== null) {
            return back()->withErrors(['hostel_name' => $validationError])->withInput();
        }

        if ($candidate !== null) {
            $data['hostel_name'] = $candidate['hostel_name'];
        }

        if (trim((string) ($data['hostel_name'] ?? '')) === '') {
            return back()->withErrors([
                'hostel_name' => 'Hostel name is required.',
            ])->withInput();
        }

        $payload = [
            'hostel_name' => $data['hostel_name'],
            'contact_person' => $data['contact_person'],
            'meter_no' => null,
            'phone_no' => $data['phone_no'],
            'no_of_routers' => $this->resolveRoutersInput($data),
            'stake' => 'monthly',
            'amount_due' => 0,
        ];
        if ($this->hostelOntColumnsAvailable()) {
            $payload['ont_site_id'] = (string) ($candidate['site_id'] ?? '') !== '' ? (string) $candidate['site_id'] : null;
            $payload['ont_site_sn'] = (string) ($candidate['site_sn'] ?? '') !== '' ? (string) $candidate['site_sn'] : null;
        }
        if ($this->hostelAgreementColumnsAvailable()) {
            $payload['agreement_type'] = 'none';
            $payload['agreement_label'] = null;
        }
        if ($this->hostelChainedColumnsAvailable()) {
            $payload['chained_from_hostel_id'] = null;
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = (bool) ($candidate !== null);
        }

        $hostel = Hostel::create($payload);

        return redirect()
            ->route('petty.tokens.hostels.agreement', ['hostel' => $hostel->id, 'setup' => 1])
            ->with('success', 'Step 1 saved. Now set the hostel agreement.');
    }

    public function agreement(Hostel $hostel)
    {
        $user = auth('petty')->user();
        $canManage = PettyAccess::allows($user, 'tokens.create_hostel')
            || PettyAccess::allows($user, 'tokens.edit_hostel');
        abort_unless($canManage, 403);

        $rootHostel = $this->resolveAgreementRootHostel($hostel);
        if ((int) $rootHostel->id !== (int) $hostel->id) {
            return redirect()
                ->route('petty.tokens.hostels.agreement', $rootHostel->id)
                ->with('warning', 'This agreement is managed from the main hostel view.');
        }

        $agreementType = $this->normalizeAgreementType((string) ($rootHostel->agreement_type ?? 'none'));
        $terminationSupported = $this->hostelAgreementTerminationColumnsAvailable();
        $familyChildren = collect($this->attachHostelPaymentSummaries($this->familyChildHostelsForRoot($rootHostel)));
        $mergePickerState = $this->ontCatalogForUi();

        return view('pettycash::spendings.tokens.agreement', [
            'hostel' => $rootHostel,
            'agreementType' => $agreementType,
            'terminationSupported' => $terminationSupported,
            'familyChildren' => $familyChildren,
            'mergePickerState' => $mergePickerState,
        ]);
    }

    public function searchHostels(Request $request)
    {
        $user = auth('petty')->user();
        $canSearch = PettyAccess::allows($user, 'tokens.create_hostel')
            || PettyAccess::allows($user, 'tokens.edit_hostel');
        abort_unless($canSearch, 403);

        $source = strtolower(trim((string) $request->query('source', 'mergeable')));
        $excludeId = (int) $request->integer('exclude', 0);
        $limit = max(5, min(50, (int) $request->integer('limit', 20)));
        $idsParam = trim((string) $request->query('ids', ''));
        $supportsOntSiteColumns = $this->hostelOntColumnsAvailable();
        $supportsAgreementFamilyColumns = $this->hostelAgreementFamilyColumnsAvailable();
        $excludedFamilyIds = collect();

        if ($excludeId > 0 && $supportsAgreementFamilyColumns) {
            $excludeHostel = Hostel::query()->find($excludeId);
            if ($excludeHostel) {
                $excludeRoot = $this->resolveAgreementRootHostel($excludeHostel);
                $excludedFamilyIds = collect($this->familyHostelIdsForRoot($excludeRoot));
            }
        }

        if ($source === 'ont_catalog') {
            if ($idsParam !== '') {
                $selectColumns = ['id', 'hostel_name', 'meter_no', 'phone_no'];
                if ($supportsAgreementFamilyColumns) {
                    $selectColumns[] = 'agreement_parent_hostel_id';
                }
                if ($supportsOntSiteColumns) {
                    $selectColumns[] = 'ont_site_sn';
                    $selectColumns[] = 'ont_site_id';
                }

                $ids = collect(explode(',', $idsParam))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->take(50);

                $items = $ids->isEmpty()
                    ? collect()
                    : Hostel::query()
                        ->whereIn('id', $ids->all())
                        ->orderBy('hostel_name')
                        ->get($selectColumns);

                return response()->json([
                    'available' => true,
                    'message' => '',
                    'hostels' => $this->attachHostelPaymentSummaries($items),
                ]);
            }

            $q = trim((string) $request->query('q', ''));
            if ($q === '') {
                return response()->json([
                    'available' => true,
                    'message' => 'Type an ONT site or hostel name.',
                    'hostels' => [],
                    'onts' => [],
                ]);
            }

            $catalog = $this->ontCatalogForUi($q, $limit);
            $available = (bool) ($catalog['available'] ?? false);
            $items = array_values($this->filterOntCatalogCandidatesForExcludedFamily(
                (array) ($catalog['hostels'] ?? []),
                $excludedFamilyIds->all()
            ));

            return response()->json([
                'available' => $available,
                'message' => (string) ($catalog['message'] ?? ($available ? 'Choose the ONT site.' : 'ONT directory is unavailable.')),
                'fetched_at' => $catalog['fetched_at'] ?? null,
                'source_count' => (int) ($catalog['source_count'] ?? count($items)),
                'hostels' => $items,
                'onts' => $items,
            ], $available ? 200 : 503);
        }

        if ($source === 'chain_parent') {
            if (!$this->hostelChainedColumnsAvailable() || !$this->hostelOntColumnsAvailable()) {
                return response()->json([
                    'available' => false,
                    'message' => 'Chained hostel mode is not available yet.',
                    'hostels' => [],
                ], 503);
            }

            $ontKey = trim((string) $request->query('ont_key', ''));

            if ($idsParam !== '' || $ontKey !== '') {
                $items = [];

                if ($idsParam !== '') {
                    $ids = collect(explode(',', $idsParam))
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->take(50)
                        ->all();

                    $items = array_merge($items, $this->chainParentOptionsFromExistingIds($ids, $excludedFamilyIds->all(), $excludeId));
                }

                if ($ontKey !== '') {
                    [$candidate, $validationError] = $this->resolveOntCandidate('', $ontKey);
                    if ($validationError !== null || $candidate === null) {
                        return response()->json([
                            'available' => false,
                            'message' => $validationError ?? 'Selected ONT/site is no longer available.',
                            'hostels' => [],
                        ], 422);
                    }

                    $items = array_merge($items, $this->chainParentOptionsFromOntCandidates([$candidate], $excludedFamilyIds->all(), $excludeId));
                }

                $items = collect($items)
                    ->unique(fn ($item) => (string) ($item['ont_key'] ?? 'row:' . ($item['id'] ?? '')))
                    ->values()
                    ->all();

                return response()->json([
                    'available' => true,
                    'message' => '',
                    'hostels' => $items,
                ]);
            }

            $q = trim((string) $request->query('q', ''));
            if ($q === '') {
                return response()->json([
                    'available' => true,
                    'message' => 'Type a main ONT/site, site id, or site serial.',
                    'hostels' => [],
                ]);
            }

            $catalog = $this->ontCatalogForUi($q, $limit);
            $available = (bool) ($catalog['available'] ?? false);
            $items = $this->chainParentOptionsFromOntCandidates(
                (array) ($catalog['hostels'] ?? []),
                $excludedFamilyIds->all(),
                $excludeId
            );

            return response()->json([
                'available' => $available,
                'message' => !$available
                    ? (string) ($catalog['message'] ?? 'ONT directory is unavailable.')
                    : (empty($items)
                        ? 'No main ONT/site found for chaining.'
                        : 'Choose the ONT/site this hostel depends on.'),
                'hostels' => $items,
            ], $available ? 200 : 503);
        }

        $selectColumns = ['id', 'hostel_name', 'meter_no', 'phone_no'];
        if ($supportsAgreementFamilyColumns) {
            $selectColumns[] = 'agreement_parent_hostel_id';
        }
        if ($supportsOntSiteColumns) {
            $selectColumns[] = 'ont_site_sn';
            $selectColumns[] = 'ont_site_id';
        }

        $query = Hostel::query();
        if ($excludeId > 0 && !$supportsAgreementFamilyColumns) {
            $query->where('id', '<>', $excludeId);
        }

        if ($idsParam !== '') {
            $ids = collect(explode(',', $idsParam))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->take(50);

            if ($ids->isEmpty()) {
                return response()->json([
                    'available' => true,
                    'message' => '',
                    'hostels' => [],
                ]);
            }

            $hostels = $query
                ->whereIn('id', $ids->all())
                ->orderBy('hostel_name')
                ->get($selectColumns);

            return response()->json([
                'available' => true,
                'message' => '',
                'hostels' => $this->attachHostelPaymentSummaries($hostels),
            ]);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([
                'available' => true,
                'message' => 'Type an ONT site or hostel name.',
                'hostels' => [],
            ]);
        }

        $catalog = $this->ontCatalogForUi($q, max(20, $limit * 3));
        if (!(bool) ($catalog['available'] ?? false)) {
            return response()->json([
                'available' => false,
                'message' => (string) ($catalog['message'] ?? 'ONT directory is unavailable.'),
                'hostels' => [],
            ], 503);
        }

        $hostels = $this->searchMergeableHostelsFromOntCatalog(
            $catalog,
            $excludedFamilyIds->all(),
            $limit
        );

        return response()->json([
            'available' => true,
            'message' => empty($hostels)
                ? 'No ONT-linked hostel is ready to join here.'
                : 'Choose the hostel to join under this parent.',
            'hostels' => $hostels,
        ]);
    }

    private function attachHostelPaymentSummaries($hostels)
    {
        $hostelList = collect($hostels ?? []);
        if ($hostelList->isEmpty()) {
            return [];
        }

        $hostelIds = $hostelList->pluck('id')->filter()->unique()->values()->all();
        if (empty($hostelIds)) {
            return $hostelList->values()->toArray();
        }

        $supportsAgreementFamilyColumns = $this->hostelAgreementFamilyColumnsAvailable();

        $paymentStats = Payment::query()
            ->select('hostel_id')
            ->selectRaw('COUNT(*) as payments_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as payments_amount')
            ->selectRaw('COALESCE(SUM(transaction_cost), 0) as payments_fee')
            ->selectRaw('MAX(date) as last_payment_date')
            ->whereIn('hostel_id', $hostelIds)
            ->groupBy('hostel_id')
            ->get()
            ->keyBy('hostel_id');

        $childCounts = collect();
        $parentNames = collect();
        $familyRouterTotals = collect();

        if ($supportsAgreementFamilyColumns) {
            $childCounts = Hostel::query()
                ->select('agreement_parent_hostel_id')
                ->selectRaw('COUNT(*) as child_count')
                ->whereIn('agreement_parent_hostel_id', $hostelIds)
                ->groupBy('agreement_parent_hostel_id')
                ->get()
                ->keyBy('agreement_parent_hostel_id');

            $parentIds = $hostelList
                ->pluck('agreement_parent_hostel_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($parentIds)) {
                $parentNames = Hostel::query()
                    ->whereIn('id', $parentIds)
                    ->pluck('hostel_name', 'id');
            }

            $familyRouterTotals = Hostel::query()
                ->selectRaw('COALESCE(agreement_parent_hostel_id, id) as root_hostel_id')
                ->selectRaw('COALESCE(SUM(no_of_routers), 0) as router_total')
                ->where(function ($query) use ($hostelIds, $parentIds) {
                    $query->whereIn('id', $hostelIds);
                    $query->orWhereIn('agreement_parent_hostel_id', $hostelIds);
                    if (!empty($parentIds)) {
                        $query->orWhereIn('agreement_parent_hostel_id', $parentIds);
                    }
                })
                ->groupBy('root_hostel_id')
                ->get()
                ->keyBy('root_hostel_id');
        }

        return $hostelList->map(function ($hostel) use ($paymentStats, $supportsAgreementFamilyColumns, $childCounts, $parentNames, $familyRouterTotals) {
            $stats = $paymentStats->get($hostel->id);
            $parentId = $supportsAgreementFamilyColumns ? (int) ($hostel->agreement_parent_hostel_id ?? 0) : 0;
            $managedChildCount = $supportsAgreementFamilyColumns
                ? (int) ($childCounts->get($hostel->id)->child_count ?? 0)
                : 0;
            $routeHostelId = $parentId > 0 ? $parentId : (int) $hostel->id;
            $routerRootId = $parentId > 0 ? $parentId : (int) $hostel->id;
            $familyRouterTotal = $supportsAgreementFamilyColumns
                ? (int) ($familyRouterTotals->get($routerRootId)->router_total ?? ($hostel->no_of_routers ?? 0))
                : (int) ($hostel->no_of_routers ?? 0);

            return [
                'id' => (int) $hostel->id,
                'hostel_name' => $hostel->hostel_name,
                'contact_person' => $hostel->contact_person ?? null,
                'meter_no' => $hostel->meter_no,
                'phone_no' => $hostel->phone_no,
                'no_of_routers' => (int) ($hostel->no_of_routers ?? 0),
                'stake' => (string) ($hostel->stake ?? 'monthly'),
                'amount_due' => (float) ($hostel->amount_due ?? 0),
                'agreement_type' => $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none')),
                'agreement_label' => $hostel->agreement_label ?? null,
                'ont_site_sn' => $hostel->ont_site_sn ?? null,
                'ont_site_id' => $hostel->ont_site_id ?? null,
                'ont_merged' => (bool) ($hostel->ont_merged ?? false),
                'agreement_parent_hostel_id' => $parentId > 0 ? $parentId : null,
                'route_hostel_id' => $routeHostelId,
                'family_role' => $parentId > 0 ? 'child' : ($managedChildCount > 0 ? 'parent' : 'standalone'),
                'family_head_name' => $parentId > 0 ? (string) ($parentNames->get($parentId) ?? '') : null,
                'managed_child_count' => $managedChildCount,
                'family_router_total' => $familyRouterTotal,
                'payments_count' => (int) ($stats->payments_count ?? 0),
                'payments_amount' => (float) ($stats->payments_amount ?? 0),
                'payments_fee' => (float) ($stats->payments_fee ?? 0),
                'last_payment_date' => !empty($stats?->last_payment_date)
                    ? Carbon::parse($stats->last_payment_date)->format('Y-m-d')
                    : null,
            ];
        })->values()->toArray();
    }

    public function updateAgreement(Hostel $hostel, Request $request)
    {
        $user = auth('petty')->user();
        $canManage = PettyAccess::allows($user, 'tokens.create_hostel')
            || PettyAccess::allows($user, 'tokens.edit_hostel');
        abort_unless($canManage, 403);

        if (!$this->hostelAgreementColumnsAvailable()) {
            return back()->with('error', 'Agreement columns are not available yet. Run the latest migrations first.');
        }

        $rootHostel = $this->resolveAgreementRootHostel($hostel);

        $data = $request->validate([
            'agreement_type' => ['required', 'in:token,send_money,package,none'],
            'agreement_label' => ['nullable', 'string', 'max:255'],
            'meter_no' => ['nullable', 'string', 'max:255'],
            'phone_no' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'stake' => ['required', 'in:monthly,semester'],
            'amount_due' => ['required', 'numeric', 'min:0'],
            'apply_to_hostels' => ['nullable', 'array'],
            'apply_to_hostels.*' => ['integer', 'exists:petty_hostels,id'],
            'apply_to_ont_keys' => ['nullable', 'array'],
            'apply_to_ont_keys.*' => ['string', 'max:120'],
        ]);

        $agreementType = $this->normalizeAgreementType((string) ($data['agreement_type'] ?? 'none'));
        $meterNo = trim((string) ($data['meter_no'] ?? ''));
        $existingPhoneNo = trim((string) ($rootHostel->phone_no ?? ''));
        $existingContactPerson = trim((string) ($rootHostel->contact_person ?? ''));
        $phoneNo = trim((string) ($data['phone_no'] ?? ''));
        $agreementLabel = trim((string) ($data['agreement_label'] ?? ''));
        $contactPerson = trim((string) ($data['contact_person'] ?? ''));

        if ($agreementType === 'token' && $meterNo === '') {
            throw ValidationException::withMessages([
                'meter_no' => 'Meter number is required for token agreement.',
            ]);
        }

        if ($agreementType === 'send_money' && $phoneNo === '') {
            throw ValidationException::withMessages([
                'phone_no' => 'Phone number is required for send money agreement.',
            ]);
        }

        if ($agreementType === 'send_money' && $contactPerson === '') {
            throw ValidationException::withMessages([
                'contact_person' => 'Recipient name is required for send money agreement.',
            ]);
        }

        if ($agreementType === 'package' && $agreementLabel === '') {
            throw ValidationException::withMessages([
                'agreement_label' => 'Package name/details are required for package agreement.',
            ]);
        }

        $payload = [
            'agreement_type' => $agreementType,
            'agreement_label' => null,
            'contact_person' => $contactPerson !== '' ? $contactPerson : ($existingContactPerson !== '' ? $existingContactPerson : null),
            'stake' => $data['stake'],
            'amount_due' => (float) $data['amount_due'],
        ];
        if ($this->hostelAgreementTerminationColumnsAvailable()) {
            $payload['agreement_terminated_at'] = null;
            $payload['agreement_termination_reason'] = null;
            $payload['agreement_termination_notes'] = null;
            $payload['agreement_transfer_hostel_id'] = null;
        }

        if ($agreementType === 'token') {
            $payload['meter_no'] = $meterNo;
            $payload['phone_no'] = ($phoneNo !== '' ? $phoneNo : ($existingPhoneNo !== '' ? $existingPhoneNo : null));
        } elseif ($agreementType === 'send_money') {
            $payload['phone_no'] = $phoneNo;
            $payload['meter_no'] = null;
        } elseif ($agreementType === 'package') {
            $payload['agreement_label'] = $agreementLabel;
            $payload['phone_no'] = ($phoneNo !== '' ? $phoneNo : ($existingPhoneNo !== '' ? $existingPhoneNo : null));
            $payload['meter_no'] = null;
        } else {
            $payload['meter_no'] = $rootHostel->meter_no ?: null;
            $payload['phone_no'] = ($existingPhoneNo !== '' ? $existingPhoneNo : null);
        }

        $applyHostels = collect($data['apply_to_hostels'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === (int) $rootHostel->id)
            ->values();

        $applyOntKeys = collect($data['apply_to_ont_keys'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $applyOntCandidates = collect();
        foreach ($applyOntKeys as $ontKey) {
            [$candidate, $validationError] = $this->resolveOntCandidate('', $ontKey);
            if ($validationError !== null || $candidate === null) {
                throw ValidationException::withMessages([
                    'apply_to_ont_keys' => $validationError ?? 'Select child hostels from the ONT directory list.',
                ]);
            }

            $applyOntCandidates->push((array) $candidate);
        }

        foreach ($applyHostels as $selectedId) {
            $selectedHostel = Hostel::query()->find($selectedId);
            if (!$selectedHostel) {
                continue;
            }

            $this->ensureHostelFamilyIsOntBacked($this->resolveAgreementRootHostel($selectedHostel));
        }

        PettyDatabase::transaction(function () use ($rootHostel, $payload, $applyHostels, $applyOntCandidates) {
            $rootHostel->update($payload);

            if ($this->hostelAgreementFamilyColumnsAvailable()) {
                $currentChildIds = Hostel::query()
                    ->where('agreement_parent_hostel_id', $rootHostel->id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $targetChildIds = collect();

                foreach ($applyHostels as $selectedId) {
                    $selectedHostel = Hostel::query()->find($selectedId);
                    if (!$selectedHostel) {
                        continue;
                    }

                    $selectedRoot = $this->resolveAgreementRootHostel($selectedHostel);

                    if ((int) $selectedRoot->id === (int) $rootHostel->id) {
                        if ((int) $selectedHostel->id !== (int) $rootHostel->id) {
                            $targetChildIds->push((int) $selectedHostel->id);
                        }
                        continue;
                    }

                    foreach ($this->familyHostelIdsForRoot($selectedRoot) as $familyMemberId) {
                        if ((int) $familyMemberId === (int) $rootHostel->id) {
                            continue;
                        }
                        $targetChildIds->push((int) $familyMemberId);
                    }
                }

                foreach ($applyOntCandidates as $candidate) {
                    $selectedHostel = $this->resolveOrCreateHostelFromOntCandidate((array) $candidate);
                    $selectedRoot = $this->resolveAgreementRootHostel($selectedHostel);

                    if ((int) $selectedRoot->id === (int) $rootHostel->id) {
                        if ((int) $selectedHostel->id !== (int) $rootHostel->id) {
                            $targetChildIds->push((int) $selectedHostel->id);
                        }
                        continue;
                    }

                    foreach ($this->familyHostelIdsForRoot($selectedRoot) as $familyMemberId) {
                        if ((int) $familyMemberId === (int) $rootHostel->id) {
                            continue;
                        }
                        $targetChildIds->push((int) $familyMemberId);
                    }
                }

                $targetChildIds = $targetChildIds->unique()->values();
                $childSyncPayload = $payload;
                unset($childSyncPayload['meter_no']);

                $detachIds = $currentChildIds->diff($targetChildIds)->values();
                if ($detachIds->isNotEmpty()) {
                    Hostel::query()
                        ->whereIn('id', $detachIds->all())
                        ->update(array_merge($childSyncPayload, [
                            'agreement_parent_hostel_id' => null,
                        ]));
                }

                if ($targetChildIds->isNotEmpty()) {
                    Hostel::query()
                        ->whereIn('id', $targetChildIds->all())
                        ->update(array_merge($childSyncPayload, [
                            'agreement_parent_hostel_id' => (int) $rootHostel->id,
                        ]));
                }

                return;
            }

            if ($applyHostels->isNotEmpty()) {
                Hostel::query()
                    ->whereIn('id', $applyHostels->all())
                    ->update($payload);
            }
        });

        $childCount = $this->hostelAgreementFamilyColumnsAvailable()
            ? Hostel::query()->where('agreement_parent_hostel_id', $rootHostel->id)->count()
            : $applyHostels->count();

        $message = $childCount > 0
            ? ('Agreement saved. ' . $childCount . ' joined hostel' . ($childCount === 1 ? '' : 's') . ' now flow through ' . $rootHostel->hostel_name . '.')
            : 'Agreement saved.';

        return $this->successResponse(
            $request,
            $message,
            route('petty.tokens.hostels.show', $rootHostel->id)
        );
    }

    public function applyAgreementHistorySuggestions(Request $request)
    {
        $user = auth('petty')->user();
        $canManage = PettyAccess::allows($user, 'tokens.create_hostel')
            || PettyAccess::allows($user, 'tokens.edit_hostel');
        abort_unless($canManage, 403);

        if (!$this->hostelAgreementColumnsAvailable()) {
            return back()->with('error', 'Agreement columns are not available yet. Run the latest migrations first.');
        }

        $data = $request->validate([
            'form_context' => ['nullable', 'string'],
            'selected_hostels' => ['required', 'array', 'min:1'],
            'selected_hostels.*' => ['integer', 'exists:petty_hostels,id'],
            'suggestions' => ['required', 'array'],
            'suggestions.*.agreement_type' => ['required', 'in:token,send_money,package,none'],
            'suggestions.*.agreement_label' => ['nullable', 'string', 'max:255'],
            'suggestions.*.meter_no' => ['nullable', 'string', 'max:255'],
            'suggestions.*.phone_no' => ['nullable', 'string', 'max:255'],
            'suggestions.*.contact_person' => ['nullable', 'string', 'max:255'],
        ]);

        $selectedIds = collect($data['selected_hostels'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return back()->with('error', 'Select at least one hostel suggestion to apply.');
        }

        $hostels = Hostel::query()
            ->whereIn('id', $selectedIds->all())
            ->get()
            ->keyBy(fn (Hostel $hostel) => (int) $hostel->id);

        $applied = 0;
        $skipped = 0;

        PettyDatabase::transaction(function () use ($selectedIds, $data, $hostels, &$applied, &$skipped) {
            foreach ($selectedIds as $hostelId) {
                /** @var Hostel|null $hostel */
                $hostel = $hostels->get((int) $hostelId);
                $suggested = (array) ($data['suggestions'][(string) $hostelId] ?? $data['suggestions'][$hostelId] ?? []);
                if (!$hostel || empty($suggested)) {
                    $skipped++;
                    continue;
                }

                $agreementType = $this->normalizeAgreementType((string) ($suggested['agreement_type'] ?? 'none'));
                if ($agreementType === 'none') {
                    $skipped++;
                    continue;
                }

                $meterNo = trim((string) ($suggested['meter_no'] ?? ''));
                $phoneNo = trim((string) ($suggested['phone_no'] ?? ''));
                $contactPerson = trim((string) ($suggested['contact_person'] ?? ''));
                $agreementLabel = trim((string) ($suggested['agreement_label'] ?? ''));

                $payload = [
                    'agreement_type' => $agreementType,
                    'agreement_label' => null,
                ];

                if ($agreementType === 'token') {
                    if ($meterNo === '') {
                        $skipped++;
                        continue;
                    }
                    $payload['meter_no'] = $meterNo;
                    $payload['phone_no'] = $phoneNo !== '' ? $phoneNo : ($hostel->phone_no ?: null);
                    $payload['contact_person'] = $contactPerson !== '' ? $contactPerson : ($hostel->contact_person ?: null);
                } elseif ($agreementType === 'send_money') {
                    if ($phoneNo === '') {
                        $skipped++;
                        continue;
                    }
                    $payload['meter_no'] = null;
                    $payload['phone_no'] = $phoneNo;
                    $payload['contact_person'] = $contactPerson !== '' ? $contactPerson : ($hostel->contact_person ?: null);
                } elseif ($agreementType === 'package') {
                    $payload['meter_no'] = null;
                    $payload['phone_no'] = $phoneNo !== '' ? $phoneNo : ($hostel->phone_no ?: null);
                    $payload['contact_person'] = $contactPerson !== '' ? $contactPerson : ($hostel->contact_person ?: null);
                    $payload['agreement_label'] = $agreementLabel !== '' ? $agreementLabel : ($hostel->agreement_label ?: 'Package');
                }

                $hostel->fill($payload)->save();
                $applied++;
            }
        });

        return redirect()
            ->route('petty.tokens.index', [
                'modal' => 'agreement-history-sync',
                'agreement_scan' => 1,
            ])
            ->with('success', 'Applied ' . $applied . ' agreement update' . ($applied === 1 ? '' : 's') . ($skipped > 0 ? '. Skipped ' . $skipped . '.' : '.'));
    }

    public function terminateAgreement(Hostel $hostel, Request $request)
    {
        $hostel = $this->resolveAgreementRootHostel($hostel);
        $user = auth('petty')->user();
        $canManage = PettyAccess::allows($user, 'tokens.create_hostel')
            || PettyAccess::allows($user, 'tokens.edit_hostel');
        abort_unless($canManage, 403);

        if (!$this->hostelAgreementTerminationColumnsAvailable()) {
            return back()->with('error', 'Agreement termination fields are not available yet. Run the latest migrations first.');
        }

        $data = $request->validate([
            'termination_reason' => ['required', 'in:transfer_agreement,closed,moved,other'],
            'termination_notes' => ['nullable', 'string', 'max:255'],
            'transfer_hostel_id' => ['nullable', 'integer', 'exists:petty_hostels,id'],
        ]);

        $reason = strtolower(trim((string) $data['termination_reason']));
        $transferId = array_key_exists('transfer_hostel_id', $data) && $data['transfer_hostel_id'] !== null
            ? (int) $data['transfer_hostel_id']
            : null;

        if ($reason === 'transfer_agreement' && !$transferId) {
            return back()->withErrors([
                'transfer_hostel_id' => 'Transfer target is required when reason is transfer agreement.',
            ])->withInput();
        }

        if ($reason !== 'transfer_agreement') {
            $transferId = null;
        }

        if ($transferId !== null && $transferId === (int) $hostel->id) {
            return back()->withErrors([
                'transfer_hostel_id' => 'Transfer target cannot be the same hostel.',
            ])->withInput();
        }

        $payload = [
            'agreement_terminated_at' => now(),
            'agreement_termination_reason' => $reason,
            'agreement_termination_notes' => trim((string) ($data['termination_notes'] ?? '')) ?: null,
            'agreement_transfer_hostel_id' => $transferId,
        ];
        if ($this->hostelAgreementColumnsAvailable()) {
            $payload['agreement_type'] = 'none';
            $payload['agreement_label'] = null;
        }

        $hostel->update($payload);

        return redirect()
            ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id])
            ->with('success', 'Active agreement cleared. You can set a new agreement again later.');
    }

    public function updateHostel(Hostel $hostel, Request $request)
    {
        $rootHostel = $this->resolveAgreementRootHostel($hostel);
        $isChainedRootHostel = $this->hostelChainedColumnsAvailable() && !empty($rootHostel->chained_from_hostel_id);
        $data = $request->validate([
            'hostel_name' => ['nullable', 'string', 'max:255'],
            'ont_key' => ['nullable', 'string', 'max:120'],
            'chained_from_hostel_id' => ['nullable', 'integer', 'exists:petty_hostels,id'],
            'chained_from_ont_key' => ['nullable', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'meter_no' => ['nullable', 'string', 'max:255'],
            'phone_no' => ['nullable', 'string', 'max:255'],
            'no_of_routers' => ['nullable', 'integer', 'min:0'],
            'stake' => ['nullable', 'in:monthly,semester'],
            'amount_due' => ['nullable', 'numeric', 'min:0'],
            'edit_scope' => ['nullable', 'in:parent_only,parent_and_selected'],
            'selected_hostels' => ['nullable', 'array'],
            'selected_hostels.*' => ['integer', 'exists:petty_hostels,id'],
            'is_due_immediately' => ['nullable', 'boolean'],
        ]);

        $editScope = strtolower(trim((string) ($data['edit_scope'] ?? 'parent_only')));
        if (!in_array($editScope, ['parent_only', 'parent_and_selected'], true)) {
            $editScope = 'parent_only';
        }

        $selectedHostelIds = collect($data['selected_hostels'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $familyChildIds = $this->familyChildHostelsForRoot($rootHostel)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectedHostelIds->isNotEmpty()) {
            $invalidIds = $selectedHostelIds
                ->reject(fn ($id) => $familyChildIds->contains((int) $id))
                ->values();

            if ($invalidIds->isNotEmpty()) {
                return back()->withErrors([
                    'selected_hostels' => 'Some selected joined hostels do not belong to this parent hostel.',
                ])->withInput();
            }
        }

        if ($editScope === 'parent_and_selected' && $selectedHostelIds->isEmpty()) {
            return back()->withErrors([
                'selected_hostels' => 'Select at least one joined hostel to update together with the parent.',
            ])->withInput();
        }

        $candidate = null;
        $chainParent = null;

        if ($isChainedRootHostel) {
            [$chainParent, $chainParentError] = $this->resolveChainedFromSelection(
                (int) ($data['chained_from_hostel_id'] ?? $rootHostel->chained_from_hostel_id),
                (string) ($data['chained_from_ont_key'] ?? ''),
                (int) $rootHostel->id
            );
            if (!$chainParent) {
                return back()->withErrors([
                    'chained_from_hostel_id' => $chainParentError ?? 'Select the main ONT/site this chained hostel depends on.',
                ])->withInput();
            }

            $data['hostel_name'] = trim((string) ($data['hostel_name'] ?? $rootHostel->hostel_name));
        } else {
            if (array_key_exists('hostel_name', $data) || array_key_exists('ont_key', $data)) {
                [$candidate, $validationError] = $this->resolveOntCandidate(
                    (string) ($data['hostel_name'] ?? ''),
                    (string) ($data['ont_key'] ?? '')
                );
                if ($validationError !== null) {
                    return back()->withErrors(['hostel_name' => $validationError])->withInput();
                }
            } else {
                $candidate = null;
            }

            if ($candidate !== null) {
                $data['hostel_name'] = $candidate['hostel_name'];
            }
        }

        if (trim((string) ($data['hostel_name'] ?? '')) === '') {
            $data['hostel_name'] = (string) ($rootHostel->hostel_name ?? '');
        }

        $payload = [
            'hostel_name' => $data['hostel_name'],
            'contact_person' => $data['contact_person'] ?? null,
            'meter_no' => array_key_exists('meter_no', $data)
                ? (trim((string) ($data['meter_no'] ?? '')) !== '' ? trim((string) $data['meter_no']) : null)
                : $rootHostel->meter_no,
            'phone_no' => $data['phone_no'] ?? null,
            'no_of_routers' => $this->resolveRoutersInput($data, (int) ($rootHostel->no_of_routers ?? 0)),
            'stake' => $data['stake'] ?? (string) ($rootHostel->stake ?? 'monthly'),
            'amount_due' => array_key_exists('amount_due', $data)
                ? (float) ($data['amount_due'] ?? 0)
                : (float) ($rootHostel->amount_due ?? 0),
            'is_due_immediately' => array_key_exists('is_due_immediately', $data)
                ? (bool) $data['is_due_immediately']
                : (bool) ($rootHostel->is_due_immediately ?? false),
        ];

        if ($this->hostelOntColumnsAvailable()) {
            if ($isChainedRootHostel && $chainParent) {
                $payload['ont_site_id'] = $chainParent->ont_site_id ?? null;
                $payload['ont_site_sn'] = $chainParent->ont_site_sn ?? null;
            } else {
                $payload['ont_site_id'] = (string) ($candidate['site_id'] ?? '') !== '' ? (string) $candidate['site_id'] : ($rootHostel->ont_site_id ?? null);
                $payload['ont_site_sn'] = (string) ($candidate['site_sn'] ?? '') !== '' ? (string) $candidate['site_sn'] : ($rootHostel->ont_site_sn ?? null);
            }
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = $isChainedRootHostel
                ? false
                : (bool) ($candidate !== null || (bool) ($rootHostel->ont_merged ?? false));
        }
        if ($this->hostelChainedColumnsAvailable()) {
            $payload['chained_from_hostel_id'] = $isChainedRootHostel && $chainParent
                ? (int) $chainParent->id
                : null;
        }

        PettyDatabase::transaction(function () use ($rootHostel, $payload, $editScope, $selectedHostelIds) {
            $rootHostel->update($payload);

            if ($editScope !== 'parent_and_selected' || $selectedHostelIds->isEmpty()) {
                return;
            }

            $sharedChildPayload = [
                'contact_person' => $payload['contact_person'],
                'phone_no' => $payload['phone_no'],
                'stake' => $payload['stake'],
                'amount_due' => $payload['amount_due'],
            ];

            Hostel::query()
                ->whereIn('id', $selectedHostelIds->all())
                ->update($sharedChildPayload);
        });

        $message = $editScope === 'parent_and_selected'
            ? ('Parent updated and synced to ' . $selectedHostelIds->count() . ' joined hostel' . ($selectedHostelIds->count() === 1 ? '' : 's') . '.')
            : 'Hostel details updated.';

        return redirect()
            ->route('petty.tokens.hostels.show', $rootHostel->id)
            ->with('success', $message);
    }

    public function mergeHostelOnt(Hostel $hostel, Request $request)
    {
        if ((bool) ($hostel->ont_merged ?? false)) {
            return redirect()
                ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id])
                ->with('success', 'Hostel already merged from ONT directory.');
        }

        $data = $request->validate([
            'ont_key' => ['required', 'string', 'max:120'],
            'hostel_name' => ['nullable', 'string', 'max:255'],
            'no_of_routers' => ['nullable', 'integer', 'min:0'],
        ]);

        [$candidate, $validationError] = $this->resolveOntCandidate(
            (string) ($data['hostel_name'] ?? ''),
            (string) ($data['ont_key'] ?? '')
        );
        if ($validationError !== null || $candidate === null) {
            return back()->withErrors([
                'ont_key' => $validationError ?? 'Selected ONT was not found in the directory.',
            ])->withInput();
        }

        $payload = [
            'hostel_name' => (string) $candidate['hostel_name'],
            'no_of_routers' => $this->resolveRoutersInput($data, (int) ($hostel->no_of_routers ?? 0)),
        ];
        if ($this->hostelOntColumnsAvailable()) {
            $payload['ont_site_id'] = (string) ($candidate['site_id'] ?? '') !== '' ? (string) $candidate['site_id'] : ($hostel->ont_site_id ?? null);
            $payload['ont_site_sn'] = (string) ($candidate['site_sn'] ?? '') !== '' ? (string) $candidate['site_sn'] : ($hostel->ont_site_sn ?? null);
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = true;
        }
        if ($this->hostelChainedColumnsAvailable()) {
            $payload['chained_from_hostel_id'] = null;
        }

        $hostel->update($payload);

        return redirect()
            ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id, 'modal' => 'hostel-merge'])
            ->with('success', 'Hostel name merged from ONT directory.');
    }

    public function refreshHostelOntSn(Hostel $hostel)
    {
        if (!$this->hostelOntColumnsAvailable()) {
            return back()->with('error', 'Site S.N columns are not available yet. Run the latest migrations first.');
        }

        if (!$this->hostelOntMergedColumnAvailable() || !(bool) ($hostel->ont_merged ?? false)) {
            return back()->with('error', 'Only merged hostels can refresh Site S.N.');
        }

        $ontKey = '';
        $siteId = trim((string) ($hostel->ont_site_id ?? ''));
        if ($siteId !== '') {
            $ontKey = str_starts_with($siteId, 'site:') ? $siteId : ('site:' . $siteId);
        }

        [$candidate, $validationError] = $this->resolveOntCandidate((string) $hostel->hostel_name, $ontKey);
        if ($candidate === null) {
            return back()->with('error', $validationError ?? 'Matching ONT/site could not be found.');
        }

        $payload = [];
        $candidateName = trim((string) ($candidate['hostel_name'] ?? ''));
        $candidateSiteId = trim((string) ($candidate['site_id'] ?? ''));
        $candidateSiteSn = trim((string) ($candidate['site_sn'] ?? ''));

        if ($candidateName !== '') {
            $payload['hostel_name'] = $candidateName;
        }
        if ($candidateSiteId !== '') {
            $payload['ont_site_id'] = $candidateSiteId;
        }
        if ($candidateSiteSn !== '') {
            $payload['ont_site_sn'] = $candidateSiteSn;
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = true;
        }

        if (!empty($payload)) {
            $hostel->update($payload);
        }

        if ($candidateSiteSn === '') {
            return back()->with('success', 'ONT matched, but no Site S.N was returned.');
        }

        return back()->with('success', 'Site S.N refreshed from ONT directory.');
    }

    public function showHostel(Hostel $hostel)
    {
        $rootHostel = $this->resolveAgreementRootHostel($hostel);
        if ((int) $rootHostel->id !== (int) $hostel->id) {
            return redirect()
                ->route('petty.tokens.hostels.show', [
                    'hostel' => $rootHostel->id,
                    'focus_child' => $hostel->id,
                ])
                ->with('warning', 'Opened in the main hostel view because this hostel is under a merged agreement.');
        }

        $familyHostels = $this->familyHostelsForRoot($rootHostel);
        $familyIds = $familyHostels->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $familyChildren = $familyHostels->filter(fn (Hostel $familyHostel) => (int) $familyHostel->id !== (int) $rootHostel->id)->values();
        $focusChildId = (int) request()->query('focus_child', 0);
        $showFocusedChildLedger = request()->boolean('child_ledger') && $focusChildId > 0;
        $supportsOverpay = $this->paymentOverpayColumnsAvailable();
        $paymentQuery = Payment::query()->with(['batch', 'hostel']);
        if ($supportsOverpay) {
            $paymentQuery->with('overpaySource');
        }

        if ($showFocusedChildLedger && in_array($focusChildId, $familyIds, true)) {
            $paymentQuery->where('hostel_id', $focusChildId);
        } else {
            $showFocusedChildLedger = false;
        }

        $payments = $paymentQuery
            ->when(!$showFocusedChildLedger, function ($query) use ($familyIds) {
                $query->whereIn('hostel_id', $familyIds);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $paymentsByBatch = $payments->groupBy('batch_id');
        $hasTransactions = $payments->isNotEmpty();

        $last = $payments->first();
        $lastPayment = $last ? [
            'amount' => (float) $last->amount,
            'date' => $last->date?->format('Y-m-d'),
        ] : null;

        $allocator = app(FundsAllocatorService::class);
        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $today = Carbon::today();
        $lastDate = $last?->date ? Carbon::parse($last->date)->startOfDay() : null;

        $nextDue = null;
        $daysToDue = null;
        $dueBadge = 'No payments yet';
        $dueStatus = 'unknown';

        if ($lastDate) {
            if (($rootHostel->stake ?: 'monthly') === 'semester') {
                $nextDue = $lastDate->copy()->addMonthsNoOverflow(4)->startOfDay();
            } else {
                $nextDue = $lastDate->copy()->addMonthNoOverflow()->startOfDay();
            }

                $daysToDue = $today->diffInDays($nextDue, false);

            if ($daysToDue === 3) { $dueBadge = 'Due in 3 days'; $dueStatus = 'upcoming'; }
            elseif ($daysToDue === 2) { $dueBadge = 'Due in 2 days'; $dueStatus = 'upcoming'; }
            elseif ($daysToDue === 1) { $dueBadge = 'Due tomorrow'; $dueStatus = 'upcoming'; }
            elseif ($daysToDue === 0) { $dueBadge = 'Due today'; $dueStatus = 'due_today'; }
            elseif ($daysToDue < 0) { $dueBadge = 'Overdue by ' . abs($daysToDue) . ' day' . (abs($daysToDue) === 1 ? '' : 's'); $dueStatus = 'overdue'; }
                else { $dueBadge = 'Due in ' . $daysToDue . ' days'; $dueStatus = 'upcoming'; }
        }

        $qrImageUrl = null;
        if (!empty($rootHostel->qr_image_path) && Storage::disk('public')->exists((string) $rootHostel->qr_image_path)) {
            $qrImageUrl = Storage::disk('public')->url((string) $rootHostel->qr_image_path);
        }

        $terminationSupported = $this->hostelAgreementTerminationColumnsAvailable();
        $agreementTerminated = $terminationSupported && !empty($rootHostel->agreement_terminated_at);
        if ($agreementTerminated) {
            $nextDue = null;
            $daysToDue = null;
            $dueBadge = 'Agreement terminated';
            $dueStatus = 'terminated';
        }

        $ontCatalog = $this->ontCatalogForUi((string) $rootHostel->hostel_name, 30);
        $selectedOnt = $this->findOntCandidateForHostel($ontCatalog, (string) $rootHostel->hostel_name);
        $selectedOntKey = (string) ($selectedOnt['key'] ?? '');
        $agreementType = $this->normalizeAgreementType((string) ($rootHostel->agreement_type ?? 'none'));
        $agreementTypeLabel = $this->agreementTypeLabel($agreementType);
        $chainedFromHostel = null;
        if ($this->hostelChainedColumnsAvailable() && !empty($rootHostel->chained_from_hostel_id)) {
            $chainedFromHostel = Hostel::query()
                ->whereKey((int) $rootHostel->chained_from_hostel_id)
                ->first(['id', 'hostel_name', 'ont_site_id', 'ont_site_sn']);
        }
        $transferTargets = collect();
        $transferHostel = null;
        if ($terminationSupported) {
            $transferTargets = Hostel::query()
                ->when($this->hostelAgreementFamilyColumnsAvailable(), function ($query) {
                    $query->whereNull('agreement_parent_hostel_id');
                })
                ->whereNotIn('id', $familyIds)
                ->orderBy('hostel_name')
                ->get(['id', 'hostel_name', 'meter_no', 'phone_no']);
            if (!empty($rootHostel->agreement_transfer_hostel_id)) {
                $transferHostel = $transferTargets->firstWhere('id', (int) $rootHostel->agreement_transfer_hostel_id);
            }
            if ($transferHostel === null && !empty($rootHostel->agreement_transfer_hostel_id)) {
                $transferHostel = Hostel::query()
                    ->whereKey((int) $rootHostel->agreement_transfer_hostel_id)
                    ->first(['id', 'hostel_name', 'meter_no', 'phone_no']);
            }
        }
        $overpayCandidates = collect();
        if ($supportsOverpay && $agreementType !== 'package') {
            $sourcePool = $payments
                ->where('hostel_id', $rootHostel->id)
                ->filter(fn (Payment $payment) => !(bool) ($payment->is_overpay_application ?? false))
                ->sortBy([
                    ['date', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();

            $eligibleSourceIds = collect();
            $cycleMonths = $this->cycleMonthsForHostel($rootHostel);
            $previousDate = null;

            foreach ($sourcePool as $sourcePayment) {
                $sourceDate = $sourcePayment->date ? Carbon::parse($sourcePayment->date)->startOfDay() : null;
                if ($sourceDate === null) {
                    continue;
                }

                if ($previousDate !== null) {
                    $expectedDue = $previousDate->copy()->addMonthsNoOverflow($cycleMonths)->startOfDay();
                    if ($sourceDate->lt($expectedDue)) {
                        $eligibleSourceIds->push((int) $sourcePayment->id);
                    }
                }

                $previousDate = $sourceDate;
            }

            $usedSourceIds = Payment::query()
                ->where('hostel_id', $rootHostel->id)
                ->where('is_overpay_application', true)
                ->whereNotNull('overpay_source_payment_id')
                ->pluck('overpay_source_payment_id')
                ->map(fn ($id) => (int) $id)
                ->unique();

            $overpayCandidates = $payments
                ->where('hostel_id', $rootHostel->id)
                ->filter(fn (Payment $payment) => !(bool) ($payment->is_overpay_application ?? false))
                ->filter(fn (Payment $payment) => $eligibleSourceIds->contains((int) $payment->id))
                ->reject(fn (Payment $payment) => $usedSourceIds->contains((int) $payment->id))
                ->values();
        }

        $supportsPendingCredits = $this->hostelPendingCreditsAvailable();
        $pendingCredits = collect();
        $helperGatewayDevice = $this->activeGatewayDevice();
        $this->refreshHelperSmsParsing($rootHostel, $familyIds);
        $helperPaymentCandidates = $this->helperPaymentCandidates($rootHostel, $familyIds);

        if ($supportsPendingCredits) {
            $pendingCredits = HostelPendingCredit::query()
                ->with(['payment', 'hostel'])
                ->whereIn('hostel_id', $familyIds)
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->get();
        }

        $familyChildSummaries = collect($this->attachHostelPaymentSummaries($familyChildren));
        $familyRouterTotal = $this->totalRoutersForHostelIds($familyIds);
        $familyChildRouterTotal = max(0, $familyRouterTotal - (int) ($rootHostel->no_of_routers ?? 0));
        $focusedChildSummary = $familyChildSummaries->firstWhere('id', $focusChildId);

        return view('pettycash::spendings.tokens.hostel_show', [
            'hostel' => $rootHostel,
            'rootHostel' => $rootHostel,
            'familyHostels' => $familyHostels,
            'familyChildren' => $familyChildren,
            'familyChildSummaries' => $familyChildSummaries,
            'familyRouterTotal' => $familyRouterTotal,
            'familyChildRouterTotal' => $familyChildRouterTotal,
            'focusChildId' => $focusChildId,
            'showFocusedChildLedger' => $showFocusedChildLedger,
            'focusedChildSummary' => $focusedChildSummary,
            'paymentsByBatch' => $paymentsByBatch,
            'hasTransactions' => $hasTransactions,
            'lastPayment' => $lastPayment,
            'batches' => $batches,
            'totalBalance' => $totalBalance,
            'nextDue' => $nextDue,
            'daysToDue' => $daysToDue,
            'dueBadge' => $dueBadge,
            'dueStatus' => $dueStatus,
            'today' => $today,
            'ontCatalog' => $ontCatalog,
            'selectedOntKey' => $selectedOntKey,
            'agreementType' => $agreementType,
            'agreementTypeLabel' => $agreementTypeLabel,
            'qrImageUrl' => $qrImageUrl,
            'qrType' => $rootHostel->qr_type,
            'qrTarget' => $rootHostel->qr_target,
            'qrReference' => $rootHostel->qr_reference,
            'qrAmount' => (float) ($rootHostel->qr_amount ?? 0),
            'qrPayload' => $rootHostel->qr_payload,
            'qrGeneratedAt' => $rootHostel->qr_generated_at,
            'chainedFromHostel' => $chainedFromHostel,
            'terminationSupported' => $terminationSupported,
            'agreementTerminated' => $agreementTerminated,
            'transferTargets' => $transferTargets,
            'transferHostel' => $transferHostel,
            'supportsOverpay' => $supportsOverpay,
            'overpayCandidates' => $overpayCandidates,
            'supportsPendingCredits' => $supportsPendingCredits,
            'pendingCredits' => $pendingCredits,
            'helperGatewayDevice' => $helperGatewayDevice,
            'helperPaymentCandidates' => $helperPaymentCandidates,
        ]);
    }

    public function syncHelperPayments(Hostel $hostel, GatewayOutboxService $outbox)
    {
        $hostel = $this->resolveAgreementRootHostel($hostel);
        $gatewayDevice = $this->activeGatewayDevice();

        if (!$gatewayDevice) {
            return redirect()
                ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id, 'modal' => 'payment-record', 'payment_entry' => 'helper'])
                ->with('error', 'No active gateway helper is selected. Set an active gateway first, then fetch helper payments again.');
        }

        $outbox->queueSmsSync($gatewayDevice, $hostel);

        return redirect()
            ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id, 'modal' => 'payment-record', 'payment_entry' => 'helper'])
            ->with('success', 'Helper sync requested. The gateway phone will scan inbox SMS and upload missing M-PESA messages automatically.');
    }

    public function generateHostelQr(Request $request, Hostel $hostel, MpesaQrService $qrService)
    {
        abort_unless(PettyAccess::allows(auth('petty')->user(), 'tokens.edit_hostel'), 403);

        try {
            $refresh = $request->boolean('refresh');
            $details = $qrService->generateForHostel($hostel, $refresh);
            $hostel->update([
                'qr_type' => $details['qr_type'],
                'qr_target' => $details['qr_target'],
                'qr_reference' => $details['qr_reference'],
                'qr_payload' => $details['qr_payload'],
                'qr_amount' => $details['qr_amount'],
                'qr_image_path' => $details['qr_image_path'],
                'qr_generated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Unable to generate M-PESA QR code: ' . $exception->getMessage());
        }

        return back()->with('success', 'M-PESA QR code generated successfully.');
    }

    public function downloadHostelQr(Hostel $hostel)
    {
        abort_unless(PettyAccess::allows(auth('petty')->user(), 'tokens.edit_hostel'), 403);

        if (empty($hostel->qr_image_path) || !Storage::disk('public')->exists((string) $hostel->qr_image_path)) {
            return back()->with('error', 'No generated QR code is available for download.');
        }

        return Storage::disk('public')->download((string) $hostel->qr_image_path, 'hostel-' . (int) $hostel->id . '-mpesa-qr.png');
    }

    public function detachChildHostel(Hostel $hostel, Hostel $child)
    {
        $rootHostel = $this->resolveAgreementRootHostel($hostel);
        $user = auth('petty')->user();
        $canManage = PettyAccess::allows($user, 'tokens.create_hostel')
            || PettyAccess::allows($user, 'tokens.edit_hostel');
        abort_unless($canManage, 403);

        if (!$this->hostelAgreementFamilyColumnsAvailable()) {
            return back()->with('error', 'Child hostel detach is not available until the latest migrations are run.');
        }

        $childRecord = Hostel::query()
            ->whereKey((int) $child->id)
            ->where('agreement_parent_hostel_id', (int) $rootHostel->id)
            ->first();

        if (!$childRecord) {
            abort(404);
        }

        $childRecord->update([
            'agreement_parent_hostel_id' => null,
        ]);

        return redirect()
            ->route('petty.tokens.hostels.show', ['hostel' => $rootHostel->id])
            ->with('success', $childRecord->hostel_name . ' detached from ' . $rootHostel->hostel_name . '.');
    }

    public function applyOverpay(Hostel $hostel, Request $request)
    {
        $hostel = $this->resolveAgreementRootHostel($hostel);
        if (!$this->paymentOverpayColumnsAvailable()) {
            return back()->with('error', 'Overpay action is unavailable. Run latest migrations first.');
        }

        $agreementType = $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none'));
        if ($agreementType === 'package') {
            return back()->with('error', 'Overpay action is not available for package agreements.');
        }

        $data = $request->validate([
            'source_payment_id' => ['required', 'integer', 'exists:petty_payments,id'],
            'marked_date' => ['required', 'date'],
            'date_mode' => ['nullable', 'in:from_marked_date,next_billing_date'],
            'next_billing_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            PettyDatabase::transaction(function () use ($hostel, $data) {
                /** @var Payment|null $source */
                $source = Payment::query()
                    ->whereKey((int) $data['source_payment_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$source || (int) $source->hostel_id !== (int) $hostel->id) {
                    throw ValidationException::withMessages([
                        'source_payment_id' => 'Select a valid source payment for this hostel.',
                    ]);
                }

                if ((bool) ($source->is_overpay_application ?? false)) {
                    throw ValidationException::withMessages([
                        'source_payment_id' => 'You cannot use an overpay-adjusted row as source.',
                    ]);
                }

                $alreadyUsed = Payment::query()
                    ->where('hostel_id', $hostel->id)
                    ->where('is_overpay_application', true)
                    ->where('overpay_source_payment_id', $source->id)
                    ->exists();

                if ($alreadyUsed) {
                    throw ValidationException::withMessages([
                        'source_payment_id' => 'This payment has already been used for an overpay adjustment.',
                    ]);
                }

                if (!$this->isSourcePaymentOverpayEligible($hostel, $source)) {
                    throw ValidationException::withMessages([
                        'source_payment_id' => 'Selected payment is not an overpay source (time lapse was already met).',
                    ]);
                }

                $markedDate = Carbon::parse((string) $data['marked_date'])->startOfDay();
                $dateMode = strtolower(trim((string) ($data['date_mode'] ?? 'from_marked_date')));
                if (!in_array($dateMode, ['from_marked_date', 'next_billing_date'], true)) {
                    $dateMode = 'from_marked_date';
                }

                $applyDate = $markedDate->format('Y-m-d');
                $nextBillingDateLabel = null;
                if ($dateMode === 'next_billing_date') {
                    $nextBillingRaw = trim((string) ($data['next_billing_date'] ?? ''));
                    if ($nextBillingRaw === '') {
                        throw ValidationException::withMessages([
                            'next_billing_date' => 'Next billing date is required when that mode is selected.',
                        ]);
                    }

                    $nextBillingDate = Carbon::parse($nextBillingRaw)->startOfDay();
                    if ($nextBillingDate->lt($markedDate)) {
                        throw ValidationException::withMessages([
                            'next_billing_date' => 'Next billing date cannot be before the marked date.',
                        ]);
                    }

                    $applyDate = $nextBillingDate
                        ->copy()
                        ->subMonthsNoOverflow($this->cycleMonthsForHostel($hostel))
                        ->format('Y-m-d');
                    $nextBillingDateLabel = $nextBillingDate->format('Y-m-d');
                }

                $sourceDate = $source->date?->format('Y-m-d');
                $sourceRef = trim((string) ($source->reference ?? ''));
                $sourceRefSanitized = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($sourceRef)) ?? '';
                $referenceBase = $sourceRefSanitized !== ''
                    ? ('OVERPAY-' . $sourceRefSanitized)
                    : ('OVERPAY-P' . $source->id);
                $reference = $referenceBase . '-' . Carbon::parse($applyDate)->format('Ymd');
                if (strlen($reference) > 255) {
                    $reference = substr($reference, 0, 255);
                }

                $auditNote = 'Overpay applied from payment #' . $source->id;
                if ($sourceDate !== null) {
                    $auditNote .= ' dated ' . $sourceDate;
                }
                if ($sourceRef !== '') {
                    $auditNote .= ' (Ref: ' . $sourceRef . ')';
                }
                if ($dateMode === 'next_billing_date' && $nextBillingDateLabel !== null) {
                    $auditNote .= ' | Next billing date: ' . $nextBillingDateLabel;
                } else {
                    $auditNote .= ' | Marked date: ' . $markedDate->format('Y-m-d');
                }
                $userNote = trim((string) ($data['notes'] ?? ''));
                $fullNote = $userNote !== '' ? ($auditNote . ' | ' . $userNote) : $auditNote;
                if (strlen($fullNote) > 255) {
                    $fullNote = substr($fullNote, 0, 255);
                }

                $agreementReceiverName = trim((string) ($hostel->contact_person ?? ''));
                $agreementReceiverPhone = trim((string) ($hostel->phone_no ?? ''));

                Payment::query()->create([
                    'hostel_id' => $hostel->id,
                    'spending_id' => null,
                    'batch_id' => null,
                    'is_overpay_application' => true,
                    'overpay_source_payment_id' => $source->id,
                    'reference' => $reference,
                    'amount' => (float) ($source->amount ?? 0),
                    'transaction_cost' => 0,
                    'date' => $applyDate,
                    'receiver_name' => $agreementReceiverName !== '' ? $agreementReceiverName : ($source->receiver_name ?: null),
                    'receiver_phone' => $agreementReceiverPhone !== '' ? $agreementReceiverPhone : ($source->receiver_phone ?: null),
                    'notes' => $fullNote,
                    'recorded_by' => auth('petty')->id(),
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors([
                'source_payment_id' => $e->getMessage(),
            ])->withInput();
        }

        return redirect()
            ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id])
            ->with('success', 'Overpay marked from existing payment. No petty balance deducted.');
    }

    public function storePendingCredit(Hostel $hostel, Request $request)
    {
        $hostel = $this->resolveAgreementRootHostel($hostel);
        if (!$this->hostelPendingCreditsAvailable()) {
            return back()->with('error', 'Pending credits table is not available. Run latest migrations first.');
        }

        $agreementType = $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none'));
        if ($agreementType !== 'package') {
            return back()->with('error', 'Pending credits are only available for package agreements.');
        }

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = array_key_exists('amount', $data) && $data['amount'] !== null
            ? (float) $data['amount']
            : (float) ($hostel->amount_due ?? 0);

        if ($amount <= 0) {
            return back()->withErrors([
                'amount' => 'Amount must be greater than zero.',
            ])->withInput();
        }

        HostelPendingCredit::query()->create([
            'hostel_id' => $hostel->id,
            'amount' => $amount,
            'reference' => trim((string) ($data['reference'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'status' => 'pending',
            'created_by' => auth('petty')->id(),
        ]);

        return redirect()
            ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id])
            ->with('success', 'Pending credit generated.');
    }

    public function sortPendingCredit(Hostel $hostel, HostelPendingCredit $credit)
    {
        $hostel = $this->resolveAgreementRootHostel($hostel);
        if (!$this->hostelPendingCreditsAvailable()) {
            return back()->with('error', 'Pending credits table is not available. Run latest migrations first.');
        }

        $familyIds = $this->familyHostelIdsForRoot($hostel);
        if (!in_array((int) $credit->hostel_id, $familyIds, true)) {
            abort(404);
        }

        $agreementType = $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none'));
        if ($agreementType !== 'package') {
            return back()->with('error', 'Pending credits are only available for package agreements.');
        }

        if (strtolower((string) $credit->status) === 'sorted') {
            return back()->with('success', 'Pending credit already marked as sorted.');
        }

        PettyDatabase::transaction(function () use ($hostel, $credit) {
            /** @var HostelPendingCredit|null $row */
            $row = HostelPendingCredit::query()
                ->whereKey($credit->id)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                return;
            }

            if (strtolower((string) $row->status) === 'sorted') {
                return;
            }

            $reference = trim((string) ($row->reference ?? ''));
            if ($reference === '') {
                $reference = 'PKG-SORT-' . $row->id . '-' . now()->format('YmdHis');
            }

            $notes = trim((string) ($row->notes ?? ''));
            $auditNote = 'Sorted package pending credit #' . $row->id;
            $fullNotes = $notes !== '' ? ($auditNote . ' | ' . $notes) : $auditNote;
            $paymentHostelId = (int) ($row->hostel_id ?? $hostel->id);
            $paymentHostel = Hostel::query()->find($paymentHostelId);

            $payment = Payment::query()->create([
                'hostel_id' => $paymentHostelId,
                'batch_id' => null,
                'reference' => $reference,
                'amount' => (float) $row->amount,
                'transaction_cost' => 0,
                'date' => now()->toDateString(),
                'receiver_name' => 'Package Credit',
                'receiver_phone' => trim((string) ($paymentHostel?->phone_no ?? $hostel->phone_no ?? '')) ?: null,
                'notes' => $fullNotes,
                'recorded_by' => auth('petty')->id(),
            ]);

            $row->update([
                'status' => 'sorted',
                'payment_id' => $payment->id,
                'sorted_by' => auth('petty')->id(),
                'sorted_at' => now(),
            ]);
        });

        return redirect()
            ->route('petty.tokens.hostels.show', ['hostel' => $hostel->id])
            ->with('success', 'Pending credit marked as sorted and posted to transaction history.');
    }

    public function storePayment(Hostel $hostel, Request $request)
    {
        $hostel = $this->resolveAgreementRootHostel($hostel);
        $familyIds = $this->familyHostelIdsForRoot($hostel);
        $supportsSpendingLink = $this->paymentSupportsSpendingLink();
        $agreementType = $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none'));
        $isPackageAgreement = $agreementType === 'package';
        $isTokenAgreement = $agreementType === 'token';
        $isSendMoneyAgreement = $agreementType === 'send_money';
        $agreementText = $this->agreementTypeLabel($agreementType);

        $data = $request->validate([
            'funding' => ['nullable', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:255'],
            'coverage_value' => ['required', 'integer', 'min:1', 'max:365'],
            'coverage_unit' => ['required', 'in:day,week,month'],

            'meter_no' => ['nullable', 'string', 'max:64'],
            'selected_sms_log_id' => ['nullable', 'integer'],
        ]);

        $selectedSmsLog = null;
        $effectiveAgreementType = $agreementType;
        $selectedSmsLogId = (int) ($data['selected_sms_log_id'] ?? 0);
        if ($selectedSmsLogId > 0) {
            $selectedSmsLog = PettyTokenSmsLog::query()->find($selectedSmsLogId);
            if (!$selectedSmsLog || $selectedSmsLog->sms_kind !== 'mpesa') {
                throw ValidationException::withMessages([
                    'selected_sms_log_id' => 'The selected helper payment message is no longer available.',
                ]);
            }

            if (!empty($selectedSmsLog->parsed_reference) && Payment::query()->where('reference', $selectedSmsLog->parsed_reference)->exists()) {
                throw ValidationException::withMessages([
                    'selected_sms_log_id' => 'That M-PESA transaction code is already recorded in the ledger.',
                ]);
            }

            $candidateIds = $this->helperPaymentCandidates($hostel, $familyIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!in_array((int) $selectedSmsLog->id, $candidateIds, true)) {
                throw ValidationException::withMessages([
                    'selected_sms_log_id' => 'The selected helper message does not match this hostel payment context closely enough.',
                ]);
            }

            $effectiveAgreementType = $this->normalizeAgreementType((string) ($selectedSmsLog->parsed_payment_type ?? ''));
            if ($effectiveAgreementType === 'none') {
                $selectedTarget = trim((string) ($selectedSmsLog->parsed_meter_number ?? ''));
                $selectedTargetDigits = preg_replace('/\D+/', '', $selectedTarget) ?? '';
                if ($selectedTarget !== '' && strlen($selectedTargetDigits) < 10) {
                    $effectiveAgreementType = 'token';
                } elseif ($selectedTarget !== '') {
                    $effectiveAgreementType = 'send_money';
                } else {
                    $effectiveAgreementType = $agreementType;
                }
            }

            if (!empty($selectedSmsLog->parsed_reference)) {
                $data['reference'] = (string) $selectedSmsLog->parsed_reference;
            }
            if ($selectedSmsLog->parsed_amount !== null) {
                $data['amount'] = (float) $selectedSmsLog->parsed_amount;
            }
            if ($selectedSmsLog->parsed_transaction_cost !== null) {
                $data['transaction_cost'] = (float) $selectedSmsLog->parsed_transaction_cost;
            }
            if (!empty($selectedSmsLog->parsed_meter_number)) {
                $data['meter_no'] = (string) $selectedSmsLog->parsed_meter_number;
            }
            if ($selectedSmsLog->sms_received_at) {
                $data['date'] = $selectedSmsLog->sms_received_at->format('Y-m-d');
            }
            if (trim((string) ($data['receiver_phone'] ?? '')) === '') {
                $selectedTarget = trim((string) ($selectedSmsLog->parsed_meter_number ?? ''));
                $selectedTargetDigits = preg_replace('/\D+/', '', $selectedTarget) ?? '';
                $hostelPhone = trim((string) ($hostel->phone_no ?? ''));
                $data['receiver_phone'] = $effectiveAgreementType === 'send_money' && $selectedTargetDigits !== ''
                    ? $selectedTarget
                    : ($hostelPhone !== '' ? $hostelPhone : null);
            }
            if (trim((string) ($data['receiver_name'] ?? '')) === '' && $effectiveAgreementType === 'send_money') {
                $data['receiver_name'] = $this->extractSendMoneyRecipientName((string) ($selectedSmsLog->sms_body ?? ''))
                    ?: (trim((string) ($hostel->contact_person ?? '')) ?: null);
            }

            $data['notes'] = trim(implode(' | ', array_filter([
                (string) ($data['notes'] ?? ''),
                'Imported from helper SMS',
            ], fn ($value) => trim((string) $value) !== '')));
        }

        $data['notes'] = $this->appendCoverageToNotes(
            (string) $data['notes'],
            (int) $data['coverage_value'],
            (string) $data['coverage_unit']
        );

        $funding = strtolower(trim((string) ($data['funding'] ?? '')));
        if (!$isPackageAgreement && !in_array($funding, ['auto', 'single'], true)) {
            throw ValidationException::withMessages([
                'funding' => 'Funding mode is required.',
            ]);
        }

        if (!$isPackageAgreement && $funding === 'single' && empty($data['batch_id'])) {
            throw ValidationException::withMessages([
                'batch_id' => 'Batch is required in Single Batch mode.',
            ]);
        }

        $fee = (float) ($data['transaction_cost'] ?? 0);
        $amount = (float) $data['amount'];

        $meterNo = trim((string) $data['meter_no']);
        $recordingIsPackage = $effectiveAgreementType === 'package';
        $recordingIsToken = $effectiveAgreementType === 'token';
        $recordingIsSendMoney = $effectiveAgreementType === 'send_money';
        $effectiveAgreementText = $this->agreementTypeLabel($effectiveAgreementType);

        if ($recordingIsToken && $meterNo === '') {
            throw ValidationException::withMessages([
                'meter_no' => 'Meter number is required.',
            ]);
        }

        $receiverPhone = trim((string) ($data['receiver_phone'] ?? ''));
        if (!$recordingIsPackage && $receiverPhone === '') {
            throw ValidationException::withMessages([
                'receiver_phone' => 'Receiver phone is required.',
            ]);
        }

        $receiverName = trim((string) ($data['receiver_name'] ?? ''));
        if ($recordingIsSendMoney && $receiverName === '') {
            throw ValidationException::withMessages([
                'receiver_name' => 'Recipient name is required for send money entries.',
            ]);
        }

        $allocator = app(FundsAllocatorService::class);

        if (!$isPackageAgreement && $funding === 'auto') {
            $required = $amount + $fee;
            $available = (float) $allocator->totalNetBalance();
            if ($required > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient TOTAL balance. Needed: ' . number_format($required, 2) . ' Available: ' . number_format($available, 2),
                ]);
            }
        }

        try {
            PettyDatabase::transaction(function () use ($hostel, $data, $funding, $fee, $amount, $allocator, $meterNo, $receiverPhone, $supportsSpendingLink, $effectiveAgreementType, $effectiveAgreementText, $recordingIsPackage) {
                if ($meterNo !== '' && trim((string) $hostel->meter_no) !== $meterNo) {
                    $hostel->meter_no = $meterNo;
                    $hostel->save();
                }

                if ($recordingIsPackage) {
                    Payment::create([
                        'hostel_id' => $hostel->id,
                        'batch_id' => null,
                        'reference' => $data['reference'],
                        'amount' => $amount,
                        'transaction_cost' => $fee,
                        'date' => $data['date'],
                        'receiver_name' => trim((string) ($data['receiver_name'] ?? '')) ?: null,
                        'receiver_phone' => ($receiverPhone !== '' ? $receiverPhone : null),
                        'notes' => $data['notes'] ?? null,
                        'recorded_by' => auth('petty')->id(),
                    ]);

                    return;
                }

                $descriptionPrefix = match ($effectiveAgreementType) {
                    'send_money' => 'Send money payment',
                    default => 'Token payment',
                };

                // Create spending first (affects balances)
                $spending = Spending::create([
                    'batch_id' => null,
                    'type' => 'token',
                    'sub_type' => 'hostel',
                    'reference' => $data['reference'],

                    // store meter number snapshot on the spending row
                    'meter_no' => ($meterNo !== '' ? $meterNo : null),

                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'description' => $descriptionPrefix . ' (' . $effectiveAgreementText . '): ' . $hostel->hostel_name,
                    'related_id' => $hostel->id,
                ]);

                $onlyBatch = ($funding === 'single') ? (int) $data['batch_id'] : null;
                $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);

                // primary batch for display/payment row
                $primaryBatchId = $spending->batch_id;

                // Payment ledger (one row, primary batch)
                $paymentPayload = [
                    'hostel_id' => $hostel->id,
                    'batch_id' => $primaryBatchId,
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'receiver_name' => trim((string) ($data['receiver_name'] ?? '')) ?: null,
                    'receiver_phone' => ($receiverPhone !== '' ? $receiverPhone : null),
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth('petty')->id(),
                ];
                if ($supportsSpendingLink) {
                    $paymentPayload['spending_id'] = $spending->id;
                }

                Payment::create($paymentPayload);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'amount' => $e->getMessage(),
            ]);
        }

        $message = match (true) {
            $isPackageAgreement => 'Package credit recorded. No petty balance was deducted.',
            $funding === 'single' => 'Payment recorded to the selected batch.',
            default => 'Payment recorded with auto allocation.',
        };

        return $this->successResponse(
            $request,
            $message,
            route('petty.tokens.hostels.show', $hostel->id)
        );
    }

    public function editPayment(Payment $payment)
    {
        $hostel = Hostel::query()->findOrFail((int) $payment->hostel_id);
        $allocator = app(FundsAllocatorService::class);
        $coverage = $this->extractCoverageFromNotes((string) ($payment->notes ?? ''));

        return view('pettycash::spendings.tokens.payment_edit', [
            'payment' => $payment,
            'hostel' => $hostel,
            'batches' => $allocator->batchesWithNetAvailable(),
            'coverage' => $coverage,
        ]);
    }

    public function updatePayment(Payment $payment, Request $request)
    {
        $hostel = Hostel::query()->findOrFail((int) $payment->hostel_id);
        $agreementType = $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none'));
        $isTokenAgreement = $agreementType === 'token';

        $data = $request->validate([
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],
            'reference' => ['required', 'string', 'max:255'],
            'meter_no' => ['nullable', 'string', 'max:64'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['required', 'numeric', 'min:0'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:255'],
            'coverage_value' => ['required', 'integer', 'min:1', 'max:365'],
            'coverage_unit' => ['required', 'in:day,week,month'],
        ]);

        $meterNo = trim((string) $data['meter_no']);
        if ($isTokenAgreement && $meterNo === '') {
            return back()->withErrors(['meter_no' => 'Meter number is required.'])->withInput();
        }

        $receiverPhone = trim((string) ($data['receiver_phone'] ?? ''));
        if ($receiverPhone === '' && $agreementType !== 'package') {
            return back()->withErrors(['receiver_phone' => 'Phone number is required.'])->withInput();
        }

        $receiverName = trim((string) ($data['receiver_name'] ?? ''));
        if ($agreementType === 'send_money' && $receiverName === '') {
            return back()->withErrors(['receiver_name' => 'Name is required for Send Money entries.'])->withInput();
        }

        if ($payment->spending_id && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required for balance-deducting payments.'])->withInput();
        }

        $amount = round((float) $data['amount'], 2);
        $fee = round((float) $data['transaction_cost'], 2);
        $notes = $this->appendCoverageToNotes(
            (string) $data['notes'],
            (int) $data['coverage_value'],
            (string) $data['coverage_unit']
        );
        $allocator = app(FundsAllocatorService::class);

        PettyDatabase::transaction(function () use ($payment, $data, $meterNo, $receiverPhone, $amount, $fee, $notes, $allocator) {
            $hostel = Hostel::query()->lockForUpdate()->find((int) $payment->hostel_id);
            if ($hostel && $meterNo !== '' && trim((string) $hostel->meter_no) !== $meterNo) {
                $hostel->meter_no = $meterNo;
                $hostel->save();
            }

            $payment->update([
                'batch_id' => !empty($data['batch_id']) ? (int) $data['batch_id'] : null,
                'reference' => $data['reference'],
                'amount' => $amount,
                'transaction_cost' => $fee,
                'date' => $data['date'],
                'receiver_name' => $data['receiver_name'] ?? null,
                'receiver_phone' => ($receiverPhone !== '' ? $receiverPhone : null),
                'notes' => $notes,
            ]);

            if ($payment->spending_id) {
                $spending = Spending::query()->lockForUpdate()->find($payment->spending_id);
                if ($spending) {
                    $spending->update([
                        'reference' => $data['reference'],
                        'amount' => $amount,
                        'transaction_cost' => $fee,
                        'meter_no' => $meterNo !== '' ? $meterNo : null,
                        'date' => $data['date'],
                    ]);

                    $allocator->forceAllocateToBatch($spending, $amount, $fee, (int) $data['batch_id']);
                }
            }
        });

        return redirect()
            ->route('petty.tokens.hostels.show', $payment->hostel_id)
            ->with('success', 'Payment details updated.');
    }

    public function destroyPayment(Payment $payment)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);

        if (!$this->paymentSupportsSpendingLink()) {
            return back()->with('error', 'Payment delete needs the spending-link migration first.');
        }

        if (!$payment->spending_id) {
            return back()->with('error', 'Legacy payment records without a linked spending cannot be deleted here.');
        }

        $spending = Spending::query()->find($payment->spending_id);
        if (!$spending) {
            return back()->with('error', 'Linked spending record was not found.');
        }

        $hostelId = (int) $payment->hostel_id;

        PettyDatabase::transaction(function () use ($payment, $spending) {
            \App\Modules\PettyCash\Models\SpendingAllocation::query()
                ->where('spending_id', $spending->id)
                ->delete();
            $payment->delete();
            $spending->delete();
        });

        return redirect()
            ->route('petty.tokens.hostels.show', $hostelId)
            ->with('success', 'Token payment deleted.');
    }

    private function extractCoverageFromNotes(string $notes): array
    {
        if (preg_match('/Coverage:\s*(\d+)\s*(day|days|week|weeks|month|months)/i', $notes, $matches)) {
            $unit = strtolower((string) ($matches[2] ?? 'day'));
            $unit = rtrim($unit, 's');

            return [
                'value' => max(1, (int) ($matches[1] ?? 1)),
                'unit' => in_array($unit, ['day', 'week', 'month'], true) ? $unit : 'day',
            ];
        }

        return [
            'value' => 1,
            'unit' => 'month',
        ];
    }

    private function appendCoverageToNotes(string $notes, int $coverageValue, string $coverageUnit): string
    {
        $cleanNotes = trim(preg_replace('/\s*\|\s*Coverage:\s*\d+\s*(day|days|week|weeks|month|months)\s*/i', '', $notes) ?? $notes);
        $coverageUnit = strtolower(trim($coverageUnit));
        if (!in_array($coverageUnit, ['day', 'week', 'month'], true)) {
            $coverageUnit = 'day';
        }

        $coverageLabel = 'Coverage: ' . $coverageValue . ' ' . $coverageUnit . ($coverageValue === 1 ? '' : 's');
        $combined = trim(implode(' | ', array_filter([$cleanNotes, $coverageLabel], fn ($value) => trim((string) $value) !== '')));

        return substr($combined, 0, 255);
    }

    /**
     * @return array{0:array<string,mixed>|null,1:string|null}
     */
    private function resolveOntCandidate(string $hostelName, string $ontKey = ''): array
    {
        /** @var OntDirectoryService $directory */
        $directory = app(OntDirectoryService::class);
        $match = $directory->findCandidate($ontKey, $hostelName);
        $catalog = (array) ($match['catalog'] ?? []);
        $candidate = $match['candidate'] ?? null;
        $available = (bool) ($catalog['available'] ?? false);

        if (!$available) {
            if ($directory->strictValidationEnabled()) {
                $message = (string) ($catalog['message'] ?? 'ONT directory is unavailable.');
                return [null, $message];
            }

            return [null, null];
        }

        if ($candidate === null) {
            $normalizedHostelName = trim($hostelName);
            $normalizedOntKey = trim($ontKey);

            if ($normalizedHostelName === '' && $normalizedOntKey !== '') {
                $retry = $directory->findCandidate('', $normalizedOntKey);
                $retryCatalog = (array) ($retry['catalog'] ?? []);
                $retryCandidate = $retry['candidate'] ?? null;

                if ((bool) ($retryCatalog['available'] ?? false) && $retryCandidate !== null) {
                    return [$retryCandidate, null];
                }
            }

            if (trim($ontKey) === '') {
                return [null, 'Select ONT/site from the list.'];
            }

            return [null, 'Invalid ONT/site selection.'];
        }

        return [$candidate, null];
    }

    /**
     * @param array<string,mixed> $data
     */
    private function resolveRoutersInput(array $data, int $fallback = 0): int
    {
        if (array_key_exists('no_of_routers', $data) && $data['no_of_routers'] !== null && $data['no_of_routers'] !== '') {
            return max(0, (int) $data['no_of_routers']);
        }

        return max(0, $fallback);
    }

    /**
     * @return array{0:Hostel|null,1:string|null}
     */
    private function resolveChainedFromSelection(int $hostelId, string $ontKey = '', int $excludeHostelId = 0): array
    {
        $existing = $this->resolveChainedFromHostel($hostelId, $excludeHostelId);
        if ($existing) {
            return [$existing, null];
        }

        $ontKey = trim($ontKey);
        if ($ontKey === '') {
            return [null, 'Select the main ONT/site this chained hostel depends on.'];
        }

        [$candidate, $validationError] = $this->resolveOntCandidate('', $ontKey);
        if ($validationError !== null || $candidate === null) {
            return [null, $validationError ?? 'Invalid ONT/site selection.'];
        }

        $mainHostel = $this->findExistingMainHostelForOntCandidate((array) $candidate, $excludeHostelId);
        if ($mainHostel) {
            return [$mainHostel, null];
        }

        $payload = [
            'hostel_name' => trim((string) ($candidate['hostel_name'] ?? '')) ?: 'New Hostel',
            'contact_person' => null,
            'meter_no' => null,
            'phone_no' => null,
            'no_of_routers' => max(0, (int) ($candidate['router_count_suggestion'] ?? 0)),
            'stake' => 'monthly',
            'amount_due' => 0,
        ];

        if ($this->hostelOntColumnsAvailable()) {
            $payload['ont_site_id'] = trim((string) ($candidate['site_id'] ?? '')) ?: null;
            $payload['ont_site_sn'] = trim((string) ($candidate['site_sn'] ?? '')) ?: null;
        }
        if ($this->hostelAgreementColumnsAvailable()) {
            $payload['agreement_type'] = 'none';
            $payload['agreement_label'] = null;
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = true;
        }
        if ($this->hostelChainedColumnsAvailable()) {
            $payload['chained_from_hostel_id'] = null;
        }

        return [Hostel::create($payload), null];
    }

    /**
     * @return array{
     *   available:bool,
     *   message:string,
     *   fetched_at:string|null,
     *   source_count:int,
     *   hostels:array<int,array{
     *      key:string,
     *      hostel_name:string,
     *      site_id:string|null,
     *      router_count_suggestion:int,
     *      merge_status:string,
     *      merge_status_label:string,
     *      merge_status_tone:string
     *   }>
     * }
     */
    private function ontCatalogForUi(string $query = '', int $limit = 30): array
    {
        /** @var OntDirectoryService $directory */
        $directory = app(OntDirectoryService::class);
        $catalog = $directory->searchCatalog($query, $limit);
        $rows = (array) ($catalog['hostels'] ?? []);
        if (empty($rows)) {
            return $catalog;
        }

        $catalog['hostels'] = $this->applyOntMergeStatus($rows);

        return $catalog;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function applyOntMergeStatus(array $rows): array
    {
        $candidateNames = collect($rows)
            ->map(function ($candidate): string {
                $candidate = (array) $candidate;
                return trim((string) ($candidate['hostel_name'] ?? ''));
            })
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();

        if ($candidateNames->isEmpty()) {
            return $rows;
        }

        $hasMergedColumn = PettyDatabase::schema()->hasColumn('petty_hostels', 'ont_merged');
        $statusByName = [];
        $hostelStatusQuery = Hostel::query()
            ->select('hostel_name')
            ->whereIn('hostel_name', $candidateNames->all());
        if ($hasMergedColumn) {
            $hostelStatusQuery->addSelect('ont_merged');
        }

        $hostelStatusQuery
            ->get()
            ->each(function (Hostel $hostel) use (&$statusByName, $hasMergedColumn) {
                $nameKey = $this->normalizeHostelName((string) $hostel->hostel_name);
                if ($nameKey === '') {
                    return;
                }

                $existing = $statusByName[$nameKey] ?? ['exists' => false, 'merged' => false];
                $statusByName[$nameKey] = [
                    'exists' => true,
                    'merged' => (bool) ($existing['merged'] || ($hasMergedColumn ? (bool) ($hostel->ont_merged ?? false) : false)),
                ];
            });

        return collect($rows)->map(function ($candidate) use ($statusByName) {
            $candidate = (array) $candidate;
            $nameKey = $this->normalizeHostelName((string) ($candidate['hostel_name'] ?? ''));
            $statusRow = $statusByName[$nameKey] ?? ['exists' => false, 'merged' => false];

            $status = 'unlinked';
            $label = 'Not Added';
            $tone = 'muted';

            if ((bool) ($statusRow['exists'] ?? false)) {
                if ((bool) ($statusRow['merged'] ?? false)) {
                    $status = 'merged';
                    $label = 'Merged';
                    $tone = 'success';
                } else {
                    $status = 'failed';
                    $label = 'Not Merged';
                    $tone = 'muted';
                }
            }

            $candidate['merge_status'] = $status;
            $candidate['merge_status_label'] = $label;
            $candidate['merge_status_tone'] = $tone;

            return $candidate;
        })->values()->all();
    }

    /**
     * @param array{hostels?:array<int,array<string,mixed>>} $catalog
     * @return array<string,mixed>|null
     */
    private function findOntCandidateForHostel(array $catalog, string $hostelName): ?array
    {
        $target = $this->normalizeHostelName($hostelName);
        if ($target === '') {
            return null;
        }

        foreach ((array) ($catalog['hostels'] ?? []) as $candidate) {
            if ($this->normalizeHostelName((string) ($candidate['hostel_name'] ?? '')) === $target) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeHostelName(string $value): string
    {
        $collapsed = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return strtoupper($collapsed);
    }

    /**
     * @param array<string,mixed> $catalog
     * @param array<int,int> $excludedFamilyIds
     * @return array<int,array<string,mixed>>
     */
    private function searchMergeableHostelsFromOntCatalog(array $catalog, array $excludedFamilyIds, int $limit = 20): array
    {
        $candidates = collect((array) ($catalog['hostels'] ?? []))
            ->map(fn ($candidate) => (array) $candidate)
            ->filter(function (array $candidate): bool {
                return trim((string) ($candidate['hostel_name'] ?? '')) !== '';
            })
            ->values();

        if ($candidates->isEmpty()) {
            return [];
        }

        $siteIds = $candidates
            ->pluck('site_id')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $siteSerials = $candidates
            ->pluck('site_sn')
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->unique()
            ->values();

        $candidateNames = $candidates
            ->pluck('hostel_name')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $matches = Hostel::query()
            ->where(function ($query) use ($siteIds, $siteSerials, $candidateNames) {
                if ($this->hostelOntColumnsAvailable()) {
                    if ($siteIds->isNotEmpty()) {
                        $query->orWhereIn('ont_site_id', $siteIds->all());
                    }

                    if ($siteSerials->isNotEmpty()) {
                        $query->orWhereIn('ont_site_sn', $siteSerials->all());
                    }
                }

                if ($candidateNames->isNotEmpty()) {
                    $query->orWhereIn('hostel_name', $candidateNames->all());
                }
            })
            ->orderBy('hostel_name')
            ->get();

        if ($matches->isEmpty()) {
            return [];
        }

        $rootMatches = [];
        foreach ($matches as $match) {
            $rootHostel = $this->resolveAgreementRootHostel($match);
            $rootId = (int) ($rootHostel->id ?? 0);
            if ($rootId <= 0 || in_array($rootId, $excludedFamilyIds, true)) {
                continue;
            }

            [$score, $matchLabel] = $this->ontCandidateMatchScore($match, $candidates);
            if ($score <= 0) {
                continue;
            }

            $sortName = strtolower((string) ($rootHostel->hostel_name ?? ''));
            if (!isset($rootMatches[$rootId]) || $score > (int) ($rootMatches[$rootId]['score'] ?? 0)) {
                $rootMatches[$rootId] = [
                    'root_id' => $rootId,
                    'score' => $score,
                    'sort_name' => $sortName,
                    'match_label' => $matchLabel,
                    'matched_hostel_name' => (string) ($match->hostel_name ?? $rootHostel->hostel_name ?? ''),
                ];
            }
        }

        if (empty($rootMatches)) {
            return [];
        }

        uasort($rootMatches, function (array $left, array $right): int {
            if ((int) ($left['score'] ?? 0) !== (int) ($right['score'] ?? 0)) {
                return (int) ($right['score'] ?? 0) <=> (int) ($left['score'] ?? 0);
            }

            return strcmp((string) ($left['sort_name'] ?? ''), (string) ($right['sort_name'] ?? ''));
        });

        $rootIds = array_slice(array_keys($rootMatches), 0, max(1, $limit));
        $rootHostels = Hostel::query()
            ->whereIn('id', $rootIds)
            ->get()
            ->keyBy('id');

        $summaries = collect($this->attachHostelPaymentSummaries($rootHostels->values()))
            ->keyBy('id');

        return collect($rootIds)->map(function ($rootId) use ($rootMatches, $summaries) {
            $summary = (array) ($summaries->get($rootId) ?? []);
            if (empty($summary)) {
                return null;
            }

            $matchMeta = (array) ($rootMatches[$rootId] ?? []);
            $matchedName = trim((string) ($matchMeta['matched_hostel_name'] ?? ''));
            $rootName = trim((string) ($summary['hostel_name'] ?? ''));

            $summary['search_match_label'] = (string) ($matchMeta['match_label'] ?? 'Matched from ONT directory');
            $summary['search_match_name'] = $matchedName !== '' && strcasecmp($matchedName, $rootName) !== 0
                ? $matchedName
                : null;

            return $summary;
        })->filter()->values()->all();
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,int> $excludedFamilyIds
     * @return array<int,array<string,mixed>>
     */
    private function filterOntCatalogCandidatesForExcludedFamily(array $rows, array $excludedFamilyIds): array
    {
        if (empty($rows) || empty($excludedFamilyIds)) {
            return array_values($rows);
        }

        $excludedHostels = Hostel::query()
            ->whereIn('id', $excludedFamilyIds)
            ->get(['hostel_name', 'ont_site_id', 'ont_site_sn']);

        if ($excludedHostels->isEmpty()) {
            return array_values($rows);
        }

        $excludedSiteIds = $excludedHostels
            ->pluck('ont_site_id')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->all();

        $excludedSiteSerials = $excludedHostels
            ->pluck('ont_site_sn')
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->unique()
            ->all();

        $excludedNames = $excludedHostels
            ->pluck('hostel_name')
            ->map(fn ($value) => $this->normalizeHostelName((string) $value))
            ->filter()
            ->unique()
            ->all();

        return collect($rows)
            ->map(fn ($candidate) => (array) $candidate)
            ->reject(function (array $candidate) use ($excludedSiteIds, $excludedSiteSerials, $excludedNames): bool {
                $candidateSiteId = trim((string) ($candidate['site_id'] ?? ''));
                $candidateSiteSerial = strtoupper(trim((string) ($candidate['site_sn'] ?? '')));
                $candidateName = $this->normalizeHostelName((string) ($candidate['hostel_name'] ?? ''));

                return ($candidateSiteId !== '' && in_array($candidateSiteId, $excludedSiteIds, true))
                    || ($candidateSiteSerial !== '' && in_array($candidateSiteSerial, $excludedSiteSerials, true))
                    || ($candidateName !== '' && in_array($candidateName, $excludedNames, true));
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int,int> $ids
     * @param array<int,int> $excludedFamilyIds
     * @return array<int,array<string,mixed>>
     */
    private function chainParentOptionsFromExistingIds(array $ids, array $excludedFamilyIds = [], int $excludeHostelId = 0): array
    {
        if (empty($ids)) {
            return [];
        }

        return Hostel::query()
            ->whereIn('id', $ids)
            ->orderBy('hostel_name')
            ->get()
            ->map(function (Hostel $hostel) use ($excludedFamilyIds, $excludeHostelId) {
                $rootHostel = $this->resolveAgreementRootHostel($hostel);
                $resolved = $this->resolveChainedFromHostel((int) $rootHostel->id, $excludeHostelId);
                if (!$resolved || in_array((int) $resolved->id, $excludedFamilyIds, true)) {
                    return null;
                }

                return [
                    'id' => (int) $resolved->id,
                    'ont_key' => trim((string) ($resolved->ont_site_id ?? '')) !== '' ? ('site:' . trim((string) $resolved->ont_site_id)) : '',
                    'hostel_name' => (string) ($resolved->hostel_name ?? ''),
                    'site_id' => (string) ($resolved->ont_site_id ?? ''),
                    'site_sn' => (string) ($resolved->ont_site_sn ?? ''),
                    'contact_person' => $resolved->contact_person ?? null,
                    'no_of_routers' => $this->totalRoutersForFamily($resolved),
                    'source_mode' => 'existing',
                    'source_label' => 'Main hostel ready in PettyCash',
                    'existing_hostel_name' => (string) ($resolved->hostel_name ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<int,array<string,mixed>> $candidates
     * @param array<int,int> $excludedFamilyIds
     * @return array<int,array<string,mixed>>
     */
    private function chainParentOptionsFromOntCandidates(array $candidates, array $excludedFamilyIds = [], int $excludeHostelId = 0): array
    {
        return collect($candidates)
            ->map(fn ($candidate) => (array) $candidate)
            ->filter(fn (array $candidate) => trim((string) ($candidate['hostel_name'] ?? '')) !== '')
            ->map(function (array $candidate) use ($excludedFamilyIds, $excludeHostelId) {
                $mainHostel = $this->findExistingMainHostelForOntCandidate($candidate, $excludeHostelId);
                if ($mainHostel && in_array((int) $mainHostel->id, $excludedFamilyIds, true)) {
                    return null;
                }

                $ontName = trim((string) ($candidate['hostel_name'] ?? ''));
                $existingName = trim((string) ($mainHostel?->hostel_name ?? ''));
                $routerTotal = $mainHostel
                    ? $this->totalRoutersForFamily($mainHostel)
                    : max(0, (int) ($candidate['router_count_suggestion'] ?? 0));

                return [
                    'id' => $mainHostel ? (int) $mainHostel->id : null,
                    'ont_key' => (string) ($candidate['key'] ?? ''),
                    'hostel_name' => $ontName,
                    'site_id' => trim((string) ($candidate['site_id'] ?? '')),
                    'site_sn' => trim((string) ($candidate['site_sn'] ?? '')),
                    'contact_person' => $mainHostel?->contact_person,
                    'no_of_routers' => $routerTotal,
                    'source_mode' => $mainHostel ? 'existing' : 'catalog',
                    'source_label' => $mainHostel
                        ? 'Main hostel ready in PettyCash'
                        : 'Will create a main hostel from the ONT catalogue',
                    'existing_hostel_name' => $existingName !== '' ? $existingName : null,
                ];
            })
            ->filter()
            ->unique(fn ($item) => (string) ($item['ont_key'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @param array<string,mixed> $candidate
     */
    private function findMatchingHostelForOntCandidate(array $candidate): ?Hostel
    {
        if ($this->hostelOntColumnsAvailable()) {
            $siteId = trim((string) ($candidate['site_id'] ?? ''));
            if ($siteId !== '') {
                $match = Hostel::query()
                    ->where('ont_site_id', $siteId)
                    ->orderBy('id')
                    ->first();

                if ($match) {
                    return $match;
                }
            }

            $siteSn = trim((string) ($candidate['site_sn'] ?? ''));
            if ($siteSn !== '') {
                $match = Hostel::query()
                    ->where('ont_site_sn', $siteSn)
                    ->orderBy('id')
                    ->first();

                if ($match) {
                    return $match;
                }
            }
        }

        $hostelName = trim((string) ($candidate['hostel_name'] ?? ''));
        if ($hostelName === '') {
            return null;
        }

        return Hostel::query()
            ->where('hostel_name', $hostelName)
            ->orderBy('id')
            ->first();
    }

    /**
     * @param array<string,mixed> $candidate
     */
    private function findExistingMainHostelForOntCandidate(array $candidate, int $excludeHostelId = 0): ?Hostel
    {
        $query = Hostel::query();

        if ($excludeHostelId > 0) {
            $query->where('id', '<>', $excludeHostelId);
        }
        if ($this->hostelAgreementFamilyColumnsAvailable()) {
            $query->whereNull('agreement_parent_hostel_id');
        }
        if ($this->hostelChainedColumnsAvailable()) {
            $query->whereNull('chained_from_hostel_id');
        }

        $siteId = trim((string) ($candidate['site_id'] ?? ''));
        if ($siteId !== '' && $this->hostelOntColumnsAvailable()) {
            $match = (clone $query)->where('ont_site_id', $siteId)->orderBy('id')->first();
            if ($match) {
                return $match;
            }
        }

        $siteSn = trim((string) ($candidate['site_sn'] ?? ''));
        if ($siteSn !== '' && $this->hostelOntColumnsAvailable()) {
            $match = (clone $query)->where('ont_site_sn', $siteSn)->orderBy('id')->first();
            if ($match) {
                return $match;
            }
        }

        $hostelName = trim((string) ($candidate['hostel_name'] ?? ''));
        if ($hostelName === '') {
            return null;
        }

        return (clone $query)->where('hostel_name', $hostelName)->orderBy('id')->first();
    }

    /**
     * @param array<string,mixed> $candidate
     */
    private function resolveOrCreateHostelFromOntCandidate(array $candidate): Hostel
    {
        $existing = $this->findMatchingHostelForOntCandidate($candidate);
        if ($existing) {
            return $existing;
        }

        $payload = [
            'hostel_name' => trim((string) ($candidate['hostel_name'] ?? '')) ?: 'New Hostel',
            'contact_person' => null,
            'meter_no' => null,
            'phone_no' => null,
            'no_of_routers' => max(0, (int) ($candidate['router_count_suggestion'] ?? 0)),
            'stake' => 'monthly',
            'amount_due' => 0,
        ];

        if ($this->hostelOntColumnsAvailable()) {
            $payload['ont_site_id'] = trim((string) ($candidate['site_id'] ?? '')) ?: null;
            $payload['ont_site_sn'] = trim((string) ($candidate['site_sn'] ?? '')) ?: null;
        }
        if ($this->hostelAgreementColumnsAvailable()) {
            $payload['agreement_type'] = 'none';
            $payload['agreement_label'] = null;
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = true;
        }
        if ($this->hostelChainedColumnsAvailable()) {
            $payload['chained_from_hostel_id'] = null;
        }

        return Hostel::create($payload);
    }

    /**
     * @param \Illuminate\Support\Collection<int,array<string,mixed>> $candidates
     * @return array{0:int,1:string}
     */
    private function ontCandidateMatchScore(Hostel $hostel, $candidates): array
    {
        $siteId = trim((string) ($hostel->ont_site_id ?? ''));
        $siteSerial = strtoupper(trim((string) ($hostel->ont_site_sn ?? '')));
        $hostelName = $this->normalizeHostelName((string) ($hostel->hostel_name ?? ''));

        foreach ($candidates as $index => $candidate) {
            $candidate = (array) $candidate;
            $candidateSiteId = trim((string) ($candidate['site_id'] ?? ''));
            $candidateSiteSerial = strtoupper(trim((string) ($candidate['site_sn'] ?? '')));
            $candidateName = $this->normalizeHostelName((string) ($candidate['hostel_name'] ?? ''));

            if ($siteId !== '' && $candidateSiteId !== '' && $siteId === $candidateSiteId) {
                return [400 - (int) $index, 'Matched by ONT site'];
            }

            if ($siteSerial !== '' && $candidateSiteSerial !== '' && $siteSerial === $candidateSiteSerial) {
                return [300 - (int) $index, 'Matched by ONT serial'];
            }

            if ($hostelName !== '' && $candidateName !== '' && $hostelName === $candidateName) {
                return [200 - (int) $index, 'Matched by ONT name'];
            }
        }

        return [0, ''];
    }

    private function resolveChainedFromHostel(int $hostelId, int $excludeHostelId = 0): ?Hostel
    {
        if ($hostelId <= 0 || !$this->hostelChainedColumnsAvailable() || !$this->hostelOntColumnsAvailable()) {
            return null;
        }

        $query = Hostel::query()
            ->whereKey($hostelId)
            ->whereNotNull('hostel_name')
            ->where('hostel_name', '<>', '')
            ->whereNotNull('ont_site_id')
            ->where('ont_site_id', '<>', '')
            ->whereNotNull('ont_site_sn')
            ->where('ont_site_sn', '<>', '');

        if ($excludeHostelId > 0) {
            $query->where('id', '<>', $excludeHostelId);
        }

        if ($this->hostelAgreementFamilyColumnsAvailable()) {
            $query->whereNull('agreement_parent_hostel_id');
        }

        if ($this->hostelChainedColumnsAvailable()) {
            $query->whereNull('chained_from_hostel_id');
        }

        return $query->first();
    }

    private function successResponse(Request $request, string $message, string $redirectUrl)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    private function resolveAgreementRootHostel(Hostel $hostel): Hostel
    {
        if (!$this->hostelAgreementFamilyColumnsAvailable()) {
            return $hostel;
        }

        $current = $hostel;
        $seen = [];

        while (!empty($current->agreement_parent_hostel_id)) {
            $currentId = (int) ($current->id ?? 0);
            if ($currentId <= 0 || in_array($currentId, $seen, true)) {
                break;
            }

            $seen[] = $currentId;
            $parent = Hostel::query()->find((int) $current->agreement_parent_hostel_id);
            if (!$parent) {
                break;
            }

            $current = $parent;
        }

        return $current;
    }

    /**
     * @return array<int,int>
     */
    private function familyHostelIdsForRoot(Hostel $rootHostel): array
    {
        if (!$this->hostelAgreementFamilyColumnsAvailable()) {
            return [(int) $rootHostel->id];
        }

        return Hostel::query()
            ->where('id', $rootHostel->id)
            ->orWhere('agreement_parent_hostel_id', $rootHostel->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function familyHostelsForRoot(Hostel $rootHostel)
    {
        $familyIds = $this->familyHostelIdsForRoot($rootHostel);

        return Hostel::query()
            ->whereIn('id', $familyIds)
            ->get()
            ->sortBy(function (Hostel $familyHostel) use ($rootHostel) {
                $sortPrefix = (int) $familyHostel->id === (int) $rootHostel->id ? '0' : '1';
                return $sortPrefix . '-' . strtolower((string) $familyHostel->hostel_name);
            })
            ->values();
    }

    private function familyChildHostelsForRoot(Hostel $rootHostel)
    {
        if (!$this->hostelAgreementFamilyColumnsAvailable()) {
            return collect();
        }

        return Hostel::query()
            ->where('agreement_parent_hostel_id', $rootHostel->id)
            ->orderBy('hostel_name')
            ->get();
    }

    /**
     * @param array<int,int> $hostelIds
     */
    private function totalRoutersForHostelIds(array $hostelIds): int
    {
        if (empty($hostelIds)) {
            return 0;
        }

        return (int) Hostel::query()
            ->whereIn('id', $hostelIds)
            ->sum('no_of_routers');
    }

    private function totalRoutersForFamily(Hostel $rootHostel): int
    {
        return $this->totalRoutersForHostelIds($this->familyHostelIdsForRoot($rootHostel));
    }

    private function paymentSupportsSpendingLink(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_payments', 'spending_id');
        }

        return $supports;
    }

    private function activeGatewayDevice(): ?PettyGatewayDevice
    {
        return PettyGatewayDevice::query()
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->first();
    }

    private function refreshHelperSmsParsing(Hostel $hostel, array $familyIds): void
    {
        $gatewayDevice = $this->activeGatewayDevice();
        if (!$gatewayDevice) {
            return;
        }

        $agreementType = $this->normalizeAgreementType((string) ($hostel->agreement_type ?? 'none'));
        if (!in_array($agreementType, ['token', 'send_money'], true)) {
            return;
        }

        $parser = app(SmsParsingService::class);
        $candidateRows = PettyTokenSmsLog::query()
            ->where('gateway_device_id', $gatewayDevice->id)
            ->whereIn('sms_kind', ['other', 'mpesa'])
            ->orderByDesc('sms_received_at')
            ->orderByDesc('id')
            ->limit(120)
            ->get();

        foreach ($candidateRows as $log) {
            $parsed = $parser->parse((string) $log->sms_body, $log->sender);
            if (($parsed['sms_kind'] ?? 'other') !== 'mpesa') {
                continue;
            }

            $log->forceFill([
                'sms_kind' => 'mpesa',
                'parsed_reference' => $parsed['parsed_reference'] ?? $log->parsed_reference,
                'parsed_meter_number' => $parsed['parsed_meter_number'] ?? $log->parsed_meter_number,
                'parsed_amount' => $parsed['parsed_amount'] ?? $log->parsed_amount,
                'parsed_transaction_cost' => $parsed['parsed_transaction_cost'] ?? $log->parsed_transaction_cost,
                'parsed_payment_type' => $parsed['parsed_payment_type'] ?? $log->parsed_payment_type,
                'sms_received_at' => $log->sms_received_at ?: ($parsed['sms_received_at'] ?? null),
            ])->save();
        }
    }

    private function helperPaymentCandidates(Hostel $hostel, array $familyIds)
    {
        $meterNumbers = collect([(string) ($hostel->meter_no ?? '')])
            ->merge(
                Hostel::query()
                    ->whereIn('id', $familyIds)
                    ->pluck('meter_no')
                    ->all()
            )
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $phoneNumbers = collect([(string) ($hostel->phone_no ?? '')])
            ->merge(
                Hostel::query()
                    ->whereIn('id', $familyIds)
                    ->pluck('phone_no')
                    ->all()
            )
            ->map(fn ($value) => $this->normalizePhoneMatchValue((string) $value))
            ->filter()
            ->flatMap(function (string $phone) {
                $digits = preg_replace('/\D+/', '', $phone) ?? '';
                $local = str_starts_with($digits, '254') ? ('0' . substr($digits, 3)) : $digits;
                return collect([$phone, $digits, $local])->filter();
            })
            ->unique()
            ->values();

        $contactNames = collect([(string) ($hostel->contact_person ?? '')])
            ->merge(
                Hostel::query()
                    ->whereIn('id', $familyIds)
                    ->pluck('contact_person')
                    ->all()
            )
            ->map(function ($value) {
                $normalized = strtolower(trim((string) $value));
                $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
                return $normalized;
            })
            ->filter(fn ($value) => $value !== '' && mb_strlen($value) >= 3)
            ->unique()
            ->values();

        $logs = PettyTokenSmsLog::query()
            ->where('sms_kind', 'mpesa')
            ->whereNotNull('parsed_reference')
            ->whereNotIn('parsed_reference', Payment::query()->select('reference'))
            ->orderByDesc('sms_received_at')
            ->orderByDesc('id')
            ->limit(160)
            ->get();

        $meterNeedles = $meterNumbers
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();
        $phoneNeedles = $phoneNumbers
            ->map(fn ($value) => preg_replace('/\D+/', '', (string) $value) ?? '')
            ->filter()
            ->unique()
            ->values();
        $nameNeedles = $contactNames
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->values();

        return $logs
            ->map(function (PettyTokenSmsLog $log) use ($meterNeedles, $phoneNeedles, $nameNeedles) {
                $score = 0;
                $parsedTarget = strtolower(trim((string) ($log->parsed_meter_number ?? '')));
                $body = strtolower((string) ($log->sms_body ?? ''));
                $bodyDigits = preg_replace('/\D+/', '', $body) ?? '';

                foreach ($meterNeedles as $meter) {
                    $meterValue = strtolower((string) $meter);
                    if ($meterValue === '') {
                        continue;
                    }
                    if ($parsedTarget === $meterValue) {
                        $score += 12;
                    } elseif (str_contains($body, $meterValue)) {
                        $score += 8;
                    }
                }

                foreach ($phoneNeedles as $phone) {
                    if ($phone === '') {
                        continue;
                    }
                    if ($parsedTarget !== '' && str_contains(preg_replace('/\D+/', '', $parsedTarget) ?? '', $phone)) {
                        $score += 12;
                    } elseif ($bodyDigits !== '' && str_contains($bodyDigits, $phone)) {
                        $score += 8;
                    }
                }

                foreach ($nameNeedles as $name) {
                    if ($name === '' || mb_strlen($name) < 3) {
                        continue;
                    }
                    if ($parsedTarget !== '' && str_contains($parsedTarget, $name)) {
                        $score += 6;
                    } elseif (str_contains($body, $name)) {
                        $score += 4;
                    }
                }

                if (($log->parsed_payment_type ?? null) === 'send_money') {
                    $score += 4;
                } elseif (($log->parsed_payment_type ?? null) === 'prepaid' || ($log->parsed_payment_type ?? null) === 'postpaid') {
                    $score += 3;
                }

                $log->helper_match_score = $score;
                return $log;
            })
            ->filter(fn (PettyTokenSmsLog $log) => (int) ($log->helper_match_score ?? 0) > 0)
            ->sortByDesc(fn (PettyTokenSmsLog $log) => [
                (int) ($log->helper_match_score ?? 0),
                optional($log->sms_received_at)->getTimestamp() ?? 0,
                (int) $log->id,
            ])
            ->take(40)
            ->values();
    }

    private function detectAgreementHistorySuggestions()
    {
        $roots = Hostel::query()
            ->when($this->hostelAgreementFamilyColumnsAvailable(), fn ($query) => $query->whereNull('agreement_parent_hostel_id'))
            ->orderBy('hostel_name')
            ->get();

        return $roots->map(function (Hostel $rootHostel) {
            $familyIds = $this->familyHostelIdsForRoot($rootHostel);

            $paymentRows = Payment::query()
                ->with('spending:id,meter_no,description')
                ->whereIn('hostel_id', $familyIds)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(25)
                ->get();

            $suggestion = $this->inferAgreementSuggestionFromPayments($rootHostel, $paymentRows);
            if ($suggestion === null) {
                return null;
            }

            $currentType = $this->normalizeAgreementType((string) ($rootHostel->agreement_type ?? 'none'));
            $currentLabel = trim((string) ($rootHostel->agreement_label ?? ''));
            $currentMeter = trim((string) ($rootHostel->meter_no ?? ''));
            $currentPhone = trim((string) ($rootHostel->phone_no ?? ''));

            $isDifferent = $currentType !== $suggestion['agreement_type']
                || ($suggestion['agreement_type'] === 'package' && $currentLabel !== trim((string) ($suggestion['agreement_label'] ?? '')))
                || ($suggestion['agreement_type'] === 'token' && $currentMeter !== trim((string) ($suggestion['meter_no'] ?? '')))
                || (in_array($suggestion['agreement_type'], ['token', 'send_money', 'package'], true) && $currentPhone !== trim((string) ($suggestion['phone_no'] ?? '')));

            if (!$isDifferent) {
                return null;
            }

            return [
                'hostel' => $rootHostel,
                'current' => [
                    'agreement_type' => $currentType,
                    'agreement_label' => $currentLabel,
                    'meter_no' => $currentMeter,
                    'phone_no' => $currentPhone,
                    'contact_person' => trim((string) ($rootHostel->contact_person ?? '')),
                ],
                'suggested' => $suggestion,
            ];
        })->filter()->values();
    }

    private function inferAgreementSuggestionFromPayments(Hostel $hostel, $paymentRows): ?array
    {
        if ($paymentRows->isEmpty()) {
            return null;
        }

        $packagePayment = $paymentRows->first(function (Payment $payment) {
            $receiverName = strtolower(trim((string) ($payment->receiver_name ?? '')));
            $notes = strtolower(trim((string) ($payment->notes ?? '')));

            return $receiverName === 'package credit'
                || str_contains($notes, 'package');
        });

        if ($packagePayment) {
            return [
                'agreement_type' => 'package',
                'agreement_label' => trim((string) ($hostel->agreement_label ?: 'Package')),
                'meter_no' => null,
                'phone_no' => trim((string) ($packagePayment->receiver_phone ?? $hostel->phone_no ?? '')),
                'contact_person' => trim((string) ($hostel->contact_person ?? '')),
                'reason' => 'Package-style payment found in history.',
                'evidence_reference' => $packagePayment->reference,
                'evidence_date' => optional($packagePayment->date)->format('Y-m-d'),
            ];
        }

        $tokenPayment = $paymentRows->first(function (Payment $payment) use ($hostel) {
            $spendingMeter = trim((string) optional($payment->spending)->meter_no);
            $hostelMeter = trim((string) ($hostel->meter_no ?? ''));
            $notes = strtolower(trim((string) ($payment->notes ?? '')));
            $description = strtolower(trim((string) (optional($payment->spending)->description ?? '')));

            return $spendingMeter !== ''
                || ($hostelMeter !== '' && trim((string) ($payment->receiver_phone ?? '')) !== '')
                || str_contains($notes, 'token')
                || str_contains($description, 'token payment');
        });

        if ($tokenPayment) {
            $suggestedMeter = trim((string) (optional($tokenPayment->spending)->meter_no ?: $hostel->meter_no ?: ''));
            if ($suggestedMeter !== '') {
                return [
                    'agreement_type' => 'token',
                    'agreement_label' => null,
                    'meter_no' => $suggestedMeter,
                    'phone_no' => trim((string) ($tokenPayment->receiver_phone ?? $hostel->phone_no ?? '')),
                    'contact_person' => trim((string) ($tokenPayment->receiver_name ?? $hostel->contact_person ?? '')),
                    'reason' => 'Previous token payment history found.',
                    'evidence_reference' => $tokenPayment->reference,
                    'evidence_date' => optional($tokenPayment->date)->format('Y-m-d'),
                ];
            }
        }

        $sendMoneyPayment = $paymentRows->first(function (Payment $payment) {
            $receiverPhone = trim((string) ($payment->receiver_phone ?? ''));
            $receiverName = trim((string) ($payment->receiver_name ?? ''));
            $spendingMeter = trim((string) optional($payment->spending)->meter_no);

            return $receiverPhone !== ''
                && $receiverName !== ''
                && $spendingMeter === '';
        });

        if ($sendMoneyPayment) {
            return [
                'agreement_type' => 'send_money',
                'agreement_label' => null,
                'meter_no' => null,
                'phone_no' => trim((string) ($sendMoneyPayment->receiver_phone ?? '')),
                'contact_person' => trim((string) ($sendMoneyPayment->receiver_name ?? $hostel->contact_person ?? '')),
                'reason' => 'Previous send money payment history found.',
                'evidence_reference' => $sendMoneyPayment->reference,
                'evidence_date' => optional($sendMoneyPayment->date)->format('Y-m-d'),
            ];
        }

        return null;
    }

    private function normalizePhoneMatchValue(string $value): string
    {
        $value = preg_replace('/[^0-9+]/', '', trim($value)) ?? '';
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, '0')) {
            return '+254' . ltrim($value, '0');
        }
        if (str_starts_with($value, '254')) {
            return '+' . $value;
        }
        return $value;
    }

    private function extractSendMoneyRecipientName(string $body): ?string
    {
        $body = trim($body);
        if ($body === '') {
            return null;
        }

        if (preg_match('/sent to\s+(.+?)\s+((?:\+?254|0)\d{9})\s+on\s+\d{1,2}\/\d{1,2}\/\d{2}\s+at\s+\d{1,2}:\d{2}\s*[AP]M/i', $body, $match)) {
            $name = trim((string) ($match[1] ?? ''));
            return $name !== '' ? preg_replace('/\s+/', ' ', $name) : null;
        }

        if (preg_match('/sent to\s+(.+?)\s+on\s+\d{1,2}\/\d{1,2}\/\d{2}\s+at\s+\d{1,2}:\d{2}\s*[AP]M/i', $body, $match)) {
            $name = trim((string) ($match[1] ?? ''));
            $name = preg_replace('/\s+/', ' ', $name) ?? '';
            if ($name !== '' && !preg_match('/^(kplc|safaricom|mpesa)$/i', $name)) {
                return $name;
            }
        }

        return null;
    }

    private function paymentOverpayColumnsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_payments', 'is_overpay_application')
                && PettyDatabase::schema()->hasColumn('petty_payments', 'overpay_source_payment_id');
        }

        return $supports;
    }

    private function cycleMonthsForHostel(Hostel $hostel): int
    {
        return (($hostel->stake ?: 'monthly') === 'semester') ? 4 : 1;
    }

    private function isSourcePaymentOverpayEligible(Hostel $hostel, Payment $source): bool
    {
        if (!$source->date) {
            return false;
        }

        $sourceDate = Carbon::parse($source->date)->startOfDay();
        $sourceDateString = $sourceDate->format('Y-m-d');

        $previous = Payment::query()
            ->where('hostel_id', $hostel->id)
            ->where(function ($w) {
                $w->whereNull('is_overpay_application')
                    ->orWhere('is_overpay_application', false);
            })
            ->where(function ($w) use ($sourceDateString, $source) {
                $w->whereDate('date', '<', $sourceDateString)
                    ->orWhere(function ($sameDate) use ($sourceDateString, $source) {
                        $sameDate->whereDate('date', $sourceDateString)
                            ->where('id', '<', (int) $source->id);
                    });
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        if (!$previous || !$previous->date) {
            return false;
        }

        $previousDate = Carbon::parse($previous->date)->startOfDay();
        $expectedDue = $previousDate
            ->copy()
            ->addMonthsNoOverflow($this->cycleMonthsForHostel($hostel))
            ->startOfDay();

        return $sourceDate->lt($expectedDue);
    }

    private function hostelOntColumnsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_hostels', 'ont_site_id')
                && PettyDatabase::schema()->hasColumn('petty_hostels', 'ont_site_sn');
        }

        return $supports;
    }

    private function hostelOntMergedColumnAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_hostels', 'ont_merged');
        }

        return $supports;
    }

    private function hostelAgreementColumnsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_hostels', 'agreement_type')
                && PettyDatabase::schema()->hasColumn('petty_hostels', 'agreement_label');
        }

        return $supports;
    }

    private function hostelAgreementFamilyColumnsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_hostels', 'agreement_parent_hostel_id');
        }

        return $supports;
    }

    private function hostelChainedColumnsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_hostels', 'chained_from_hostel_id');
        }

        return $supports;
    }

    private function hostelAgreementTerminationColumnsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasColumn('petty_hostels', 'agreement_terminated_at')
                && PettyDatabase::schema()->hasColumn('petty_hostels', 'agreement_termination_reason')
                && PettyDatabase::schema()->hasColumn('petty_hostels', 'agreement_transfer_hostel_id');
        }

        return $supports;
    }

    private function hostelPendingCreditsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = PettyDatabase::schema()->hasTable('petty_hostel_pending_credits')
                && PettyDatabase::schema()->hasColumn('petty_hostel_pending_credits', 'hostel_id')
                && PettyDatabase::schema()->hasColumn('petty_hostel_pending_credits', 'status');
        }

        return $supports;
    }

    private function normalizeAgreementType(string $agreementType): string
    {
        $normalized = strtolower(trim($agreementType));

        return in_array($normalized, ['token', 'send_money', 'package', 'none'], true)
            ? $normalized
            : 'none';
    }

    private function agreementTypeLabel(string $agreementType): string
    {
        return match ($this->normalizeAgreementType($agreementType)) {
            'token' => 'Token',
            'send_money' => 'Send Money',
            'package' => 'Package',
            default => 'No Agreement',
        };
    }

    private function ontKeyForHostel(Hostel $hostel): string
    {
        $siteId = trim((string) ($hostel->ont_site_id ?? ''));
        if ($siteId !== '') {
            return str_starts_with($siteId, 'site:') ? $siteId : ('site:' . $siteId);
        }

        return '';
    }

    private function ensureHostelFamilyIsOntBacked(Hostel $rootHostel): void
    {
        foreach ($this->familyHostelsForRoot($rootHostel) as $familyHostel) {
            [$candidate] = $this->resolveOntCandidate(
                (string) ($familyHostel->hostel_name ?? ''),
                $this->ontKeyForHostel($familyHostel)
            );

            if ($candidate !== null) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'apply_to_hostels' => 'Joined hostels must come from the ONT directory. Merge the hostel from ONT before attaching it here.',
        ]);
    }

    public function pdfHostels()
    {
        $format = strtolower((string) request()->query('format', 'pdf'));
        $semesterMonths = 4;
        $q = trim((string) request()->query('q', ''));
        $supportsOntSiteColumns = $this->hostelOntColumnsAvailable();
        $supportsAgreementFamilyColumns = $this->hostelAgreementFamilyColumnsAvailable();

        $lastPaySub = Payment::query();
        if ($supportsAgreementFamilyColumns) {
            $lastPaySub
                ->join('petty_hostels as payment_hostels', 'payment_hostels.id', '=', 'petty_payments.hostel_id')
                ->selectRaw('COALESCE(payment_hostels.agreement_parent_hostel_id, payment_hostels.id) as root_hostel_id')
                ->selectRaw('MAX(petty_payments.date) as last_payment_date')
                ->groupBy('root_hostel_id');
        } else {
            $lastPaySub
                ->selectRaw('hostel_id as root_hostel_id')
                ->selectRaw('MAX(date) as last_payment_date')
                ->groupBy('hostel_id');
        }

        $hostels = Hostel::query()
            ->when($supportsAgreementFamilyColumns, function ($query) {
                $query->whereNull('petty_hostels.agreement_parent_hostel_id');
            })
            ->leftJoinSub($lastPaySub, 'lp', function ($join) {
                $join->on('lp.root_hostel_id', '=', 'petty_hostels.id');
            })
            ->select('petty_hostels.*', 'lp.last_payment_date')
            ->when($q !== '', function ($qq) use ($q, $supportsOntSiteColumns, $supportsAgreementFamilyColumns) {
                $like = '%' . str_replace('%', '\\%', $q) . '%';

                $qq->where(function ($w) use ($like, $supportsOntSiteColumns, $supportsAgreementFamilyColumns) {
                    $w->where('hostel_name', 'like', $like)
                        ->orWhere('contact_person', 'like', $like)
                        ->orWhere('meter_no', 'like', $like)
                        ->orWhere('phone_no', 'like', $like);

                    if ($supportsOntSiteColumns) {
                        $w->orWhere('ont_site_sn', 'like', $like)
                            ->orWhere('ont_site_id', 'like', $like);
                    }

                    if ($supportsAgreementFamilyColumns) {
                        $w->orWhereExists(function ($childQuery) use ($like, $supportsOntSiteColumns) {
                            $childQuery
                                ->selectRaw('1')
                                ->from('petty_hostels as child_hostels')
                                ->whereColumn('child_hostels.agreement_parent_hostel_id', 'petty_hostels.id')
                                ->where(function ($childWhere) use ($like, $supportsOntSiteColumns) {
                                    $childWhere
                                        ->where('child_hostels.hostel_name', 'like', $like)
                                        ->orWhere('child_hostels.contact_person', 'like', $like)
                                        ->orWhere('child_hostels.meter_no', 'like', $like)
                                        ->orWhere('child_hostels.phone_no', 'like', $like);

                                    if ($supportsOntSiteColumns) {
                                        $childWhere
                                            ->orWhere('child_hostels.ont_site_sn', 'like', $like)
                                            ->orWhere('child_hostels.ont_site_id', 'like', $like);
                                    }
                                });
                        });
                    }
                });
            })
            ->orderBy('hostel_name')
            ->get()
            ->map(function ($h) {
                $h->last_payment_date = $h->last_payment_date ? Carbon::parse($h->last_payment_date)->format('Y-m-d') : null;
                return $h;
            });

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $hostels->map(function ($h) use ($semesterMonths) {
                $lastDate = $h->last_payment_date ? Carbon::parse($h->last_payment_date)->startOfDay() : null;
                $dueDate = null;

                if ($lastDate) {
                    $dueDate = ($h->stake === 'semester')
                        ? $lastDate->copy()->addMonthsNoOverflow($semesterMonths)->format('Y-m-d')
                        : $lastDate->copy()->addMonthNoOverflow()->format('Y-m-d');
                }

                return [
                    'hostel_name' => $h->hostel_name,
                    'site_sn' => $h->ont_site_sn ?? '',
                    'contact_person' => $h->contact_person ?? '',
                    'meter_no' => $h->meter_no ?? '',
                    'phone_no' => $h->phone_no ?? '',
                    'routers' => (int) ($h->no_of_routers ?? 0),
                    'stake' => strtoupper((string) ($h->stake ?? '')),
                    'amount_due' => number_format((float) $h->amount_due, 2, '.', ''),
                    'last_payment_date' => $h->last_payment_date ?? '',
                    'next_due_date' => $dueDate ?? '',
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-token-hostels-' . now()->format('Ymd-His'),
                [
                    'Hostel' => 'hostel_name',
                    'Site S.N' => 'site_sn',
                    'Name' => 'contact_person',
                    'Meter Number' => 'meter_no',
                    'Phone Number' => 'phone_no',
                    'Routers' => 'routers',
                    'Billing Cycle' => 'stake',
                    'Due Amount' => 'amount_due',
                    'Last Payment Date' => 'last_payment_date',
                    'Next Due Date' => 'next_due_date',
                ],
                $rows
            );
        }

        $pdf = Pdf::loadView('pettycash::reports.tokens_hostels_pdf', [
            'hostels' => $hostels,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-token-hostels.pdf');
    }

    public function pdfHostelPayments(Hostel $hostel)
    {
        $hostel = $this->resolveAgreementRootHostel($hostel);
        $format = strtolower((string) request()->query('format', 'pdf'));
        $familyIds = $this->familyHostelIdsForRoot($hostel);
        $hasFamilyRows = count($familyIds) > 1;

        $payments = Payment::whereIn('hostel_id', $familyIds)
            ->with(['batch', 'hostel'])
            ->orderByDesc('date')
            ->get();

        $total = (float) $payments->sum(fn ($p) => (float) $p->amount + (float) ($p->transaction_cost ?? 0));

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $payments->map(function ($p) use ($hasFamilyRows) {
                $amount = (float) $p->amount;
                $fee = (float) ($p->transaction_cost ?? 0);

                $row = [
                    'date' => $p->date?->format('Y-m-d'),
                    'reference' => $p->reference ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'receiver_name' => $p->receiver_name ?? '',
                    'receiver_phone' => $p->receiver_phone ?? '',
                    'batch_no' => $p->batch?->batch_no ?? '',
                    'notes' => $p->notes ?? '',
                ];

                if ($hasFamilyRows) {
                    $row = ['hostel_name' => $p->hostel?->hostel_name ?? 'Hostel #' . (int) $p->hostel_id] + $row;
                }

                return $row;
            })->all();

            $columns = [
                'Date' => 'date',
                'Reference' => 'reference',
                'Amount' => 'amount',
                'Cost' => 'transaction_cost',
                'Total' => 'total',
                'Name' => 'receiver_name',
                'Phone Number' => 'receiver_phone',
                'Batch' => 'batch_no',
                'Notes' => 'notes',
            ];
            if ($hasFamilyRows) {
                $columns = ['Hostel' => 'hostel_name'] + $columns;
            }

            return TabularExport::download(
                $format,
                'pettycash-token-hostel-' . $hostel->id . '-payments-' . now()->format('Ymd-His'),
                $columns,
                $rows
            );
        }

        $pdf = Pdf::loadView('pettycash::reports.tokens_hostel_payments_pdf', [
            'hostel' => $hostel,
            'payments' => $payments,
            'hasFamilyRows' => $hasFamilyRows,
            'total' => $total,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-token-' . $hostel->id . '-payments.pdf');
    }
}
