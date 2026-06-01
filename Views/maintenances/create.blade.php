@extends('pettycash::layouts.app')

@section('title','Record Service - '.$bike->plate_no)

@push('styles')
<style>
    .hint{font-size:13px;color:#667085}
</style>
@endpush

@section('content')
<div class="form-wrap">

    <div class="form-header">
        <div>
            <h2>Record Service</h2>
            <div class="hint">Bike: <strong>{{ $bike->plate_no }}</strong></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.maintenances.show', [$bike->id, 'tab' => 'overview']) }}">Back</a>
            <a class="btn2" href="{{ route('petty.maintenances.index', ['tab' => 'history']) }}">History</a>
        </div>
    </div>

    @if($errors->any())
        <div class="err">
            <strong>Fix these errors:</strong>
            <ul style="margin:8px 0 0 18px">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form class="pc-form" method="POST" action="{{ route('petty.maintenances.service.store', $bike->id) }}">
            @csrf

            <div class="pc-workflow" data-workflow>
                <section class="pc-step" data-step="service-schedule" data-step-open="1" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Service schedule</span>
                                <span class="pc-step-text">Start with the service date and the next due date.</span>
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
                                    <label>Service Date</label>
                                    <input class="pc-input" type="date" name="service_date" value="{{ old('service_date', $defaultServiceDate ?? now()->format('Y-m-d')) }}" required>
                            </div>

                            <div class="pc-field">
                                    <label>Next Due Date (optional)</label>
                                    <input class="pc-input" type="date" name="next_due_date" value="{{ old('next_due_date') }}">
                                    <div class="pc-help">This updates the bike's next service due in schedule.</div>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn2" type="button" data-step-next>Proceed to Work Done</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pc-step" data-step="service-work">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">2</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Reference and work done</span>
                                <span class="pc-step-text">Add the service reference and a short note of the work completed.</span>
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
                                <label>Reference (optional)</label>
                                <input class="pc-input" type="text" name="reference" value="{{ old('reference') }}" placeholder="e.g. invoice no / ref code">
                            </div>

                            <div class="pc-field full">
                                <label>Work Done (optional)</label>
                                <textarea class="pc-textarea" name="work_done" rows="4" placeholder="Describe what was done...">{{ old('work_done') }}</textarea>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn2" type="button" data-step-next>Proceed to Cost</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pc-step" data-step="service-cost">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">3</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Cost</span>
                                <span class="pc-step-text">Finish with the amount, transaction cost, and save.</span>
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
                                    <label>Amount</label>
                                    <input class="pc-input" type="number" step="0.01" min="0" name="amount" value="{{ old('amount', '0') }}">
                            </div>

                            <div class="pc-field">
                                    <label>Transaction Cost</label>
                                    <input class="pc-input" type="number" step="0.01" min="0" name="transaction_cost" value="{{ old('transaction_cost', '0') }}">
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit">Save Service</button>
                                    <a class="btn2" href="{{ route('petty.maintenances.show', [$bike->id, 'tab' => 'services']) }}">View Services</a>
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
