@extends('pettycash::layouts.app')

@section('title','Edit Other Spending')

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Edit Other Spending</h2>
            <div class="form-subtitle">Update the saved record directly. Editing here does not stop on balance checks.</div>
        </div>
        <a class="btn2" href="{{ route('petty.others.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.others.update', $spending->id) }}">
            @csrf
            @method('PUT')

            <div class="pc-workflow" data-workflow data-workflow-unlock-all="1">
                <section class="pc-step" data-step="other-edit-main" data-step-open="1" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Record details</span>
                                <span class="pc-step-text">Choose the batch, then keep the transaction and description complete.</span>
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
                                        <option value="{{ $b->id }}" @selected((string) old('batch_id', $spending->batch_id) === (string) $b->id)>
                                            {{ $b->batch_no }} (Balance: {{ number_format((float) $b->available_balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="pc-field">
                                <label>Reference</label>
                                <input class="pc-input" name="reference" required value="{{ old('reference', $spending->reference) }}">
                            </div>

                            <div class="pc-field">
                                <label>Amount</label>
                                <input class="pc-input" type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $spending->amount) }}">
                            </div>

                            <div class="pc-field">
                                <label>Transaction Cost</label>
                                <input class="pc-input" type="number" step="0.01" min="0" name="transaction_cost" value="{{ old('transaction_cost', $spending->transaction_cost ?? 0) }}">
                            </div>

                            <div class="pc-field">
                                <label>Date</label>
                                <input class="pc-input" type="date" name="date" required value="{{ old('date', optional($spending->date)->format('Y-m-d')) }}">
                            </div>

                            <div class="pc-field">
                                <label>Respondent</label>
                                <select class="pc-select" name="respondent_id">
                                    <option value="">Select respondent</option>
                                    @foreach($respondents as $r)
                                        <option value="{{ $r->id }}" @selected((string) old('respondent_id', $spending->respondent_id) === (string) $r->id)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="pc-field full">
                                <label>Description</label>
                                <input class="pc-input" name="description" required value="{{ old('description', $spending->description) }}">
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit">Update Other Spending</button>
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
