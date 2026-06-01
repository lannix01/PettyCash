@extends('pettycash::layouts.app')
@section('title','Edit Daily Meal Bill')

@php
    $selectedPeople = old('involved_respondent_ids', $dailySpending->respondents->pluck('id')->map(fn ($id) => (string) $id)->all());
    if (!is_array($selectedPeople)) {
        $selectedPeople = [];
    }
@endphp

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Edit Daily Meal Bill</h2>
            <div class="form-subtitle">Update the bill entry before it is paid.</div>
        </div>
        <a class="btn2" href="{{ route('petty.meals.daily.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.meals.daily.update', $dailySpending->id) }}">
            @csrf
            @method('PUT')

            <div class="pc-workflow" data-workflow data-workflow-unlock-all="1">
                <section class="pc-step" data-step="daily-edit-main" data-step-open="1" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Bill details</span>
                                <span class="pc-step-text">Keep the date, amount, people, and details complete.</span>
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
                                <input class="pc-input" type="date" name="spending_date" required value="{{ old('spending_date', optional($dailySpending->spending_date)->format('Y-m-d')) }}">
                            </div>

                            <div class="pc-field">
                                <label>Amount</label>
                                <input class="pc-input" type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $dailySpending->amount) }}">
                            </div>

                            <div class="pc-field full">
                                <label>People Involved</label>
                                <select class="pc-select" name="involved_respondent_ids[]" multiple required style="min-height:180px">
                                    @foreach($respondents as $r)
                                        <option value="{{ $r->id }}" @selected(in_array((string) $r->id, array_map('strval', $selectedPeople), true))>
                                            {{ $r->name }} @if($r->phone) ({{ $r->phone }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="pc-field full">
                                <label>Details</label>
                                <input class="pc-input" name="notes" required value="{{ old('notes', $dailySpending->notes) }}">
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit">Update Daily Bill</button>
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
