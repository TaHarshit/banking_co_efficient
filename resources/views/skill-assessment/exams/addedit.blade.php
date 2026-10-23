@extends('layouts.app')
@section('pagewisestyle')
<style>
.tags-input-wrapper {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    min-height: 38px;
    height: auto;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    cursor: text;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}

.tags-input-wrapper.form-control {
    background-image: none !important;
}

.tags-input-wrapper:focus-within {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.tags-input-wrapper.is-invalid {
    border-color: #dc3545;
}

.tags-input-wrapper.is-invalid:focus-within {
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

.tags-list {
    display: contents;
}

.tag-item {
    background-color: #0d6efd;
    color: #fff;
    padding: 2px 8px 2px 10px;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    line-height: 1.35;
    white-space: nowrap;
    user-select: none;
}

.tag-item .remove-tag {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    padding: 0;
    margin: 0;
    font-size: 1.1rem;
    line-height: 1;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    transition: color .15s ease, background-color .15s ease;
}

.tag-item .remove-tag:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.25);
}

.tag-inner-input {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    padding: 2px 4px;
    flex: 1 1 120px;
    min-width: 90px;
    font-size: 0.875rem;
    background: transparent;
    color: #212529;
    height: 28px;
}

.tag-inner-input::placeholder {
    color: #6c757d;
    font-size: 0.85rem;
}
</style>
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
<script>
function focusTagInput(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.focus();
    }
}

function addTag(listId, inputId, hiddenId) {
    const list = document.getElementById(listId);
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    
    if (!input || !list || !hidden) return;
    
    const value = input.value.trim().replace(/^,+|,+$/g, '');
    if (value === '') return;
    
    // Check if tag already exists (case-insensitive)
    const existingTags = Array.from(list.querySelectorAll('.tag-item')).map(tag => 
        (tag.getAttribute('data-value') || tag.textContent.replace('×', '')).trim().toLowerCase()
    );
    
    if (existingTags.includes(value.toLowerCase())) {
        input.value = '';
        return;
    }
    
    // Create new tag element
    const tagSpan = document.createElement('span');
    tagSpan.className = 'tag-item';
    tagSpan.setAttribute('data-value', value);
    
    const textNode = document.createTextNode(value);
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'remove-tag';
    removeBtn.innerHTML = '&times;';
    removeBtn.onclick = function(e) {
        removeTag(this, e);
    };
    
    tagSpan.appendChild(textNode);
    tagSpan.appendChild(removeBtn);
    list.appendChild(tagSpan);
    
    updateHiddenField(listId, hiddenId);
    input.value = '';
    
    // Update placeholder
    if (list.children.length > 0) {
        input.placeholder = (inputId.indexOf('fr') !== -1) ? 'Ajouter une balise...' : 'Add tag...';
    }
}

function removeTag(element, event) {
    if (event) {
        event.stopPropagation();
    }
    const tagItem = element.closest('.tag-item');
    if (!tagItem) return;
    
    const list = tagItem.parentElement;
    const isFr = list.id.indexOf('fr') !== -1;
    const hiddenId = isFr ? 'tags_fr' : 'tags';
    const inputId = isFr ? 'tags_fr_input' : 'tags_input';
    
    tagItem.remove();
    updateHiddenField(list.id, hiddenId);
    
    const input = document.getElementById(inputId);
    if (input && list.children.length === 0) {
        input.placeholder = isFr ? 'Tapez et appuyez sur Entrée pour ajouter des balises' : 'Type and press Enter to add tags';
    }
}

function updateHiddenField(listId, hiddenId) {
    const list = document.getElementById(listId);
    const hidden = document.getElementById(hiddenId);
    if (!list || !hidden) return;
    
    const tags = Array.from(list.querySelectorAll('.tag-item')).map(tag => 
        (tag.getAttribute('data-value') || tag.firstChild.textContent).trim()
    );
    hidden.value = tags.length > 0 ? JSON.stringify(tags) : '';
}

function setupTagInput(listId, inputId, hiddenId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTag(listId, inputId, hiddenId);
        } else if (e.key === 'Backspace' && input.value === '') {
            const list = document.getElementById(listId);
            if (list && list.lastElementChild) {
                const removeBtn = list.lastElementChild.querySelector('.remove-tag');
                if (removeBtn) {
                    removeTag(removeBtn);
                }
            }
        }
    });
    
    input.addEventListener('paste', function(e) {
        const pasteData = (e.clipboardData || window.clipboardData).getData('text');
        if (pasteData && pasteData.includes(',')) {
            e.preventDefault();
            const parts = pasteData.split(',');
            parts.forEach(function(part) {
                input.value = part;
                addTag(listId, inputId, hiddenId);
            });
            input.value = '';
        }
    });
    
    input.addEventListener('blur', function() {
        if (input.value.trim() !== '') {
            addTag(listId, inputId, hiddenId);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    setupTagInput('tags-list', 'tags_input', 'tags');
    setupTagInput('tags-list-fr', 'tags_fr_input', 'tags_fr');
    
    updateHiddenField('tags-list', 'tags');
    updateHiddenField('tags-list-fr', 'tags_fr');
    
    const form = document.querySelector('form.needs-validation');
    if (form) {
        form.addEventListener('submit', function() {
            addTag('tags-list', 'tags_input', 'tags');
            addTag('tags-list-fr', 'tags_fr_input', 'tags_fr');
        });
    }
});
</script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ isset($data) ? __('messages.edit_exam') ?? 'Edit Exam' : __('messages.add_exam') ?? 'Add Exam' }}
            </h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('manageskillassessmentexamtemplates') }}">{{ __('messages.skill_assessment') ?? 'Skill Assessment' }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ isset($data) ? __('messages.edit_exam') ?? 'Edit Exam' : __('messages.add_exam') ?? 'Add Exam' }}
                    </li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ isset($data) ? __('messages.edit_exam') ?? 'Edit Exam' : __('messages.add_exam') ?? 'Add Exam' }}
                            </h5>
                            <form class="row g-3 needs-validation" action="{{ route('storeskillassessmentexamtemplate') }}"
                                method="POST" novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ isset($data) ? $data->id : 0 }}">

                                {{-- Exam Title EN --}}
                                <div class="col-md-6 mt-3">
                                    <label for="title"
                                        class="form-label fw-bold">{{ __('messages.title_en') ?? 'Title (EN)' }}
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="title"
                                        class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" id="title"
                                        value="{{ isset($data) ? $data->title : old('title') }}"
                                        placeholder="e.g., Banking Knowledge Assessment" required>
                                    @if ($errors->has('title'))
                                        <div class="invalid-feedback">{{ $errors->first('title') }}</div>
                                    @endif
                                </div>

                                {{-- Exam Title FR --}}
                                <div class="col-md-6 mt-3">
                                    <label for="title_fr"
                                        class="form-label fw-bold">{{ __('messages.title_fr') ?? 'Title (FR)' }}</label>
                                    <input type="text" name="title_fr"
                                        class="form-control {{ $errors->has('title_fr') ? 'is-invalid' : '' }}"
                                        id="title_fr" value="{{ isset($data) ? $data->title_fr : old('title_fr') }}"
                                        placeholder="e.g., Évaluation des connaissances bancaires">
                                    @if ($errors->has('title_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('title_fr') }}</div>
                                    @endif
                                </div>

                                {{-- Exam Level EN --}}
                                <div class="col-md-6 mt-3">
                                    <label for="exam_level"
                                        class="form-label">{{ __('messages.exam_level_en') ?? 'Exam Level (EN)' }}</label>
                                    <select name="exam_level" id="exam_level"
                                        class="form-control {{ $errors->has('exam_level') ? 'is-invalid' : '' }}">
                                        <option value="">{{ __('messages.select_exam_level') }}</option>
                                        <option value="beginner" {{ isset($data) && $data->exam_level == 'beginner' ? 'selected' : '' }}>{{ __('messages.beginner') }}</option>
                                        <option value="intermediate" {{ isset($data) && $data->exam_level == 'intermediate' ? 'selected' : '' }}>{{ __('messages.intermediate') }}</option>
                                        <option value="advanced" {{ isset($data) && $data->exam_level == 'advanced' ? 'selected' : '' }}>{{ __('messages.advanced') }}</option>
                                        <option value="expert" {{ isset($data) && $data->exam_level == 'expert' ? 'selected' : '' }}>{{ __('messages.expert') }}</option>
                                    </select>
                                    @if ($errors->has('exam_level'))
                                        <div class="invalid-feedback">{{ $errors->first('exam_level') }}</div>
                                    @endif
                                </div>

                                {{-- Exam Level FR --}}
                                <div class="col-md-6 mt-3">
                                    <label for="exam_level_fr"
                                        class="form-label">{{ __('messages.exam_level_fr') ?? 'Exam Level (FR)' }}</label>
                                    <select name="exam_level_fr" id="exam_level_fr"
                                        class="form-control {{ $errors->has('exam_level_fr') ? 'is-invalid' : '' }}">
                                        <option value="">Sélectionner le niveau d'examen</option>
                                        <option value="débutant" {{ isset($data) && $data->exam_level_fr == 'débutant' ? 'selected' : '' }}>Débutant</option>
                                        <option value="intermédiaire" {{ isset($data) && $data->exam_level_fr == 'intermédiaire' ? 'selected' : '' }}>Intermédiaire</option>
                                        <option value="avancé" {{ isset($data) && $data->exam_level_fr == 'avancé' ? 'selected' : '' }}>Avancé</option>
                                        <option value="expert" {{ isset($data) && $data->exam_level_fr == 'expert' ? 'selected' : '' }}>Expert</option>
                                    </select>
                                    @if ($errors->has('exam_level_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('exam_level_fr') }}</div>
                                    @endif
                                </div>

                                {{-- Tags EN (Multiple) --}}
                                <div class="col-md-6 mt-3">
                                    <label for="tags_input"
                                        class="form-label">{{ __('messages.tags_en') ?? 'Tags (EN)' }}</label>
                                    @php
                                        $tagsRaw = old('tags', isset($data) ? ($data->getRawOriginal('tags') ?? $data->tags) : null);
                                        $tagsValue = is_string($tagsRaw) ? json_decode($tagsRaw, true) : $tagsRaw;
                                        if (!is_array($tagsValue)) { $tagsValue = []; }
                                        $tagsHidden = !empty($tagsValue) ? json_encode(array_values($tagsValue)) : '';
                                    @endphp
                                    <div class="tags-input-wrapper form-control {{ $errors->has('tags') ? 'is-invalid' : '' }}" onclick="focusTagInput('tags_input')">
                                        <div class="tags-list" id="tags-list">
                                            @foreach($tagsValue as $tag)
                                                <span class="tag-item" data-value="{{ $tag }}">{{ $tag }}<button type="button" class="remove-tag" onclick="removeTag(this, event)">&times;</button></span>
                                            @endforeach
                                        </div>
                                        <input type="text" id="tags_input" class="tag-inner-input"
                                            placeholder="{{ empty($tagsValue) ? 'Type and press Enter to add tags' : 'Add tag...' }}">
                                        <input type="hidden" name="tags" id="tags"
                                            value="{{ $tagsHidden }}">
                                    </div>
                                    @if ($errors->has('tags'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('tags') }}</div>
                                    @endif
                                </div>

                                {{-- Tags FR (Multiple) --}}
                                <div class="col-md-6 mt-3">
                                    <label for="tags_fr_input"
                                        class="form-label">{{ __('messages.tags_fr') ?? 'Tags (FR)' }}</label>
                                    @php
                                        $tagsFrRaw = old('tags_fr', isset($data) ? ($data->getRawOriginal('tags_fr') ?? $data->tags_fr) : null);
                                        $tagsFrValue = is_string($tagsFrRaw) ? json_decode($tagsFrRaw, true) : $tagsFrRaw;
                                        if (!is_array($tagsFrValue)) { $tagsFrValue = []; }
                                        $tagsFrHidden = !empty($tagsFrValue) ? json_encode(array_values($tagsFrValue)) : '';
                                    @endphp
                                    <div class="tags-input-wrapper form-control {{ $errors->has('tags_fr') ? 'is-invalid' : '' }}" onclick="focusTagInput('tags_fr_input')">
                                        <div class="tags-list" id="tags-list-fr">
                                            @foreach($tagsFrValue as $tag)
                                                <span class="tag-item" data-value="{{ $tag }}">{{ $tag }}<button type="button" class="remove-tag" onclick="removeTag(this, event)">&times;</button></span>
                                            @endforeach
                                        </div>
                                        <input type="text" id="tags_fr_input" class="tag-inner-input"
                                            placeholder="{{ empty($tagsFrValue) ? 'Tapez et appuyez sur Entrée pour ajouter des balises' : 'Ajouter une balise...' }}">
                                        <input type="hidden" name="tags_fr" id="tags_fr"
                                            value="{{ $tagsFrHidden }}">
                                    </div>
                                    @if ($errors->has('tags_fr'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('tags_fr') }}</div>
                                    @endif
                                </div>

                                {{-- Exam Description EN --}}
                                <div class="col-md-6 mt-3">
                                    <label for="description"
                                        class="form-label">{{ __('messages.description_en') ?? 'Description (EN)' }}</label>
                                    <textarea name="description" class="form-control" id="description" rows="3"
                                        placeholder="Optional description of this exam">{{ isset($data) ? $data->description : old('description') }}</textarea>
                                </div>

                                {{-- Exam Description FR --}}
                                <div class="col-md-6 mt-3">
                                    <label for="description_fr"
                                        class="form-label">{{ __('messages.description_fr') ?? 'Description (FR)' }}</label>
                                    <textarea name="description_fr" class="form-control" id="description_fr" rows="3"
                                        placeholder="Description facultative">{{ isset($data) ? $data->description_fr : old('description_fr') }}</textarea>
                                </div>

                                {{-- Duration --}}
                                <div class="col-md-4 position-relative mt-3">
                                    <label for="duration_minutes"
                                        class="form-label">{{ __('messages.duration_minutes') ?? 'Duration (minutes)' }}</label>
                                    <input type="number" name="duration_minutes"
                                        class="form-control {{ $errors->has('duration_minutes') ? 'is-invalid' : '' }}"
                                        id="duration_minutes"
                                        value="{{ isset($data) ? $data->duration_minutes : old('duration_minutes') }}"
                                        min="1" placeholder="Optional">
                                    @if ($errors->has('duration_minutes'))
                                        <div class="invalid-feedback">{{ $errors->first('duration_minutes') }}</div>
                                    @endif
                                </div>

                                {{-- Passing Percentage --}}
                                <div class="col-md-4 position-relative mt-3">
                                    <label for="passing_percentage"
                                        class="form-label">{{ __('messages.passing_percentage') ?? 'Passing Percentage' }}</label>
                                    <input type="number" name="passing_percentage"
                                        class="form-control {{ $errors->has('passing_percentage') ? 'is-invalid' : '' }}"
                                        id="passing_percentage"
                                        value="{{ isset($data) ? $data->passing_percentage : old('passing_percentage') }}"
                                        min="0" max="100" step="0.01" placeholder="Optional">
                                    @if ($errors->has('passing_percentage'))
                                        <div class="invalid-feedback">{{ $errors->first('passing_percentage') }}</div>
                                    @endif
                                </div>

                                {{-- Order --}}
                                <div class="col-md-4 position-relative mt-3">
                                    <label for="order" class="form-label">{{ __('messages.order') ?? 'Order' }} <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="order"
                                        class="form-control {{ $errors->has('order') ? 'is-invalid' : '' }}"
                                        id="order"
                                        value="{{ isset($data) ? $data->order : (isset($nextOrder) ? $nextOrder : 1) }}"
                                        min="1" required>
                                    @if ($errors->has('order'))
                                        <div class="invalid-feedback">{{ $errors->first('order') }}</div>
                                    @endif
                                </div>

                                {{-- Status --}}
                                <div class="col-md-4 position-relative mt-3">
                                    <label class="form-label">{{ __('messages.status') ?? 'Status' }}</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                            {{ (isset($data) && $data->is_active) || !isset($data) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                            for="is_active">{{ __('messages.active') ?? 'Active' }}</label>
                                    </div>
                                </div>

                                {{-- Submit Button --}}
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ isset($data) ? __('messages.save') ?? 'Save' : __('messages.add') ?? 'Add' }}
                                    </button>
                                    <a href="{{ route('manageskillassessmentexamtemplates') }}"
                                        class="btn btn-secondary">
                                        {{ __('messages.cancel') ?? 'Cancel' }}
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.footer')
@endsection
