@extends('pettycash::layouts.app')
@section('title','Add Respondent')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Add Respondent</h2>
      <div class="form-subtitle">Create a reusable respondent for spendings and reports.</div>
    </div>
    <a class="btn2" href="{{ route('petty.respondents.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.respondents.store') }}">
      @csrf

      <div class="pc-workflow" data-workflow>
        <section class="pc-step" data-step="respondent-main" data-step-open="1" data-step-unlocked="1">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">1</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Respondent name</span>
                <span class="pc-step-text">Start with the name you will reuse in payments and reports.</span>
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
                <label>Name</label>
                <input class="pc-input" name="name" value="{{ old('name') }}" required>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn2" type="button" data-step-next>Proceed to Contact</button>
                </div>
                <div class="pc-step-actions-note">Phone and category stay in the last section.</div>
              </div>
            </div>
          </div>
        </section>

        <section class="pc-step" data-step="respondent-extra">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">2</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Contact and category</span>
                <span class="pc-step-text">Use these only where they help with payment records.</span>
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
                <label>Phone Number (optional)</label>
                <input class="pc-input" name="phone" value="{{ old('phone') }}">
              </div>

              <div class="pc-field">
                <label>Staff ID</label>
                <input class="pc-input" name="staff_id" value="{{ old('staff_id') }}" placeholder="Auto-generated if left blank">
              </div>

              <div class="pc-field">
                <label>Status</label>
                <select class="pc-select" name="status" required>
                  @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', \App\Modules\PettyCash\Models\Respondent::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>

              <div class="pc-field full">
                <label>Category</label>
                <select class="pc-select" name="category" required>
                  @foreach($categoryOptions as $category)
                    <option value="{{ $category }}" @selected(old('category', \App\Modules\PettyCash\Models\Respondent::CATEGORY_OTHER_STAFF) === $category)>{{ $category }}</option>
                  @endforeach
                </select>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn" type="submit">Save Respondent</button>
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
