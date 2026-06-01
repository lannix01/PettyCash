@extends('pettycash::layouts.app')
@section('title','Edit Meal')

@section('content')
<div class="form-wrap">
  <div class="form-header">
    <div>
      <h2>Edit Meal (Lunch)</h2>
      <div class="form-subtitle">Update the meal record directly. Editing here does not stop on balance checks.</div>
    </div>
    <a class="btn2" href="{{ route('petty.meals.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.meals.update', $spending->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-workflow" data-workflow>
        <section class="pc-step" data-step="meal-edit-source" data-step-open="1" data-step-unlocked="1">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">1</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Batch and date</span>
                <span class="pc-step-text">Start with the batch allocation and payment date.</span>
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
                      {{ $b->batch_no ?? $b->id }} (Balance: {{ number_format((float)$b->available_balance, 2) }})
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="pc-field">
                  <label>Date Paid</label>
                  <input class="pc-input" type="date" name="date" value="{{ old('date', optional($spending->date)->format('Y-m-d')) }}" required>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn2" type="button" data-step-next>Proceed to Payment</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="pc-step" data-step="meal-edit-payment">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">2</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Payment details</span>
                <span class="pc-step-text">Update the reference, amount, and transaction cost here.</span>
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

        <section class="pc-step" data-step="meal-edit-note">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">3</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Description</span>
                <span class="pc-step-text">Finish with the required meal details and save.</span>
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
                <label>Details</label>
                <input class="pc-input" name="description" required value="{{ old('description', $spending->description) }}">
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn" type="submit">Update Meal</button>
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
