@extends('pettycash::layouts.app')
@section('title','Bikes (Master)')

@push('styles')
<style>
.wrap{max-width:1100px;margin:0 auto}
.card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:800}
.muted{color:#667085;font-size:12px}
input,select{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px;background:#fff}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
th{font-size:12px;color:#475467;text-align:left}
.success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}
.status-badge{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;border:1px solid #d0d5dd;font-size:11px;font-weight:800}
.status-active{background:#ecfdf3;border-color:#abefc6;color:#027a48}
.status-inactive{background:#f2f4f7;border-color:#eaecf0;color:#475467}
.status-flagged{background:#fffaeb;border-color:#fedf89;color:#b54708}
.status-disabled{background:#fef3f2;border-color:#fecdca;color:#b42318}
</style>
@endpush

@section('content')
@php $canDeleteBikeMaster = \App\Modules\PettyCash\Support\PettyAccess::isAdmin(auth('petty')->user()); @endphp
<div class="wrap pc-list-shell" data-pc-list-root="bikes-master-index" data-pc-ajax="1">
  <div class="pc-inline-refresh"><span class="spinner"></span><span>Refreshing records...</span></div>
  <div class="top">
    <div>
      <h2 style="margin:0">Transportation (Master)</h2>
     <!-- <div class="muted">Bikes and other transportation details</div> -->
    </div>
    <a class="btn" href="{{ route('petty.bikes_master.create') }}">+ Add New</a>
  </div>

  <div class="card">
    <div class="pc-filter-dock">
      <details class="pc-filter-panel" open data-filter-pinned="1">
        <summary>
          <span class="pc-filter-title">Filters</span>
          <span class="pc-filter-state">live</span>
        </summary>
        <div class="pc-filter-body">
          <form method="GET" action="{{ route('petty.bikes_master.index') }}" class="pc-filter-row" data-pc-auto-filter="1" data-pc-list-root-id="bikes-master-index">
            <div class="pc-filter-grow">
              <div class="muted">Search</div>
              <input type="search" name="q" value="{{ $q }}" placeholder="Plate number, model, status">
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
              <div class="muted">Sort</div>
              <select name="sort">
                <option value="latest" @selected($sort === 'latest')>Newest First</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest First</option>
                <option value="plate_asc" @selected($sort === 'plate_asc')>Plate A-Z</option>
                <option value="plate_desc" @selected($sort === 'plate_desc')>Plate Z-A</option>
              </select>
            </div>
            <div class="pc-filter-actions">
              <button class="btn" type="submit">Apply</button>
              <a class="btn2" href="{{ route('petty.bikes_master.index') }}">Reset</a>
            </div>
          </form>
        </div>
      </details>
    </div>
    <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Plate No</th>
          <th>Model</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($bikes as $b)
        <tr>
          <td>{{ $b->plate_no }}</td>
          <td>{{ $b->model ?? '-' }}</td>
          @php $status = $b->normalizedStatus(); @endphp
          <td>
            <span class="status-badge status-{{ $status }}">{{ $b->statusLabel() }}</span>
            @if($status === \App\Modules\PettyCash\Models\Bike::STATUS_DISABLED)
              <div class="muted" style="margin-top:4px">Hidden from new spending selection.</div>
            @endif
          </td>
          <td>
            <a href="{{ route('petty.bikes_master.edit', $b->id) }}">Edit</a>
            @if($canDeleteBikeMaster)
              <form method="POST" action="{{ route('petty.bikes_master.destroy', $b->id) }}" style="display:inline-block;margin-left:8px" data-confirm="Delete bike {{ $b->plate_no }}?">
                @csrf
                @method('DELETE')
                <button type="submit" style="border:none;background:none;color:#b42318;cursor:pointer;padding:0">Delete</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">No transportation modes added yet.</td></tr>
      @endforelse
      </tbody>
    </table>
    </div>

    <div style="margin-top:12px">{{ $bikes->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
  </div>
</div>
@endsection
