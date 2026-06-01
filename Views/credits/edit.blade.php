@extends('pettycash::layouts.app')
@section('title','Edit Credit')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Edit Credit</h2>
      <div class="form-subtitle">Update amount and fees so balances match the actual transaction.</div>
    </div>
    <a class="btn2" href="{{ route('petty.credits.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.credits.update', $credit->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-workflow" data-workflow>
        <section class="pc-step" data-step="credit-main" data-step-open="1" data-step-unlocked="1">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">1</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Transaction details</span>
                <span class="pc-step-text">Adjust the date, reference, and amount first.</span>
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
                <label>Date</label>
                <input class="pc-input" type="date" name="date" value="{{ old('date', optional($credit->date)->format('Y-m-d')) }}" required>
              </div>

              <div class="pc-field">
                <label>Reference (MPESA code)</label>
                <input class="pc-input" name="reference" value="{{ old('reference', $credit->reference) }}">
              </div>

              <div class="pc-field">
                <label>Amount</label>
                <input class="pc-input" type="number" step="0.01" name="amount" value="{{ old('amount', $credit->amount) }}" required>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn2" type="button" data-step-next>Proceed to Fees</button>
                </div>
                <div class="pc-step-actions-note">Finish the cost and note in the last section.</div>
              </div>
            </div>
          </div>
        </section>

        <section class="pc-step" data-step="credit-extra">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">2</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Fee and description</span>
                <span class="pc-step-text">Use this section for the MPESA cost and any short note.</span>
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
                <label>Transaction Cost</label>
                <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', $credit->transaction_cost ?? 0) }}">
              </div>

              <div class="pc-field full">
                <label>Description</label>
                <input class="pc-input" name="description" value="{{ old('description', $credit->description) }}">
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn" type="submit">Update Credit</button>
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
