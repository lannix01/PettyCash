@extends('pettycash::layouts.app')
@section('title','Respondents')

@push('styles')
<style>
.wrap{max-width:1100px;margin:0 auto}
.card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:800}
.btn2{display:inline-block;padding:7px 10px;border-radius:8px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700;font-size:12px}
.muted{color:#667085;font-size:12px}
input,select{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px;background:#fff}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
th{font-size:12px;color:#475467;text-align:left}
.success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}
.badge{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:800}
.badge-ok{background:#ecfdf3;color:#027a48;border:1px solid #abefc6}
.badge-off{background:#f2f4f7;color:#475467;border:1px solid #eaecf0}
.badge-alert{background:#fff7e6;color:#b54708;border:1px solid #fedf89}
.badge-stop{background:#fef3f2;color:#b42318;border:1px solid #fecdca}
.actions{display:flex;gap:6px;flex-wrap:wrap}
</style>
@endpush

@section('content')
@php
  $pettyUser = auth('petty')->user();
  $canCreateRespondent = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'respondents.create');
  $canEditRespondent = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'respondents.edit');
  $canDeleteRespondent = \App\Modules\PettyCash\Support\PettyAccess::isAdmin($pettyUser);
@endphp
<div class="wrap pc-list-shell" data-pc-list-root="respondents-index" data-pc-ajax="1">
  <div class="pc-inline-refresh"><span class="spinner"></span><span>Refreshing records...</span></div>
  <div class="top">
    <div>
      <h2 style="margin:0">Respondents</h2>
      <!-- <div class="muted">People asking for payments for items.</div> -->
    </div>
    @if($canCreateRespondent)
      <a class="btn" href="{{ route('petty.respondents.create') }}">+ Add Respondent</a>
    @endif
  </div>

  <div class="card">
    <div class="pc-filter-dock">
      <details class="pc-filter-panel" open data-filter-pinned="1">
        <summary>
          <span class="pc-filter-title">Filters</span>
          <span class="pc-filter-state">live</span>
        </summary>
        <div class="pc-filter-body">
          <form method="GET" action="{{ route('petty.respondents.index') }}" class="pc-filter-row" data-pc-auto-filter="1" data-pc-list-root-id="respondents-index">
            <div class="pc-filter-grow">
              <div class="muted">Search</div>
              <input type="search" name="q" value="{{ $q }}" placeholder="Name, phone, staff ID, category, email">
            </div>
            <div>
              <div class="muted">Status</div>
              <select name="status">
                <option value="">All</option>
                @foreach($statusOptions as $statusValue => $statusLabel)
                  <option value="{{ $statusValue }}" @selected($status === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <div class="muted">Category</div>
              <select name="category">
                <option value="">All</option>
                @foreach($categoryOptions as $categoryOption)
                  <option value="{{ $categoryOption }}" @selected($category === $categoryOption)>{{ $categoryOption }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <div class="muted">Sort</div>
              <select name="sort">
                <option value="latest" @selected($sort === 'latest')>Newest First</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest First</option>
                <option value="name_asc" @selected($sort === 'name_asc')>Name A-Z</option>
                <option value="name_desc" @selected($sort === 'name_desc')>Name Z-A</option>
              </select>
            </div>
            <div class="pc-filter-actions">
              <button class="btn" type="submit">Apply</button>
              <a class="btn2" href="{{ route('petty.respondents.index') }}">Reset</a>
            </div>
          </form>
        </div>
      </details>
    </div>
    <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Category</th>
          <th>Status</th>
          <th>Card</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($respondents as $r)
        <tr>
          <td>
            <div style="font-weight:800">{{ $r->name }}</div>
            @if($r->profile_title)
              <div class="muted">{{ $r->profile_title }}</div>
            @endif
          </td>
          <td>{{ $r->phone ?? '-' }}</td>
          <td>{{ $r->category ?? '-' }}</td>
          <td>
            @php
              $statusClass = match($r->normalizedStatus()) {
                  \App\Modules\PettyCash\Models\Respondent::STATUS_ACTIVE => 'badge-ok',
                  \App\Modules\PettyCash\Models\Respondent::STATUS_DECOMMISSIONED => 'badge-stop',
                  \App\Modules\PettyCash\Models\Respondent::STATUS_FLAGGED => 'badge-alert',
                  default => 'badge-off',
              };
            @endphp
            <span class="badge {{ $statusClass }}">{{ $r->statusLabel() }}</span>
          </td>
          <td>
            @if($r->card_generated_at)
              <span class="badge badge-ok">Generated</span>
              <div class="muted" style="margin-top:4px">{{ $r->card_generated_at?->format('Y-m-d H:i') }}</div>
            @else
              <span class="badge badge-off">Not generated</span>
            @endif
          </td>
          <td>
            <div class="actions">
              <a class="btn2" href="{{ route('petty.respondents.show', $r->id) }}">Profile</a>
              @if($canEditRespondent)
                <form method="POST" action="{{ route('petty.respondents.card.generate', $r->id) }}" style="margin:0">
                  @csrf
                  <button class="btn2" type="submit">Generate Card</button>
                </form>
                <form method="POST" action="{{ route('petty.respondents.card.sms', $r->id) }}" style="margin:0">
                  @csrf
                  <button class="btn2" type="submit">Send SMS</button>
                </form>
                @if($r->card_public_token)
                  <a class="btn2" target="_blank" href="{{ route('petty.respondents.card.public.short', ['token' => $r->card_public_token]) }}">Public Link</a>
                @endif
              @endif
              @if($canDeleteRespondent)
                <form method="POST" action="{{ route('petty.respondents.destroy', $r->id) }}" style="margin:0" data-confirm="Delete or decommission respondent {{ $r->name }}?">
                  @csrf
                  @method('DELETE')
                  <button class="btn2" type="submit">{{ $r->normalizedStatus() === \App\Modules\PettyCash\Models\Respondent::STATUS_DECOMMISSIONED ? 'Delete' : 'Delete / Decommission' }}</button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted">No respondents added yet.</td></tr>
      @endforelse
      </tbody>
    </table>
    </div>

    <div style="margin-top:12px">{{ $respondents->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
  </div>
</div>
@endsection
