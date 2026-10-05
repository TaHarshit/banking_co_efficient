@extends('layouts.business')

@section('title', __('messages.business_policies'))

@section('pagewisestyle')
    <style>
        .policy-section {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #ffffff;
            transition: border-color 0.15s ease;
        }
        .policy-section:hover {
            border-color: #ced4da;
        }
        .collapse-header {
            cursor: pointer;
            user-select: none;
            padding: 10px 16px;
            transition: background-color 0.15s ease-in-out;
            border-radius: 7px;
        }
        .collapse-header:hover {
            background-color: #f8f9fa;
        }
        .collapse-header:not(.collapsed) {
            border-bottom: 1px solid #f1f3f5;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
        .collapse-chevron {
            transition: transform 0.25s ease-in-out;
            font-size: 0.85rem;
        }
        .collapse-header.collapsed .collapse-chevron {
            transform: rotate(-90deg);
        }
        .point-item-row {
            background-color: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .point-item-row:hover {
            background-color: #ffffff;
            border-color: #cbd5e1;
        }
    </style>
@endsection

@section('content')
    <div class="pagetitle">
        <h1>{{ __('messages.business_policies') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.business_policies') }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @include('partials.messages')

                <!-- Card 1: Internal Business Policies - 3 App Sections (Bilingual EN/FR) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <h5 class="card-title m-0 p-0 text-primary">
                                <i class="bi bi-chat-square-quote me-2"></i>{{ __('messages.internal_business_policies_message') }}
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-phone me-1"></i> 3 App Policy Sections
                                </span>
                                <span class="badge bg-light text-dark border">
                                    🇬🇧 EN / 🇫🇷 FR
                                </span>
                                <span class="badge text-secondary">Optional</span>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            {{ __('messages.business_policies_message_hint') }}
                        </p>

                        <form action="{{ route('business.policies.message.update') }}" method="POST" id="policySectionsForm">
                            @csrf

                            @php
                                $sectionMeta = [
                                    0 => [
                                        'color' => 'primary',
                                        'badge_bg' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'placeholder_title_en' => 'e.g. 1. General Policy & Client Verification',
                                        'placeholder_title_fr' => 'ex. 1. Politique générale et vérification client',
                                        'point_placeholder_en' => 'e.g. Verify client identity and ensure mandatory KYC documentation is uploaded.',
                                        'point_placeholder_fr' => 'ex. Vérifier l\'identité du client et les documents KYC obligatoires.',
                                    ],
                                    1 => [
                                        'color' => 'info',
                                        'badge_bg' => 'bg-info-subtle text-info border border-info-subtle',
                                        'placeholder_title_en' => 'e.g. 2. Compliance & Due Diligence Requirements',
                                        'placeholder_title_fr' => 'ex. 2. Exigences de conformité et de diligence',
                                        'point_placeholder_en' => 'e.g. Cross-check client profile against AML/CFT risk criteria.',
                                        'point_placeholder_fr' => 'ex. Vérifier le profil client par rapport aux critères LBC/FT.',
                                    ],
                                    2 => [
                                        'color' => 'success',
                                        'badge_bg' => 'bg-success-subtle text-success border border-success-subtle',
                                        'placeholder_title_en' => 'e.g. 3. Case Submission & Approval Checklist',
                                        'placeholder_title_fr' => 'ex. 3. Liste de contrôle et soumission du cas',
                                        'point_placeholder_en' => 'e.g. Confirm all mandatory fields are completed before AI analysis.',
                                        'point_placeholder_fr' => 'ex. Confirmer tous les champs requis avant l\'analyse IA.',
                                    ],
                                ];

                                $hasActiveSections = false;
                                foreach ($sections as $s) {
                                    if (!empty($s['title_en']) || !empty($s['title_fr']) || !empty(array_filter($s['points'] ?? []))) {
                                        $hasActiveSections = true;
                                        break;
                                    }
                                }
                            @endphp

                            <!-- Collapsible Controls Bar -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 pb-2 border-bottom">
                                <div class="small text-muted">
                                    <i class="bi bi-arrows-expand me-1"></i> Click on any section header to expand or collapse.
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="expandAllSections()">
                                        <i class="bi bi-arrows-angle-expand me-1"></i> Expand All
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="collapseAllSections()">
                                        <i class="bi bi-arrows-angle-contract me-1"></i> Collapse All
                                    </button>
                                </div>
                            </div>

                            <div class="sections-accordion mb-3">
                                @for ($i = 0; $i < 3; $i++)
                                    @php
                                        $sec = $sections[$i] ?? ['id' => $i + 1, 'title_en' => '', 'title_fr' => '', 'points' => []];
                                        $titleEnVal = old("sections.$i.title_en", $sec['title_en'] ?? ($sec['title'] ?? ''));
                                        $titleFrVal = old("sections.$i.title_fr", $sec['title_fr'] ?? '');
                                        $pointsVal = old("sections.$i.points", $sec['points'] ?? []);
                                        if (!is_array($pointsVal)) { $pointsVal = []; }
                                        $meta = $sectionMeta[$i];

                                        $filledCount = 0;
                                        foreach ($pointsVal as $p) {
                                            $e = is_array($p) ? ($p['en'] ?? '') : (string)$p;
                                            $f = is_array($p) ? ($p['fr'] ?? '') : '';
                                            if (trim($e) !== '' || trim($f) !== '') {
                                                $filledCount++;
                                            }
                                        }
                                        $isFirstOpen = ($i === 0);
                                    @endphp
                                    <div class="policy-section mb-3">
                                        <div class="collapse-header d-flex justify-content-between align-items-center {{ $isFirstOpen ? '' : 'collapsed' }}"
                                             data-bs-toggle="collapse"
                                             data-bs-target="#section-collapse-{{ $i }}"
                                             aria-expanded="{{ $isFirstOpen ? 'true' : 'false' }}"
                                             aria-controls="section-collapse-{{ $i }}">
                                            <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                <span class="badge {{ $meta['badge_bg'] }} rounded-pill px-2 py-1 flex-shrink-0">
                                                    Section {{ $i + 1 }}
                                                </span>
                                                <span class="fw-semibold text-dark text-truncate title-preview" id="title-preview-{{ $i }}">
                                                    {{ $titleEnVal ?: ($titleFrVal ?: 'Empty Section (Click to expand & edit)') }}
                                                </span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                <span class="badge bg-light text-secondary border count-badge" id="count-{{ $i }}">
                                                    {{ $filledCount }} {{ $filledCount === 1 ? 'point' : 'points' }}
                                                </span>
                                                <i class="bi bi-chevron-down collapse-chevron text-muted"></i>
                                            </div>
                                        </div>

                                        <div id="section-collapse-{{ $i }}" class="collapse {{ $isFirstOpen ? 'show' : '' }} section-collapse-item">
                                            <div class="p-3">
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold text-dark mb-1">
                                                            🇬🇧 {{ __('messages.section_title') ?? 'Section Title' }} (EN)
                                                        </label>
                                                        <input type="text" name="sections[{{ $i }}][title_en]" 
                                                               class="form-control form-control-sm" 
                                                               placeholder="{{ $meta['placeholder_title_en'] }}"
                                                               value="{{ $titleEnVal }}"
                                                               oninput="updateTitlePreview({{ $i }})">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold text-dark mb-1">
                                                            🇫🇷 {{ __('messages.section_title') ?? 'Section Title' }} (FR)
                                                        </label>
                                                        <input type="text" name="sections[{{ $i }}][title_fr]" 
                                                               class="form-control form-control-sm" 
                                                               placeholder="{{ $meta['placeholder_title_fr'] }}"
                                                               value="{{ $titleFrVal }}"
                                                               oninput="updateTitlePreview({{ $i }})">
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="form-label small fw-bold text-dark m-0">
                                                        <i class="bi bi-list-check me-1 text-muted"></i>Point Messages (EN / FR)
                                                    </label>
                                                    <span class="text-muted small">Bullet points shown on mobile app</span>
                                                </div>

                                                <div id="points-container-{{ $i }}" class="points-container mb-3">
                                                    @forelse ($pointsVal as $pIdx => $point)
                                                        @php
                                                            $enVal = is_array($point) ? ($point['en'] ?? '') : (string)$point;
                                                            $frVal = is_array($point) ? ($point['fr'] ?? '') : '';
                                                        @endphp
                                                        <div class="point-item-row point-item">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small fw-semibold text-secondary point-label">
                                                                    <i class="bi bi-circle-fill text-{{ $meta['color'] }} me-1" style="font-size: 0.45rem; vertical-align: middle;"></i>Point #{{ $pIdx + 1 }}
                                                                </span>
                                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 py-0 px-2" onclick="removePoint(this, {{ $i }})" title="Remove point">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                            <div class="row g-2">
                                                                <div class="col-md-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text bg-light text-muted px-2 py-0 small fw-bold">🇬🇧</span>
                                                                        <input type="text" name="sections[{{ $i }}][points][{{ $pIdx }}][en]" 
                                                                               class="form-control" 
                                                                               placeholder="{{ $meta['point_placeholder_en'] }}"
                                                                               value="{{ $enVal }}"
                                                                               oninput="updatePointsCount({{ $i }})">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text bg-light text-muted px-2 py-0 small fw-bold">🇫🇷</span>
                                                                        <input type="text" name="sections[{{ $i }}][points][{{ $pIdx }}][fr]" 
                                                                               class="form-control" 
                                                                               placeholder="{{ $meta['point_placeholder_fr'] }}"
                                                                               value="{{ $frVal }}"
                                                                               oninput="updatePointsCount({{ $i }})">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="point-item-row point-item">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small fw-semibold text-secondary point-label">
                                                                    <i class="bi bi-circle-fill text-{{ $meta['color'] }} me-1" style="font-size: 0.45rem; vertical-align: middle;"></i>Point #1
                                                                </span>
                                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 py-0 px-2" onclick="removePoint(this, {{ $i }})" title="Remove point">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                            <div class="row g-2">
                                                                <div class="col-md-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text bg-light text-muted px-2 py-0 small fw-bold">🇬🇧</span>
                                                                        <input type="text" name="sections[{{ $i }}][points][0][en]" 
                                                                               class="form-control" 
                                                                               placeholder="{{ $meta['point_placeholder_en'] }}"
                                                                               value=""
                                                                               oninput="updatePointsCount({{ $i }})">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text bg-light text-muted px-2 py-0 small fw-bold">🇫🇷</span>
                                                                        <input type="text" name="sections[{{ $i }}][points][0][fr]" 
                                                                               class="form-control" 
                                                                               placeholder="{{ $meta['point_placeholder_fr'] }}"
                                                                               value=""
                                                                               oninput="updatePointsCount({{ $i }})">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforelse
                                                </div>

                                                <button type="button" class="btn btn-outline-{{ $meta['color'] }} btn-sm px-3 rounded-pill" onclick="addPoint({{ $i }})">
                                                    <i class="bi bi-plus-circle me-1"></i> Add Point
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                                <small class="text-muted">
                                    @if ($hasActiveSections || $business->business_policies_message)
                                        <i class="bi bi-check-circle-fill text-success me-1"></i> Bilingual policy sections are active and will be displayed in the app before case creation.
                                    @else
                                        <i class="bi bi-info-circle me-1"></i> No policy sections configured. Users will see standard case creation flow without this prompt.
                                    @endif
                                </small>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                        <i class="bi bi-save me-1"></i> {{ __('messages.save_message') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Card 2: Policy Questions -->
                <div class="card shadow-sm">
                    <div class="card-body pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="card-title m-0 p-0">
                                    <i class="bi bi-card-checklist me-2"></i>Policy Questions & Criteria
                                </h5>
                                <p class="text-muted small m-0">Questions and evaluation criteria used during case study analysis.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="bi bi-upload me-1"></i> {{ __('messages.import') }}
                                </button>
                                <a href="{{ route('business.policies.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> {{ __('messages.add_question') }}
                                </a>
                            </div>
                        </div>

                        @if (!$hasCustomQuestions)
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2 fs-5"></i>
                                <div>
                                    <strong>Default Global Policies Active:</strong> Your bank currently uses the system's global default policy questions ({{ $globalQuestionsCount }} questions). 
                                    Once you add or import customized questions here, your bank's case studies will automatically evaluate against your specific policies.
                                </div>
                            </div>
                        @endif

                        @if ($questions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('messages.section') }} (EN)</th>
                                            <th>{{ __('messages.section') }} (FR)</th>
                                            <th>{{ __('messages.question') }} (EN)</th>
                                            <th>{{ __('messages.question') }} (FR)</th>
                                            <th class="text-center">{{ __('messages.options') }}</th>
                                            <th class="text-center" width="130">{{ __('messages.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($questions as $question)
                                            <tr>
                                                <td><span class="fw-semibold">{{ $question->section_name_en ?: $question->section_name }}</span></td>
                                                <td><span class="fw-semibold">{{ $question->section_name_fr ?: $question->section_name }}</span></td>
                                                <td>{{ Str::limit($question->question_en, 50) }}</td>
                                                <td>{{ Str::limit($question->question_fr, 50) }}</td>
                                                <td class="text-center"><span class="badge bg-secondary rounded-pill">{{ $question->options->count() }}</span></td>
                                                <td class="text-center">
                                                    <a href="{{ route('business.policies.edit', $question->id) }}"
                                                        class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                    <a href="{{ route('business.policies.destroy', $question->id) }}"
                                                        onclick="return confirm('{{ __('messages.confirm_delete_policy_question') }}')"
                                                        class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                {{ $questions->links() }}
                            </div>
                        @else
                            <div class="text-center py-4 border rounded bg-light">
                                <i class="bi bi-shield-lock display-6 text-muted mb-2 d-block"></i>
                                <h6 class="text-muted">No custom policy questions yet.</h6>
                                <p class="text-muted small mb-3">Click "Add Question" to create your bank's specific policy questions, or "Import" from an Excel/CSV file.</p>
                                <a href="{{ route('business.policies.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Policy Question
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('business.policies.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('messages.import') }} {{ __('messages.business_policies') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Upload an Excel (.xlsx, .xls) or CSV file containing your bank's policy questions and options.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-upload me-1"></i> Upload & Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('customjs')
    <script type="text/javascript">
        const sectionMetaConfig = {
            0: {
                color: 'primary',
                placeholderEn: 'e.g. Verify client identity and ensure mandatory KYC documentation is uploaded.',
                placeholderFr: 'ex. Vérifier l\'identité du client et les documents KYC obligatoires.'
            },
            1: {
                color: 'info',
                placeholderEn: 'e.g. Cross-check client profile against AML/CFT risk criteria.',
                placeholderFr: 'ex. Vérifier le profil client par rapport aux critères LBC/FT.'
            },
            2: {
                color: 'success',
                placeholderEn: 'e.g. Confirm all mandatory fields are completed before AI analysis.',
                placeholderFr: 'ex. Confirmer tous les champs requis avant l\'analyse IA.'
            }
        };

        function addPoint(secIndex, enText = '', frText = '') {
            const container = document.getElementById(`points-container-${secIndex}`);
            if (!container) return;
            const meta = sectionMetaConfig[secIndex] || {
                color: 'primary',
                placeholderEn: 'Point in English...',
                placeholderFr: 'Point en français...'
            };
            const color = meta.color;
            const placeholderEn = meta.placeholderEn;
            const placeholderFr = meta.placeholderFr;
            const currentCount = container.querySelectorAll('.point-item').length;
            const nextIdx = currentCount;
            const div = document.createElement('div');
            div.className = 'point-item-row point-item';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-semibold text-secondary point-label">
                        <i class="bi bi-circle-fill text-${color} me-1" style="font-size: 0.45rem; vertical-align: middle;"></i>Point #${nextIdx + 1}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 py-0 px-2" onclick="removePoint(this, ${secIndex})" title="Remove point">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted px-2 py-0 small fw-bold">🇬🇧</span>
                            <input type="text" name="sections[${secIndex}][points][${nextIdx}][en]" 
                                   class="form-control" 
                                   placeholder="${placeholderEn}"
                                   value="${enText.replace(/"/g, '&quot;')}"
                                   oninput="updatePointsCount(${secIndex})">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted px-2 py-0 small fw-bold">🇫🇷</span>
                            <input type="text" name="sections[${secIndex}][points][${nextIdx}][fr]" 
                                   class="form-control" 
                                   placeholder="${placeholderFr}"
                                   value="${frText.replace(/"/g, '&quot;')}"
                                   oninput="updatePointsCount(${secIndex})">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(div);
            const input = div.querySelector('input');
            if (!enText && input) input.focus();
            renumberPoints(secIndex);
            updatePointsCount(secIndex);
        }

        function removePoint(btn, secIndex) {
            const container = document.getElementById(`points-container-${secIndex}`);
            if (!container) return;
            const rows = container.querySelectorAll('.point-item');
            if (rows.length > 1) {
                btn.closest('.point-item').remove();
            } else {
                const inputs = btn.closest('.point-item').querySelectorAll('input');
                inputs.forEach(inp => inp.value = '');
            }
            renumberPoints(secIndex);
            updatePointsCount(secIndex);
        }

        function renumberPoints(secIndex) {
            const container = document.getElementById(`points-container-${secIndex}`);
            if (!container) return;
            const items = container.querySelectorAll('.point-item');
            items.forEach((item, idx) => {
                const label = item.querySelector('.point-label');
                if (label) {
                    const dot = label.querySelector('i');
                    const dotClone = dot ? dot.cloneNode(true) : null;
                    label.innerHTML = '';
                    if (dotClone) {
                        label.appendChild(dotClone);
                        label.appendChild(document.createTextNode(' '));
                    }
                    label.appendChild(document.createTextNode(`Point #${idx + 1}`));
                }
            });
        }

        function updatePointsCount(secIndex) {
            const container = document.getElementById(`points-container-${secIndex}`);
            const badge = document.getElementById(`count-${secIndex}`);
            if (container && badge) {
                const items = container.querySelectorAll('.point-item');
                let count = 0;
                items.forEach(item => {
                    const inputs = item.querySelectorAll('input');
                    let hasVal = false;
                    inputs.forEach(inp => {
                        if (inp.value.trim() !== '') hasVal = true;
                    });
                    if (hasVal) count++;
                });
                badge.textContent = `${count} ${count === 1 ? 'point' : 'points'}`;
            }
        }

        function updateTitlePreview(secIndex) {
            const enInput = document.querySelector(`input[name="sections[${secIndex}][title_en]"]`);
            const frInput = document.querySelector(`input[name="sections[${secIndex}][title_fr]"]`);
            const preview = document.getElementById(`title-preview-${secIndex}`);
            if (preview) {
                const en = enInput ? enInput.value.trim() : '';
                const fr = frInput ? frInput.value.trim() : '';
                let label = '';
                if (en && fr) {
                    label = `${en} (${fr})`;
                } else {
                    label = en || fr || 'Empty Section (Click to expand & edit)';
                }
                preview.textContent = label;
            }
        }

        function expandAllSections() {
            for (let i = 0; i < 3; i++) {
                const el = document.getElementById(`section-collapse-${i}`);
                if (el && !el.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                    bsCollapse.show();
                }
            }
        }

        function collapseAllSections() {
            for (let i = 0; i < 3; i++) {
                const el = document.getElementById(`section-collapse-${i}`);
                if (el && el.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                    bsCollapse.hide();
                }
            }
        }
    </script>
@endsection

