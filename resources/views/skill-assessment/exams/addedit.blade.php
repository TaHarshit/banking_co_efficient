@extends('layouts.app')
@section('pagewisestyle')
@endsection
@section('pagewisescript')
@endsection
@section('customjs')
<style>
.tags-input-container {
    position: relative;
}
.tags-display {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 5px;
    min-height: 30px;
    padding: 5px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    background-color: #f8f9fa;
}
.tag-item {
    background-color: #0d6efd;
    color: white;
    padding: 2px 8px;
    border-radius: 15px;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.remove-tag {
    cursor: pointer;
    font-weight: bold;
    margin-left: 5px;
}
.remove-tag:hover {
    color: #ffcccc;
}
</style>

<script>
function addTag(inputId, displayId, hiddenId) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    const hidden = document.getElementById(hiddenId);
    
    if (input.value.trim() === '') return;
    
    // Check if tag already exists
    const existingTags = Array.from(display.querySelectorAll('.tag-item')).map(tag => 
        tag.textContent.replace('×', '').trim()
    );
    
    if (existingTags.includes(input.value.trim())) {
        input.value = '';
        return;
    }
    
    // Create new tag element
    const tagSpan = document.createElement('span');
    tagSpan.className = 'tag-item';
    tagSpan.innerHTML = input.value.trim() + ' <span class="remove-tag" onclick="removeTag(this)">×</span>';
    
    display.appendChild(tagSpan);
    
    // Update hidden field with JSON array
    updateHiddenField(displayId, hiddenId);
    
    // Clear input
    input.value = '';
}

function removeTag(element) {
    const tagItem = element.parentElement;
    const display = tagItem.parentElement;
    const hiddenInputId = display.id.replace('tags-display', 'tags').replace('-fr', '_fr');
    const hiddenInput = document.getElementById(hiddenInputId);
    
    tagItem.remove();
    updateHiddenField(display.id, hiddenInputId);
}

function updateHiddenField(displayId, hiddenId) {
    const display = document.getElementById(displayId);
    const hidden = document.getElementById(hiddenId);
    const tags = Array.from(display.querySelectorAll('.tag-item')).map(tag => 
        tag.textContent.replace('×', '').trim()
    );
    hidden.value = JSON.stringify(tags);
}

// Initialize existing tags on page load
document.addEventListener('DOMContentLoaded', function() {
    // English tags
    const tagsInput = document.getElementById('tags_input');
    if (tagsInput) {
        tagsInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag('tags_input', 'tags-display', 'tags');
            }
        });
    }
    
    // French tags
    const tagsFrInput = document.getElementById('tags_fr_input');
    if (tagsFrInput) {
        tagsFrInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag('tags_fr_input', 'tags-display-fr', 'tags_fr');
            }
        });
    }
    
    // Initialize hidden fields with existing tags
    updateHiddenField('tags-display', 'tags');
    updateHiddenField('tags-display-fr', 'tags_fr');
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
                                        <option value="">Select Exam Level</option>
                                        <option value="beginner" {{ isset($data) && $data->exam_level == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                        <option value="intermediate" {{ isset($data) && $data->exam_level == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                        <option value="advanced" {{ isset($data) && $data->exam_level == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                        <option value="expert" {{ isset($data) && $data->exam_level == 'expert' ? 'selected' : '' }}>Expert</option>
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
                                    <label for="tags"
                                        class="form-label">{{ __('messages.tags_en') ?? 'Tags (EN)' }}</label>
                                    <div class="tags-input-container">
                                        <div class="tags-display" id="tags-display">
                                            @if(isset($data) && !empty($data->tags))
                                                @foreach(json_decode($data->tags, true) as $tag)
                                                    <span class="tag-item">{{ $tag }} <span class="remove-tag" onclick="removeTag(this)">×</span></span>
                                                @endforeach
                                            @endif
                                        </div>
                                        <input type="text" name="tags_input" id="tags_input"
                                            class="form-control {{ $errors->has('tags') ? 'is-invalid' : '' }}"
                                            placeholder="Type and press Enter to add tags">
                                        <input type="hidden" name="tags" id="tags" 
                                            value="{{ isset($data) ? $data->tags : old('tags') }}">
                                    </div>
                                    @if ($errors->has('tags'))
                                        <div class="invalid-feedback">{{ $errors->first('tags') }}</div>
                                    @endif
                                </div>

                                {{-- Tags FR (Multiple) --}}
                                <div class="col-md-6 mt-3">
                                    <label for="tags_fr"
                                        class="form-label">{{ __('messages.tags_fr') ?? 'Tags (FR)' }}</label>
                                    <div class="tags-input-container">
                                        <div class="tags-display" id="tags-display-fr">
                                            @if(isset($data) && !empty($data->tags_fr))
                                                @foreach(json_decode($data->tags_fr, true) as $tag)
                                                    <span class="tag-item">{{ $tag }} <span class="remove-tag" onclick="removeTag(this)">×</span></span>
                                                @endforeach
                                            @endif
                                        </div>
                                        <input type="text" name="tags_fr_input" id="tags_fr_input"
                                            class="form-control {{ $errors->has('tags_fr') ? 'is-invalid' : '' }}"
                                            placeholder="Tapez et appuyez sur Entrée pour ajouter des balises">
                                        <input type="hidden" name="tags_fr" id="tags_fr" 
                                            value="{{ isset($data) ? $data->tags_fr : old('tags_fr') }}">
                                    </div>
                                    @if ($errors->has('tags_fr'))
                                        <div class="invalid-feedback">{{ $errors->first('tags_fr') }}</div>
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
