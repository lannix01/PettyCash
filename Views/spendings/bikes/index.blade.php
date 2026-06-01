@extends('pettycash::layouts.app')

@section('title','Transportation Spendings')

@push('styles')
<style>
    .wrap{max-width:1200px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:700;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700}
    input,select{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px}
    .muted{color:#667085;font-size:12px}
    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}
    .status-pill{display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;border:1px solid #d0d5dd;font-size:11px;font-weight:700;margin-top:4px}
    .status-pill-active{background:#ecfdf3;border-color:#abefc6;color:#027a48}
    .status-pill-inactive{background:#f2f4f7;border-color:#eaecf0;color:#475467}
    .status-pill-flagged{background:#fffaeb;border-color:#fedf89;color:#b54708}
    .status-pill-disabled{background:#fef3f2;border-color:#fecdca;color:#b42318}
</style>
@endpush

@section('content')
@php
    $pettyUser = auth('petty')->user();
    $canEditBikeSpending = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'bikes.edit');
    $canDeleteBikeSpending = \App\Modules\PettyCash\Support\PettyAccess::isAdmin($pettyUser);
@endphp
<div class="wrap pc-list-shell" data-pc-list-root="bikes-spendings-index" data-pc-ajax="1">
    <div class="pc-inline-refresh"><span class="spinner"></span><span>Refreshing records...</span></div>
    <div class="top">
        <div>
            <h2 style="margin:0">Fuel/Maintenance Expenses</h2>
            <div class="muted">Total in range: <span class="pill">{{ number_format($total, 2) }}</span></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn" href="{{ route('petty.bikes.create') }}">+ New Spending</a>
            @include('pettycash::partials.export_select', [
                'options' => [
                    'PDF' => route('petty.bikes.pdf', ['from'=>$from,'to'=>$to,'sub_type'=>$sub,'batch_id'=>$batchId,'q'=>$q,'sort'=>$sort,'format'=>'pdf']),
                    'CSV' => route('petty.bikes.pdf', ['from'=>$from,'to'=>$to,'sub_type'=>$sub,'batch_id'=>$batchId,'q'=>$q,'sort'=>$sort,'format'=>'csv']),
                    'Excel' => route('petty.bikes.pdf', ['from'=>$from,'to'=>$to,'sub_type'=>$sub,'batch_id'=>$batchId,'q'=>$q,'sort'=>$sort,'format'=>'excel']),
                ],
            ])
        </div>
    </div>

    <div class="card">
        <div class="pc-filter-dock">
            <details class="pc-filter-panel" open data-filter-pinned="1">
                <summary>
                    <span class="pc-filter-title">Filters</span>
                    <span class="pc-filter-state">live</span>
                </summary>
                <div class="pc-filter-body">
                    <form method="GET" class="row pc-filter-row" action="{{ route('petty.bikes.index') }}" data-pc-auto-filter="1" data-pc-list-root-id="bikes-spendings-index">
                        <div class="pc-filter-grow">
                            <div class="muted">Search</div>
                            <input type="search" name="q" value="{{ $q }}" placeholder="Plate, ref, respondent, batch, particulars">
                        </div>
                        <div>
                            <div class="muted">From</div>
                            <input type="date" name="from" value="{{ $from }}">
                        </div>
                        <div>
                            <div class="muted">To</div>
                            <input type="date" name="to" value="{{ $to }}">
                        </div>
                        <div>
                            <div class="muted">Type</div>
                            <select name="sub_type">
                                <option value="">All</option>
                                <option value="fuel" @selected($sub==='fuel')>Fuel</option>
                                <option value="maintenance" @selected($sub==='maintenance')>Maintenance</option>
                            </select>
                        </div>
                        <div>
                            <div class="muted">Batch</div>
                            <select name="batch_id">
                                <option value="">All</option>
                                @foreach($batches as $b)
                                    <option value="{{ $b->id }}" @selected((string)$batchId === (string)$b->id)>{{ $b->batch_no }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div class="muted">Sort</div>
                            <select name="sort">
                                <option value="date_desc" @selected($sort === 'date_desc')>Newest First</option>
                                <option value="date_asc" @selected($sort === 'date_asc')>Oldest First</option>
                                <option value="amount_desc" @selected($sort === 'amount_desc')>Amount High-Low</option>
                                <option value="amount_asc" @selected($sort === 'amount_asc')>Amount Low-High</option>
                            </select>
                        </div>
                        <div class="pc-filter-actions">
                            <button class="btn" type="submit">Apply</button>
                            <a class="btn2" href="{{ route('petty.bikes.index') }}">Reset</a>
                        </div>
                    </form>
                </div>
            </details>
        </div>

        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Plate</th>
                <th>Subtype</th>
                <th>MPESA Ref</th>
                <th>Amount</th>
                <th>Respondent</th>
                <th>Description</th>
                <th>Particulars</th>
                <th>Batch</th>
                <th>Actions</th>

            </tr>
            </thead>
            <tbody>
            @forelse($spendings as $s)
                @php $bikeId = (int)$s->related_id; @endphp
                <tr>
                    <td>{{ $s->date?->format('Y-m-d') }}</td>
                    <td>
                        @if($s->bike)
                            <a href="{{ route('petty.bikes.byBike', $s->bike->id) }}">{{ $s->bike->plate_no }}</a>
                            @php $bikeStatus = $s->bike->normalizedStatus(); @endphp
                            <div><span class="status-pill status-pill-{{ $bikeStatus }}">{{ $s->bike->statusLabel() }}</span></div>
                        @else
                            -
                        @endif
                    </td>

                    <td><span class="pill">{{ strtolower($s->sub_type ?? '-') }}</span></td>
                    <td>{{ $s->reference }}</td>
                    <td>{{ number_format((float)$s->amount, 2) }}</td>
                    <td>{{ $s->respondent?->name ?? '-' }}</td>
                    <td>{{ $s->description }}</td>
                    <td>{{ $s->particulars }}</td>
                    <td>{{ $s->batch?->batch_no ?? '-' }}</td>
                    <td>
                        @if($canEditBikeSpending)
                            <a href="{{ route('petty.bikes.edit', $s->id) }}">Edit</a>
                        @endif
                        @if($canDeleteBikeSpending)
                            <form method="POST" action="{{ route('petty.bikes.destroy', $s->id) }}" style="display:inline-block;margin-left:8px" data-confirm="Delete this bike spending?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="border:none;background:none;color:#b42318;cursor:pointer;padding:0">Delete</button>
                            </form>
                        @endif
                        @if(!$canEditBikeSpending && !$canDeleteBikeSpending)
                            <span class="muted">-</span>
                        @endif
                    </td>

                </tr>
            @empty
                <tr><td colspan="9" class="muted">No  spendings yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>

        <div style="margin-top:12px;">{{ $spendings->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
    </div>
</div>
@endsection
