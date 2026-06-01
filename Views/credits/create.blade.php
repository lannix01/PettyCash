@extends('pettycash::layouts.app')

@section('title','New Credit')

@section('content')
<div class="form-wrap form-wrap-sm">
    <div class="form-header">
        <div>
            <h2>New Credit</h2>
            <div class="form-subtitle">Create a new credit entry and auto-generate a fresh batch.</div>
        </div>
        <a class="btn2" href="{{ route('petty.credits.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.credits.store') }}">
            @csrf

            <div class="pc-workflow" data-workflow>
                <section class="pc-step" data-step="credit-core" data-step-open="1" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Credit basics</span>
                                <span class="pc-step-text">Capture the reference, amount, and date first.</span>
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
                                <label>Mpesa REF</label>
                                <input class="pc-input" name="reference" value="{{ old('reference') }}">
                            </div>

                            <div class="pc-field">
                                <label>Amount</label>
                                <input class="pc-input" type="number" step="0.01" name="amount" required value="{{ old('amount') }}">
                            </div>

                            <div class="pc-field">
                                <label>Date</label>
                                <input class="pc-input" type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}">
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn2" type="button" data-step-next>Proceed to Fees</button>
                                </div>
                                <div class="pc-step-actions-note">Step 2 is only for fee and note details.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pc-step" data-step="credit-meta">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">2</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Fees and note</span>
                                <span class="pc-step-text">Keep the transaction cost and optional note together.</span>
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
                                <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', 0) }}">
                            </div>

                            <div class="pc-field full">
                                <label>Description (optional)</label>
                                <textarea class="pc-textarea" name="description" rows="3">{{ old('description') }}</textarea>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit">Save Credit & Create Batch</button>
                                </div>
                                <div class="pc-step-actions-note">Saving this credit opens a fresh batch automatically.</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </div>
</div>
@endsection
