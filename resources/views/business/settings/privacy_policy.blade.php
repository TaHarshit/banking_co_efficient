@extends('layouts.business')

@section('title', __('messages.privacy_policy_nav') ?? 'Privacy Policy')

@section('pagewisestyle')
<style>
    .tox-tinymce {
        border-radius: 8px !important;
        border: 1px solid #ced4da !important;
    }
    .policy-status-badge {
        font-size: 0.82rem;
        padding: 0.4rem 0.75rem;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
    <div class="pagetitle mb-4">
        <h1>{{ __('messages.privacy_policy_nav') ?? 'Privacy Policy' }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('business.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.privacy_policy_nav') ?? 'Privacy Policy' }}</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <!-- Informational Alert -->
                <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border-left: 5px solid #0284c7 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 bg-white text-primary shadow-sm">
                                <i class="bi bi-shield-lock-fill fs-3" style="color: #0284c7;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1" style="color: #0f172a;">{{ __('messages.privacy_policy_nav') ?? 'Privacy Policy' }} &bull; {{ $business->name }}</h5>
                                <p class="text-muted mb-2 mb-md-0" style="font-size: 0.95rem;">
                                    {{ __('messages.bank_privacy_policy_desc') ?? 'Customize the privacy policy shown to candidates and employees of your institution in the mobile application. If left empty, the global platform policy applies.' }}
                                </p>
                            </div>
                            <div class="d-none d-md-flex flex-column gap-2 text-end">
                                @if(!empty(trim(strip_tags($business->privacy_policy ?? ''))))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle policy-status-badge">
                                        <i class="bi bi-check-circle me-1"></i> 🇬🇧 {{ __('messages.custom_policy_active') ?? 'Custom English Active' }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border policy-status-badge">
                                        <i class="bi bi-info-circle me-1"></i> 🇬🇧 {{ __('messages.using_default_policy') ?? 'Default Platform English' }}
                                    </span>
                                @endif

                                @if(!empty(trim(strip_tags($business->privacy_policy_fr ?? ''))))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle policy-status-badge">
                                        <i class="bi bi-check-circle me-1"></i> 🇫🇷 {{ __('messages.custom_policy_active') ?? 'Custom French Active' }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border policy-status-badge">
                                        <i class="bi bi-info-circle me-1"></i> 🇫🇷 {{ __('messages.using_default_policy') ?? 'Default Platform French' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('business.privacy_policy.update') }}" method="POST" class="needs-validation">
                    @csrf

                    <!-- English Privacy Policy -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 p-0 fs-6 fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="fs-5">🇬🇧</span> {{ __('messages.privacy_policy') }}
                            </h5>
                            @if(!empty(trim(strip_tags($business->privacy_policy ?? ''))))
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i> {{ __('messages.custom_policy_active') ?? 'Custom Policy Active' }}
                                </span>
                            @else
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-info-circle me-1"></i> {{ __('messages.using_default_policy') ?? 'Default Platform Policy' }}
                                </span>
                            @endif
                        </div>
                        <div class="card-body pt-3">
                            <p class="text-muted small mb-3">
                                Provide your institution's English legal terms and data handling procedures. Leaving this empty causes the mobile app to show the standard platform policy.
                            </p>
                            <textarea name="privacy_policy" id="privacy_policy" rows="12" class="form-control tinymce-editor">{!! old('privacy_policy', $business->privacy_policy) !!}</textarea>
                            @if ($errors->has('privacy_policy'))
                                <div class="text-danger small mt-1">{{ $errors->first('privacy_policy') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- French Privacy Policy -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 p-0 fs-6 fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="fs-5">🇫🇷</span> {{ __('messages.privacy_policy_fr') }}
                            </h5>
                            @if(!empty(trim(strip_tags($business->privacy_policy_fr ?? ''))))
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i> {{ __('messages.custom_policy_active') ?? 'Custom Policy Active' }}
                                </span>
                            @else
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-info-circle me-1"></i> {{ __('messages.using_default_policy') ?? 'Default Platform Policy' }}
                                </span>
                            @endif
                        </div>
                        <div class="card-body pt-3">
                            <p class="text-muted small mb-3">
                                Fournissez la politique de confidentialité en français pour votre institution. Si ce champ est laissé vide, l'application affichera la politique standard de la plateforme.
                            </p>
                            <textarea name="privacy_policy_fr" id="privacy_policy_fr" rows="12" class="form-control tinymce-editor">{!! old('privacy_policy_fr', $business->privacy_policy_fr) !!}</textarea>
                            @if ($errors->has('privacy_policy_fr'))
                                <div class="text-danger small mt-1">{{ $errors->first('privacy_policy_fr') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Changes are immediately applied to the Setting API for your bank's candidates and employees.
                            </div>
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i> {{ __('messages.save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
