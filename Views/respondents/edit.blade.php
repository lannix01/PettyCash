@extends('pettycash::layouts.app')
@section('title','Edit Respondent')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Edit {{ old('name', $respondent->name) }}</h2>
      <div class="form-subtitle">Keep respondent contact details clean for payment records.</div>
    </div>
    <a class="btn2" href="{{ route('petty.respondents.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.respondents.update', $respondent->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-workflow" data-workflow>
        <section class="pc-step" data-step="respondent-edit-main" data-step-open="1" data-step-unlocked="1">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">1</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Core respondent details</span>
                <span class="pc-step-text">Keep the saved name clean before touching the optional fields.</span>
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
                <input class="pc-input" name="name" value="{{ old('name', $respondent->name) }}" required>
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn2" type="button" data-step-next>Proceed to Optional Fields</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="pc-step" data-step="respondent-edit-extra">
          <button class="pc-step-trigger" type="button" data-step-toggle>
            <span class="pc-step-trigger-main">
              <span class="pc-step-index">2</span>
              <span class="pc-step-copy">
                <span class="pc-step-title">Phone and category</span>
                <span class="pc-step-text">Update the optional contact fields only if they are useful.</span>
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
                <input class="pc-input" name="phone" value="{{ old('phone', $respondent->phone) }}">
              </div>

              <div class="pc-field full">
                <label>Category (optional)</label>
                <input class="pc-input" name="category" value="{{ old('category', $respondent->category) }}">
              </div>

              <div class="pc-step-actions">
                <div class="pc-step-actions-main">
                  <button class="btn" type="submit">Update Respondent</button>
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
