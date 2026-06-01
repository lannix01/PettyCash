@extends('pettycash::layouts.app')
@section('title','Edit Bike Spending')

@section('content')
<div class="form-wrap">
  <div class="form-header">
    <div>
      <h2>Edit Bike Spending</h2>
      <div class="form-subtitle">Fees are included so batch balances stay accurate.</div>
    </div>
    <a class="btn2" href="{{ route('petty.bikes.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.bikes.update', $spending->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-workflow" data-workflow>
        <section class="pc-step" data-step="bike-edit-source" data-step-open="1" data-step-unlocked="1">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">1</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Source and type</span>
                <span class="pc-step-text">Start with the batch, subtype, bike, and respondent.</span>
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
                  <label>Batch</label>
                  <select class="pc-select" name="batch_id" required>
                    @foreach($batches as $b)
                      <option value="{{ $b->id }}" @selected((string)old('batch_id', $spending->batch_id) === (string)$b->id)>
                        {{ $b->batch_no ?? $b->id }}
                      </option>
                    @endforeach
                  </select>
              </div>

              <div class="pc-field">
                  <label>Subtype</label>
                  <select class="pc-select" name="sub_type" required>
                    <option value="fuel" @selected(old('sub_type', $spending->sub_type)==='fuel')>Fuel</option>
                    <option value="maintenance" @selected(old('sub_type', $spending->sub_type)==='maintenance')>Maintenance</option>
                  </select>
              </div>

              <div class="pc-field">
                  <label>Bike</label>
                  <select class="pc-select" name="bike_id" required>
                    @foreach($bikes as $bike)
                      <option value="{{ $bike->id }}" @selected((string)old('bike_id', $spending->related_id) === (string)$bike->id)>
                        {{ $bike->plate_no }}
                        @if($bike->normalizedStatus() !== \App\Modules\PettyCash\Models\Bike::STATUS_ACTIVE)
                          ({{ $bike->statusLabel() }})
                        @endif
                      </option>
                    @endforeach
                  </select>
                  <div class="pc-help">Only active bikes can be reassigned here. A disabled bike can stay attached only to preserve historical records.</div>
              </div>

              <div class="pc-field">
                  <label>Respondent (optional)</label>
                  <select class="pc-select" name="respondent_id">
                    <option value="">-</option>
                    @foreach($respondents as $r)
                      <option value="{{ $r->id }}" @selected((string)old('respondent_id', $spending->respondent_id) === (string)$r->id)>
                        {{ $r->name }}
                      </option>
                    @endforeach
                  </select>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn2" type="button" data-step-next>Proceed to Payment</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="pc-step" data-step="bike-edit-payment">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">2</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Payment details</span>
                <span class="pc-step-text">Update the date, reference, amount, and fee here.</span>
              </span>
            </span>
            <span style="display:flex;align-items:center;gap:10px">
              <span class="pc-step-meta" data-step-meta>Locked</span>
              <span class="pc-step-arrow">⌄</span>
            </span>
          </button>
          <div class="pc-step-body" data-step-body hidden>
            <div class="pc-step-panel">
              <div class="pc-field">
                  <label>Date</label>
                  <input class="pc-input" type="date" name="date" value="{{ old('date', optional($spending->date)->format('Y-m-d')) }}" required>
              </div>

              <div class="pc-field">
                  <label>Reference (MPESA code)</label>
                  <input class="pc-input" name="reference" required value="{{ old('reference', $spending->reference) }}">
              </div>

              <div class="pc-field">
                  <label>Amount</label>
                  <input class="pc-input" type="number" step="0.01" name="amount" value="{{ old('amount', $spending->amount) }}" required>
              </div>

              <div class="pc-field">
                  <label>Transaction Cost (MPESA fee)</label>
                  <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', $spending->transaction_cost ?? 0) }}">
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn2" type="button" data-step-next>Proceed to Notes</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="pc-step" data-step="bike-edit-notes">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">3</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Description and particulars</span>
                <span class="pc-step-text">Finish with the short description and maintenance particulars.</span>
              </span>
            </span>
            <span style="display:flex;align-items:center;gap:10px">
              <span class="pc-step-meta" data-step-meta>Locked</span>
              <span class="pc-step-arrow">⌄</span>
            </span>
          </button>
          <div class="pc-step-body" data-step-body hidden>
            <div class="pc-step-panel">
              <div class="pc-field">
                <label>Description (optional)</label>
                <input class="pc-input" name="description" value="{{ old('description', $spending->description) }}">
              </div>

              <div class="pc-field full">
                <label>Particulars (required for maintenance)</label>
                <textarea class="pc-textarea" name="particulars" rows="3">{{ old('particulars', $spending->particulars) }}</textarea>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn" type="submit">Update Bike Spending</button>
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
