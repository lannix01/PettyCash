@extends('pettycash::layouts.app')
@section('title','Add Bike')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Add Bike</h2>
      <div class="form-subtitle">Register a new bike in master data for spending and service logs.</div>
    </div>
    <a class="btn2" href="{{ route('petty.bikes_master.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.bikes_master.store') }}">
      @csrf

      <div class="pc-workflow" data-workflow>
        <section class="pc-step" data-step="bike-main" data-step-open="1" data-step-unlocked="1">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">1</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Bike identity</span>
                <span class="pc-step-text">Register the main transport record with plate number and model.</span>
              </span>
            </span>
            <span style="display:flex;align-items:center;gap:10px">
              <span class="pc-step-meta" data-step-meta>Open</span>
              <span class="pc-step-arrow">⌄</span>
            </span>
          </button>
          <div class="pc-step-body" data-step-body>
            <div class="pc-step-panel">
              <div class="pc-field">
                <label>Plate Number</label>
                <input class="pc-input" name="plate_no" value="{{ old('plate_no') }}" required>
              </div>

              <div class="pc-field">
                <label>Model</label>
                <input class="pc-input" name="model" value="{{ old('model') }}">
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn2" type="button" data-step-next>Proceed to Status</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="pc-step" data-step="bike-status">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">2</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Status</span>
                <span class="pc-step-text">Choose the operational status that controls whether this bike can be selected for new spending records.</span>
              </span>
            </span>
            <span style="display:flex;align-items:center;gap:10px">
              <span class="pc-step-meta" data-step-meta>Locked</span>
              <span class="pc-step-arrow">⌄</span>
            </span>
          </button>
          <div class="pc-step-body" data-step-body hidden>
            <div class="pc-step-panel">
              <div class="pc-field full">
                <label>Status</label>
                <select class="pc-select" name="status" required>
                  @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', \App\Modules\PettyCash\Models\Bike::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                <div class="pc-help">Only <strong>Active</strong> bikes will appear when recording new bike spendings.</div>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn" type="submit">Save Bike</button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </form>
  </div>
</div>
@endsection
