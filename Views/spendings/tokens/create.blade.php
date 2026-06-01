@extends('pettycash::layouts.app')

@section('title','Add Hostel')

@push('styles')
<style>
    .status-banner{margin-top:12px;padding:12px;border-radius:12px;border:1px solid #d0d5dd;background:#f8fafc;color:#344054;font-size:13px}
    .status-banner.error{border-color:#fecdca;background:#fef3f2;color:#b42318}
    .status-banner.ok{border-color:#abefc6;background:#ecfdf3;color:#027a48}
    .ont-smart{position:relative}
    .ont-select-native{display:none}
    .ont-smart-trigger{width:100%;display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;background:#fff;color:#101828;font-size:13px;cursor:pointer;text-align:left}
    .ont-smart-trigger:disabled{background:#f9fafb;color:#98a2b3;cursor:not-allowed}
    .ont-smart-menu{position:absolute;z-index:40;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #d0d5dd;border-radius:12px;box-shadow:0 16px 30px rgba(16,24,40,.12);overflow:hidden}
    .ont-smart-search{width:100%;border:none;border-bottom:1px solid #eaecf0;padding:10px 12px;font-size:13px;outline:none}
    .ont-smart-list{max-height:280px;overflow:auto}
    .ont-smart-item{width:100%;border:none;background:#fff;text-align:left;padding:10px 12px;font-size:13px;color:#101828;cursor:pointer}
    .ont-smart-item:hover,.ont-smart-item.active{background:#eef4ff}
    .ont-smart-empty{padding:10px 12px;color:#667085;font-size:13px}
    .ont-smart-item-title{font-weight:800;color:#101828}
    .ont-smart-item-meta{margin-top:4px;display:grid;grid-template-columns:repeat(3,minmax(0,max-content));align-items:center;gap:8px}
    .ont-smart-item-site{font-size:12px;color:#667085}
    .ont-smart-item-sn{font-size:12px;color:#667085}
    .ont-status-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.02em;border:1px solid transparent}
    .ont-status-chip.success{background:#ecfdf3;border-color:#abefc6;color:#027a48}
    .ont-status-chip.muted{background:#f2f4f7;border-color:#d0d5dd;color:#475467}
    .ont-preview{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .ont-meta{border:1px solid #eaecf0;background:#fcfcfd;border-radius:12px;padding:10px}
    .ont-meta .label{font-size:11px;letter-spacing:.04em;font-weight:800;color:#667085;text-transform:uppercase}
    .ont-meta .value{margin-top:4px;font-size:14px;font-weight:800;color:#101828}
    .source-panel[hidden]{display:none !important}
    .inline-search-shell{position:relative}
    .inline-search-menu{position:absolute;z-index:40;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #d0d5dd;border-radius:12px;box-shadow:0 16px 30px rgba(16,24,40,.12);overflow:hidden}
    .inline-search-item{width:100%;border:none;background:#fff;text-align:left;padding:10px 12px;font-size:13px;color:#101828;cursor:pointer}
    .inline-search-item:hover{background:#eef4ff}
    .inline-search-title{font-weight:800;color:#101828}
    .inline-search-meta{margin-top:4px;display:flex;flex-wrap:wrap;gap:8px;font-size:12px;color:#667085}
    .source-mode-note{margin-top:8px;font-size:12px;color:#667085}
    .mode-choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .mode-choice{position:relative;display:block}
    .mode-choice input{
        position:absolute;
        top:14px;
        left:14px;
        width:20px;
        height:20px;
        margin:0;
        opacity:1;
        accent-color:#1849a9;
        cursor:pointer;
        z-index:2;
    }
    .mode-choice-card{
        display:grid;
        gap:6px;
        min-height:94px;
        padding:14px 14px 14px 46px;
        border:1px solid #d0d5dd;
        border-radius:16px;
        background:#fff;
        cursor:pointer;
        transition:.16s ease;
    }
    .mode-choice-card strong{font-size:14px;color:#101828}
    .mode-choice-card span{font-size:12px;line-height:1.6;color:#667085}
    .mode-choice input:checked + .mode-choice-card{
        border-color:#1849a9;
        background:#eef6ff;
        box-shadow:0 0 0 3px rgba(24,73,169,.12);
    }
    @media(max-width:900px){
        .mode-choice-grid{grid-template-columns:1fr}
        .ont-preview{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
    $ontHostels = (array) ($ontCatalog['hostels'] ?? []);
    $ontAvailable = (bool) ($ontCatalog['available'] ?? false);
    $ontMessage = (string) ($ontCatalog['message'] ?? '');
    $selectedOntKey = (string) old('ont_key', '');
    $selectedCreateMode = (string) old('create_mode', $chainingSupported ? '' : 'ont_site');
    $selectedChainParentId = (int) old('chained_from_hostel_id', 0);
    $selectedChainParentOntKey = (string) old('chained_from_ont_key', '');

    if ($selectedOntKey === '' && old('hostel_name')) {
        $normalizedOldHostel = strtoupper(trim((string) old('hostel_name')));
        foreach ($ontHostels as $candidate) {
            if (strtoupper(trim((string) ($candidate['hostel_name'] ?? ''))) === $normalizedOldHostel) {
                $selectedOntKey = (string) ($candidate['key'] ?? '');
                break;
            }
        }
    }
    if (!$chainingSupported && $selectedCreateMode === 'chained_hostel') {
        $selectedCreateMode = 'ont_site';
    }
@endphp
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Add Hostel</h2>
            <div class="form-subtitle">Step 1 of 2: Create the hostel from ONT or chain it from a main site, then set the agreement.</div>
        </div>
        <a class="btn2" href="{{ route('petty.tokens.index') }}">Back</a>
    </div>

    @if(!$ontAvailable)
        <div class="status-banner error">
            {{ $ontMessage !== '' ? $ontMessage : 'ONT directory is unavailable.' }}
            @if($chainingSupported)
                <div style="margin-top:4px">You can still create a chained hostel from a main ONT/site selection.</div>
            @endif
        </div>
    @endif

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.store') }}">
            @csrf

            <div class="pc-workflow" data-workflow>
                <section class="pc-step" data-step="ont" data-step-open="1" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Choose the hostel source</span>
                                <span class="pc-step-text">Pick an ONT site for a main hostel, or chain a mini hostel from a main ONT/site.</span>
                            </span>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px">
                            <span class="pc-step-meta" data-step-meta>Open</span>
                            <span class="pc-step-arrow">⌄</span>
                        </span>
                    </button>
                    <div class="pc-step-body" data-step-body>
                        <div class="pc-step-panel">
                            <div class="pc-field full">
                                <label>Create Mode</label>
                                <div class="mode-choice-grid">
                                    <label class="mode-choice" for="createModeOnt">
                                        <input type="radio" name="create_mode" id="createModeOnt" value="ont_site" required @checked($selectedCreateMode === 'ont_site')>
                                        <span class="mode-choice-card">
                                            <strong>From ONT Site</strong>
                                            <span>Create the hostel directly from the original ONT site record.</span>
                                        </span>
                                    </label>
                                    @if($chainingSupported)
                                        <label class="mode-choice" for="createModeChained">
                                            <input type="radio" name="create_mode" id="createModeChained" value="chained_hostel" required @checked($selectedCreateMode === 'chained_hostel')>
                                            <span class="mode-choice-card">
                                                <strong>Chained Hostel</strong>
                                                <span>Keep this hostel’s own details, but depend on an existing main site already in PettyCash.</span>
                                            </span>
                                        </label>
                                    @endif
                                </div>
                                <div class="pc-help">Choose exactly one source before continuing.</div>
                                @unless($chainingSupported)
                                    <div class="source-mode-note">Chained hostel mode appears here after the latest hostel migrations are run.</div>
                                @endunless
                            </div>

                            <div class="source-panel" id="ontSourcePanel" @if($selectedCreateMode !== 'ont_site') hidden @endif>
                                <div class="pc-field full">
                                <label>ONT / Site</label>
                                    <select class="pc-select ont-select-native" name="ont_key" id="ontKey" data-ont-available="{{ $ontAvailable ? '1' : '0' }}" @if(!$ontAvailable || $selectedCreateMode !== 'ont_site') disabled @endif @if($selectedCreateMode === 'ont_site') required @endif>
                                        <option value="">Select ONT site</option>
                                        @foreach($ontHostels as $candidate)
                                            @php
                                                $optKey = (string) ($candidate['key'] ?? '');
                                                $optName = (string) ($candidate['hostel_name'] ?? '');
                                                $optSiteId = (string) ($candidate['site_id'] ?? '');
                                                $optSiteSn = (string) ($candidate['site_sn'] ?? '');
                                                $optMergeStatus = (string) ($candidate['merge_status'] ?? 'unlinked');
                                                $optMergeLabel = (string) ($candidate['merge_status_label'] ?? 'Not Added');
                                                $optMergeTone = (string) ($candidate['merge_status_tone'] ?? 'muted');
                                            @endphp
                                            <option
                                                value="{{ $optKey }}"
                                                data-name="{{ $optName }}"
                                                data-site-id="{{ $optSiteId }}"
                                                data-site-sn="{{ $optSiteSn }}"
                                                data-merge-status="{{ $optMergeStatus }}"
                                                data-merge-status-label="{{ $optMergeLabel }}"
                                                data-merge-status-tone="{{ $optMergeTone }}"
                                                @selected($selectedOntKey === $optKey)
                                            >
                                                {{ $optName }} @if($optSiteId !== '') • Site {{ $optSiteId }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="ont-smart" id="ontSmartCreate" data-search-url="{{ route('petty.tokens.hostels.search', ['source' => 'ont_catalog'], false) }}"></div>
                                    <input type="hidden" name="hostel_name" id="hostelNameHidden" value="{{ $selectedCreateMode === 'ont_site' ? old('hostel_name') : '' }}" @if($selectedCreateMode !== 'ont_site') disabled @endif>
                                </div>

                                <div class="pc-step-summary">
                                    <div class="ont-preview">
                                        <div class="ont-meta">
                                            <div class="label">Selected Hostel</div>
                                            <div class="value" id="selectedHostelPreview">-</div>
                                        </div>
                                        <div class="ont-meta">
                                            <div class="label">Selected Site</div>
                                            <div class="value" id="selectedSitePreview">-</div>
                                        </div>
                                        <div class="ont-meta">
                                            <div class="label">Site S.N</div>
                                            <div class="value" id="selectedSnPreview">-</div>
                                        </div>
                                        <div class="ont-meta">
                                            <div class="label">Merge Status</div>
                                            <div class="value">
                                                <span class="ont-status-chip muted" id="selectedMergeStatus">Not Added</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="source-panel" id="chainSourcePanel" @if($selectedCreateMode !== 'chained_hostel') hidden @endif>
                                <div class="pc-field full">
                                    <label for="manualHostelName">Hostel Name</label>
                                    <input class="pc-input" id="manualHostelName" name="hostel_name" value="{{ $selectedCreateMode === 'chained_hostel' ? old('hostel_name') : '' }}" placeholder="e.g. Block B Annex" @if($selectedCreateMode !== 'chained_hostel') disabled @endif>
                                    <div class="pc-help">Keep the hostel details as entered here. The site will come from the selected main ONT/site.</div>
                                </div>

                                <div class="pc-field full">
                                    <label for="chainParentSearchInput">Chained From Main ONT/Site</label>
                                    <div class="inline-search-shell" id="chainParentShell" data-search-url="{{ route('petty.tokens.hostels.search', ['source' => 'chain_parent'], false) }}">
                                        <input class="pc-input" type="text" id="chainParentSearchInput" placeholder="Search main ONT/site, site id, or site serial" value="" @if($selectedCreateMode !== 'chained_hostel') disabled @endif>
                                        <div class="inline-search-menu" id="chainParentSearchMenu" hidden></div>
                                    </div>
                                    <input type="hidden" name="chained_from_hostel_id" id="chainedFromHostelId" value="{{ $selectedChainParentId > 0 ? $selectedChainParentId : '' }}">
                                    <input type="hidden" name="chained_from_ont_key" id="chainedFromOntKey" value="{{ $selectedChainParentOntKey }}">
                                    <div class="ajax-row" id="chainParentStatus" aria-live="polite"></div>
                                    <div class="pc-help">This search now comes from the main ONT catalogue. If the main hostel is not yet in PettyCash, it will be created automatically from the selected ONT/site.</div>
                                </div>

                                <div class="pc-step-summary">
                                    <div class="ont-preview">
                                        <div class="ont-meta">
                                            <div class="label">Main Hostel</div>
                                            <div class="value" id="chainParentHostelPreview">-</div>
                                        </div>
                                        <div class="ont-meta">
                                            <div class="label">Main Site</div>
                                            <div class="value" id="chainParentSitePreview">-</div>
                                        </div>
                                        <div class="ont-meta">
                                            <div class="label">Site S.N</div>
                                            <div class="value" id="chainParentSnPreview">-</div>
                                        </div>
                                        <div class="ont-meta">
                                            <div class="label">Routers</div>
                                            <div class="value" id="chainParentRoutersPreview">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn2" type="button" data-step-next>Proceed to Profile Details</button>
                                </div>
                                <div class="pc-step-actions-note">Use ONT mode for direct sites. Use chained mode for mini hostels that depend on an existing main site.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pc-step" data-step="details">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">2</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Add hostel details</span>
                                <span class="pc-step-text">Keep this short. Agreement setup comes immediately after saving.</span>
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
                                <label>Contact Person Name</label>
                                <input class="pc-input" name="contact_person" value="{{ old('contact_person') }}" required>
                            </div>

                            <div class="pc-field">
                                <label>Contact Person Number</label>
                                <input class="pc-input" name="phone_no" value="{{ old('phone_no') }}" required>
                            </div>

                            <div class="pc-field">
                                <label>No of Routers</label>
                                <input class="pc-input" id="noOfRoutersInput" type="number" min="1" name="no_of_routers" value="{{ old('no_of_routers', 1) }}" required>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit" data-loading-label="Creating Hostel..." data-loading-copy="Saving the hostel and opening the agreement builder.">Save and Continue to Agreement</button>
                                </div>
                                <div class="pc-step-actions-note">Saving here opens the agreement builder next.</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const createModeInputs = Array.from(document.querySelectorAll('input[name="create_mode"]'));
    const ontSelect = document.getElementById('ontKey');
    const ontSmart = document.getElementById('ontSmartCreate');
    const hostelNameHidden = document.getElementById('hostelNameHidden');
    const hostelPreview = document.getElementById('selectedHostelPreview');
    const sitePreview = document.getElementById('selectedSitePreview');
    const snPreview = document.getElementById('selectedSnPreview');
    const mergeStatusPreview = document.getElementById('selectedMergeStatus');
    const ontSourcePanel = document.getElementById('ontSourcePanel');
    const chainSourcePanel = document.getElementById('chainSourcePanel');
    const manualHostelName = document.getElementById('manualHostelName');
    const chainParentShell = document.getElementById('chainParentShell');
    const chainParentSearchInput = document.getElementById('chainParentSearchInput');
    const chainParentSearchMenu = document.getElementById('chainParentSearchMenu');
    const chainedFromHostelId = document.getElementById('chainedFromHostelId');
    const chainedFromOntKey = document.getElementById('chainedFromOntKey');
    const chainParentStatus = document.getElementById('chainParentStatus');
    const chainParentHostelPreview = document.getElementById('chainParentHostelPreview');
    const chainParentSitePreview = document.getElementById('chainParentSitePreview');
    const chainParentSnPreview = document.getElementById('chainParentSnPreview');
    const chainParentRoutersPreview = document.getElementById('chainParentRoutersPreview');
    const ontSearchAvailable = String(ontSelect.dataset.ontAvailable || '0') === '1';
    const searchUrl = ontSmart ? String(ontSmart.dataset.searchUrl || '') : '';
    const chainSearchUrl = chainParentShell ? String(chainParentShell.dataset.searchUrl || '') : '';

    if (createModeInputs.length === 0 || !ontSelect || !ontSmart || !hostelNameHidden || !hostelPreview || !sitePreview || !snPreview || !mergeStatusPreview || !searchUrl) {
        return;
    }

    const chainParentCache = new Map();
    let chainParentRequest = 0;

    function syncStatusChip(label, tone) {
        mergeStatusPreview.textContent = label || 'Not Added';
        mergeStatusPreview.classList.remove('success', 'muted');
        mergeStatusPreview.classList.add(tone === 'success' ? 'success' : 'muted');
    }

    function syncFromSelected() {
        const selected = ontSelect.options[ontSelect.selectedIndex];
        if (!selected || !selected.value) {
            hostelNameHidden.value = '';
            hostelPreview.textContent = '-';
            sitePreview.textContent = '-';
            snPreview.textContent = '-';
            syncStatusChip('Not Added', 'muted');
            return;
        }

        const hostelName = selected.dataset.name || '';
        const siteId = selected.dataset.siteId || '';
        const siteSn = selected.dataset.siteSn || '';
        const mergeLabel = selected.dataset.mergeStatusLabel || 'Not Added';
        const mergeTone = selected.dataset.mergeStatusTone || 'muted';

        hostelNameHidden.value = hostelName;
        hostelPreview.textContent = hostelName || '-';
        sitePreview.textContent = siteId !== '' ? ('Site ' + siteId) : 'No site id';
        snPreview.textContent = siteSn !== '' ? siteSn : '-';
        syncStatusChip(mergeLabel, mergeTone);
    }

    function clearChainParentSelection(message) {
        if (chainedFromHostelId) chainedFromHostelId.value = '';
        if (chainedFromOntKey) chainedFromOntKey.value = '';
        if (chainParentSearchInput) {
            chainParentSearchInput.dataset.selectedId = '';
            chainParentSearchInput.dataset.selectedKey = '';
        }
        if (chainParentHostelPreview) chainParentHostelPreview.textContent = '-';
        if (chainParentSitePreview) chainParentSitePreview.textContent = '-';
        if (chainParentSnPreview) chainParentSnPreview.textContent = '-';
        if (chainParentRoutersPreview) chainParentRoutersPreview.textContent = '-';
        if (chainParentStatus) {
            chainParentStatus.textContent = message || '';
            chainParentStatus.className = 'ajax-row';
        }
    }

    function applyChainParentSelection(item) {
        if (!item) {
            clearChainParentSelection('');
            return;
        }

        const selectedId = String(item.id || '');
        const selectedKey = String(item.ont_key || '');
        if (selectedId !== '' || selectedKey !== '') {
            chainParentCache.set(selectedId !== '' ? selectedId : selectedKey, item);
        }

        if (chainedFromHostelId) chainedFromHostelId.value = selectedId;
        if (chainedFromOntKey) chainedFromOntKey.value = selectedKey;
        if (chainParentSearchInput) {
            chainParentSearchInput.value = String(item.hostel_name || '');
            chainParentSearchInput.dataset.selectedId = selectedId;
            chainParentSearchInput.dataset.selectedKey = selectedKey;
            chainParentSearchInput.setCustomValidity('');
        }
        if (chainParentHostelPreview) chainParentHostelPreview.textContent = String(item.hostel_name || '-');
        if (chainParentSitePreview) chainParentSitePreview.textContent = item.site_id ? ('Site ' + item.site_id) : '-';
        if (chainParentSnPreview) chainParentSnPreview.textContent = String(item.site_sn || '-');
        if (chainParentRoutersPreview) {
            const routers = Number(item.no_of_routers || 0);
            chainParentRoutersPreview.textContent = Number.isFinite(routers) ? String(routers) : '-';
        }
        if (chainParentStatus) {
            chainParentStatus.textContent = String(item.source_label || 'Main ONT/site selected.');
            chainParentStatus.className = 'ajax-row ok';
        }
    }

    function updateChainParentValidity() {
        if (!chainParentSearchInput) return;
        const selectedHostelId = String(chainedFromHostelId?.value || '');
        const selectedOntKey = String(chainedFromOntKey?.value || '');
        if (getCreateModeValue() === 'chained_hostel' && selectedHostelId === '' && selectedOntKey === '') {
            chainParentSearchInput.setCustomValidity('Select the main ONT/site this record is chained from.');
        } else {
            chainParentSearchInput.setCustomValidity('');
        }
    }

    function getCreateModeValue() {
        const checked = createModeInputs.find((input) => input.checked);
        return checked ? String(checked.value || '') : '';
    }

    function setChainSearchMessage(message, tone) {
        if (!chainParentStatus) return;
        chainParentStatus.textContent = message || '';
        chainParentStatus.className = tone ? ('ajax-row ' + tone) : 'ajax-row';
    }

    function renderChainParentItems(items) {
        if (!chainParentSearchMenu) return;
        chainParentSearchMenu.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ont-smart-empty';
            empty.textContent = 'No main ONT/site found.';
            chainParentSearchMenu.appendChild(empty);
            chainParentSearchMenu.hidden = false;
            return;
        }

        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inline-search-item';

            const title = document.createElement('div');
            title.className = 'inline-search-title';
            title.textContent = String(item.hostel_name || '-');

            const meta = document.createElement('div');
            meta.className = 'inline-search-meta';
            meta.innerHTML = [
                item.site_id ? ('Site ' + item.site_id) : 'No site id',
                item.site_sn ? ('S.N ' + item.site_sn) : 'No site serial',
                'Routers ' + String(item.no_of_routers ?? 0),
                String(item.source_label || ''),
            ].filter(Boolean).map((entry) => '<span>' + entry + '</span>').join('');

            btn.appendChild(title);
            btn.appendChild(meta);
            btn.addEventListener('click', function () {
                applyChainParentSelection(item);
                if (chainParentSearchMenu) chainParentSearchMenu.hidden = true;
            });

            chainParentSearchMenu.appendChild(btn);
        });

        chainParentSearchMenu.hidden = false;
    }

    async function fetchChainParentByIds(ids) {
        if (!chainSearchUrl || !ids.length) return [];

        const url = new URL(chainSearchUrl, window.location.origin);
        if (ids.length === 1 && String(ids[0] || '').startsWith('site:')) {
            url.searchParams.set('ont_key', String(ids[0] || ''));
        } else {
            url.searchParams.set('ids', ids.join(','));
        }
        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(String(payload.message || 'Unable to load main ONT/site.'));
        }
        return Array.isArray(payload.hostels) ? payload.hostels : [];
    }

    async function searchChainParents(query) {
        if (!chainSearchUrl) return;
        const requestNo = ++chainParentRequest;
        const url = new URL(chainSearchUrl, window.location.origin);
        url.searchParams.set('q', query);
        url.searchParams.set('limit', '20');
        setChainSearchMessage('Searching main ONT/sites...', '');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (requestNo !== chainParentRequest) return;

            if (!response.ok) {
                setChainSearchMessage(String(payload.message || 'Search failed.'), 'error');
                if (chainParentSearchMenu) chainParentSearchMenu.hidden = true;
                return;
            }

            const items = Array.isArray(payload.hostels) ? payload.hostels : [];
            items.forEach((item) => {
                const id = String(item.id || '');
                const key = String(item.ont_key || '');
                if (id !== '' || key !== '') {
                    chainParentCache.set(id !== '' ? id : key, item);
                }
            });
            renderChainParentItems(items);
            setChainSearchMessage(String(payload.message || ''), items.length ? 'ok' : '');
        } catch (error) {
            if (requestNo !== chainParentRequest) return;
            setChainSearchMessage(error && error.message ? error.message : 'Search failed.', 'error');
            if (chainParentSearchMenu) chainParentSearchMenu.hidden = true;
        }
    }

    function buildSmartSelect(select, mountNode) {
        const defaultLabel = 'Search ONT site by name';
        let activeRequest = 0;

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ont-smart-trigger';
        trigger.disabled = select.disabled;

        const triggerLabel = document.createElement('span');
        const triggerIcon = document.createElement('span');
        triggerIcon.textContent = '▾';
        trigger.appendChild(triggerLabel);
        trigger.appendChild(triggerIcon);

        const menu = document.createElement('div');
        menu.className = 'ont-smart-menu';
        menu.hidden = true;

        const search = document.createElement('input');
        search.type = 'text';
        search.className = 'ont-smart-search';
        search.placeholder = 'Search site name...';

        const list = document.createElement('div');
        list.className = 'ont-smart-list';

        menu.appendChild(search);
        menu.appendChild(list);
        mountNode.appendChild(trigger);
        mountNode.appendChild(menu);

        function upsertSelectOption(item) {
            const value = String(item.key || '');
            if (value === '') return null;

            const label = String(item.hostel_name || '') + (item.site_id ? (' • Site ' + item.site_id) : '');
            let option = Array.from(select.options).find((row) => row.value === value);
            if (!option) {
                option = new Option(label, value, false, false);
                select.add(option);
            } else {
                option.textContent = label;
            }

            option.dataset.name = String(item.hostel_name || '');
            option.dataset.siteId = String(item.site_id || '');
            option.dataset.siteSn = String(item.site_sn || '');
            option.dataset.mergeStatus = String(item.merge_status || 'unlinked');
            option.dataset.mergeStatusLabel = String(item.merge_status_label || 'Not Added');
            option.dataset.mergeStatusTone = String(item.merge_status_tone || 'muted');

            return option;
        }

        function renderHint(text) {
            list.innerHTML = '';
            const row = document.createElement('div');
            row.className = 'ont-smart-empty';
            row.textContent = text;
            list.appendChild(row);
        }

        function renderItems(items) {
            list.innerHTML = '';
            if (!Array.isArray(items) || items.length === 0) {
                renderHint('No site found');
                return;
            }

            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ont-smart-item';
                if (select.value === String(item.key || '')) {
                    btn.classList.add('active');
                }

                const title = document.createElement('div');
                title.className = 'ont-smart-item-title';
                title.textContent = String(item.hostel_name || item.site_name || '-');

                const meta = document.createElement('div');
                meta.className = 'ont-smart-item-meta';

                const site = document.createElement('span');
                site.className = 'ont-smart-item-site';
                site.textContent = item.site_id ? ('Site ' + item.site_id) : 'No site id';

                const sn = document.createElement('span');
                sn.className = 'ont-smart-item-sn';
                sn.textContent = 'S.N ' + String(item.site_sn || '-');

                const status = document.createElement('span');
                status.className = 'ont-status-chip ' + (String(item.merge_status_tone || 'muted') === 'success' ? 'success' : 'muted');
                status.textContent = String(item.merge_status_label || 'Not Added');

                meta.appendChild(site);
                meta.appendChild(sn);
                meta.appendChild(status);
                btn.appendChild(title);
                btn.appendChild(meta);

                btn.addEventListener('click', function () {
                    const option = upsertSelectOption(item);
                    if (!option) return;
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    menu.hidden = true;
                    updateTriggerLabel();
                });

                list.appendChild(btn);
            });
        }

        async function fetchAndRender(query) {
            const requestNo = ++activeRequest;
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '40');
            renderHint('Searching ONT sites...');

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));
                if (requestNo !== activeRequest) return;

                if (!response.ok) {
                    renderHint(String(payload.message || 'Search failed'));
                    return;
                }

                renderItems(Array.isArray(payload.hostels) ? payload.hostels : (Array.isArray(payload.onts) ? payload.onts : []));
            } catch (error) {
                if (requestNo !== activeRequest) return;
                const detail = error && error.message ? (': ' + error.message) : '. Try again.';
                renderHint('Search failed' + detail);
            }
        }

        function updateTriggerLabel() {
            const selected = select.options[select.selectedIndex];
            triggerLabel.textContent = selected && selected.value ? selected.textContent.trim() : defaultLabel;
        }

        async function onSearchInput() {
            const q = search.value.trim();
            if (q.length < 2) {
                renderHint('Type 2+ characters');
                return;
            }
            await fetchAndRender(q);
        }

        trigger.addEventListener('click', function () {
            if (trigger.disabled) return;
            menu.hidden = !menu.hidden;
            if (!menu.hidden) {
                if (search.value.trim().length < 2) {
                    renderHint('Type 2+ characters');
                } else {
                    fetchAndRender(search.value.trim());
                }
                search.focus();
            }
        });

        search.addEventListener('input', onSearchInput);
        search.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const firstOption = list.querySelector('.ont-smart-item');
            if (firstOption) {
                firstOption.click();
            }
        });

        document.addEventListener('click', function (event) {
            if (!mountNode.contains(event.target)) {
                menu.hidden = true;
            }
        });

        select.addEventListener('change', updateTriggerLabel);
        updateTriggerLabel();
        renderHint('Type 2+ characters');

        return {
            setDisabled(disabled) {
                trigger.disabled = !!disabled;
                if (disabled) {
                    menu.hidden = true;
                }
            },
            activate() {
                if (trigger.disabled) return;
                menu.hidden = false;
                renderHint('Type 2+ characters');
                search.focus();
            },
        };
    }

    const smartSelect = buildSmartSelect(ontSelect, ontSmart);
    ontSelect.addEventListener('change', syncFromSelected);
    syncFromSelected();

    function syncSourceMode() {
        const selectedMode = getCreateModeValue();
        const hasSelection = selectedMode !== '';
        const isOntMode = selectedMode === 'ont_site';

        if (ontSourcePanel) ontSourcePanel.hidden = !isOntMode;
        if (chainSourcePanel) chainSourcePanel.hidden = selectedMode !== 'chained_hostel';

        if (ontSelect) {
            ontSelect.disabled = !isOntMode || !ontSearchAvailable || !searchUrl;
            ontSelect.required = isOntMode && ontSearchAvailable;
        }
        if (smartSelect) {
            smartSelect.setDisabled(ontSelect.disabled);
        }
        if (hostelNameHidden) {
            hostelNameHidden.disabled = !isOntMode;
        }

        if (manualHostelName) {
            manualHostelName.disabled = !hasSelection || isOntMode;
            manualHostelName.required = selectedMode === 'chained_hostel';
        }
        if (chainParentSearchInput) {
            chainParentSearchInput.disabled = !hasSelection || isOntMode;
            if (!hasSelection || isOntMode) {
                chainParentSearchInput.setCustomValidity('');
            }
        }

        if (!hasSelection) {
            if (smartSelect) {
                smartSelect.setDisabled(true);
            }
            if (hostelNameHidden) {
                hostelNameHidden.disabled = true;
            }
            return;
        }

        if (isOntMode) {
            if (manualHostelName) {
                manualHostelName.setCustomValidity('');
            }
            updateChainParentValidity();
            if (smartSelect) {
                window.requestAnimationFrame(() => smartSelect.activate());
            }
            return;
        }

        if (ontSelect) {
            ontSelect.setCustomValidity('');
        }
        updateChainParentValidity();
    }

    if (chainParentSearchInput && chainParentSearchMenu) {
        chainParentSearchInput.addEventListener('input', function () {
            if (getCreateModeValue() !== 'chained_hostel') return;
            const query = chainParentSearchInput.value.trim();
            const selectedId = String(chainedFromHostelId?.value || '');
            const selectedKey = String(chainedFromOntKey?.value || '');
            const cacheKey = selectedId !== '' ? selectedId : selectedKey;
            const selectedLabel = cacheKey !== ''
                ? String((chainParentCache.get(cacheKey) || {}).hostel_name || '')
                : '';

            if (selectedLabel === '' || query !== selectedLabel) {
                clearChainParentSelection('');
            }

            updateChainParentValidity();

            if (query.length < 2) {
                chainParentSearchMenu.hidden = true;
                setChainSearchMessage(query === '' ? '' : 'Type 2+ characters to search.', '');
                return;
            }

            searchChainParents(query);
        });

        chainParentSearchInput.addEventListener('focus', function () {
            const query = chainParentSearchInput.value.trim();
            if (getCreateModeValue() !== 'chained_hostel') return;
            if (query.length >= 2) {
                searchChainParents(query);
            }
        });

        chainParentSearchInput.addEventListener('blur', function () {
            updateChainParentValidity();
        });

        document.addEventListener('click', function (event) {
            if (!chainParentShell || chainParentShell.contains(event.target)) return;
            chainParentSearchMenu.hidden = true;
        });
    }

    createModeInputs.forEach((input) => input.addEventListener('change', syncSourceMode));
    syncSourceMode();

    const oldChainParentRef = String(chainedFromHostelId?.value || chainedFromOntKey?.value || '');
    if (oldChainParentRef !== '') {
        fetchChainParentByIds([oldChainParentRef])
            .then(function (items) {
                const match = Array.isArray(items)
                    ? items.find((item) => {
                        const itemId = String(item.id || '');
                        const itemKey = String(item.ont_key || '');
                        return itemId === oldChainParentRef || itemKey === oldChainParentRef;
                    })
                    : null;
                if (match) {
                    applyChainParentSelection(match);
                    return;
                }

                clearChainParentSelection('Main ONT/site could not be loaded. Search again.');
                setChainSearchMessage('Main ONT/site could not be loaded. Search again.', 'error');
            })
            .catch(function (error) {
                clearChainParentSelection('');
                setChainSearchMessage(error && error.message ? error.message : 'Main ONT/site could not be loaded.', 'error');
            })
            .finally(function () {
                updateChainParentValidity();
            });
    }
})();
</script>
@endpush
