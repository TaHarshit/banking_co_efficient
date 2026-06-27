@extends('layouts.app')
@include('partials.headerfiles')
@include('partials.footerfiles')

@section('pagewisestyle')
    <style>
        .validation-checklist {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
            font-size: 0.85rem;
        }

        .validation-checklist li {
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            transition: color 0.2s ease;
        }

        .validation-checklist li.valid {
            color: #198754;
        }

        .validation-checklist li i {
            font-size: 1rem;
        }
    </style>
@endsection

@section('content')
    <script>
        window.togglePasswordVisibility = function(e) {
            if (e) e.preventDefault();
            var passwordInput = document.getElementById('password');
            var togglePasswordIcon = document.getElementById('togglePasswordIcon');
            if (passwordInput) {
                var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                if (togglePasswordIcon) {
                    if (togglePasswordIcon.classList.contains('bi-eye')) {
                        togglePasswordIcon.classList.remove('bi-eye');
                        togglePasswordIcon.classList.add('bi-eye-slash');
                    } else {
                        togglePasswordIcon.classList.remove('bi-eye-slash');
                        togglePasswordIcon.classList.add('bi-eye');
                    }
                }
            }
        };

        window.toggleConfirmPasswordVisibility = function(e) {
            if (e) e.preventDefault();
            var confirmInput = document.getElementById('password_confirmation');
            var toggleConfirmIcon = document.getElementById('toggleConfirmPasswordIcon');
            if (confirmInput) {
                var type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmInput.setAttribute('type', type);
                if (toggleConfirmIcon) {
                    if (toggleConfirmIcon.classList.contains('bi-eye')) {
                        toggleConfirmIcon.classList.remove('bi-eye');
                        toggleConfirmIcon.classList.add('bi-eye-slash');
                    } else {
                        toggleConfirmIcon.classList.remove('bi-eye-slash');
                        toggleConfirmIcon.classList.add('bi-eye');
                    }
                }
            }
        };

        document.addEventListener("DOMContentLoaded", function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const form = document.getElementById('resetPasswordForm');
            const confirmFeedback = document.getElementById('confirmFeedback');

            const rules = {
                length: (val) => val.length >= 8,
                lowercase: (val) => /[a-z]/.test(val),
                uppercase: (val) => /[A-Z]/.test(val),
                number: (val) => /[0-9]/.test(val),
                special: (val) => /[#?!@$%^&*-]/.test(val)
            };

            function updateUIElement(element, isValid, isEmpty) {
                if (!element) return;
                const icon = element.querySelector('i');
                
                if (isEmpty) {
                    element.classList.remove('text-danger', 'text-success', 'valid');
                    element.classList.add('text-muted');
                    if (icon) {
                        icon.classList.remove('bi-check-circle-fill', 'bi-x-circle-fill', 'text-danger', 'text-success');
                        icon.classList.add('bi-circle', 'text-muted');
                    }
                    return;
                }

                if (isValid) {
                    element.classList.remove('text-danger', 'text-muted');
                    element.classList.add('text-success', 'valid');
                    if (icon) {
                        icon.classList.remove('bi-x-circle-fill', 'bi-circle', 'text-danger', 'text-muted');
                        icon.classList.add('bi-check-circle-fill', 'text-success');
                    }
                } else {
                    element.classList.remove('text-success', 'valid', 'text-muted');
                    element.classList.add('text-danger');
                    if (icon) {
                        icon.classList.remove('bi-check-circle-fill', 'bi-circle', 'text-success', 'text-muted');
                        icon.classList.add('bi-x-circle-fill', 'text-danger');
                    }
                }
            }

            function validatePassword() {
                if(!passwordInput) return false;
                const val = passwordInput.value;
                const isEmpty = val.length === 0;
                let allValid = true;
                Object.keys(rules).forEach(key => {
                    const isValid = rules[key](val);
                    const element = document.getElementById('rule-' + key);
                    updateUIElement(element, isValid, isEmpty);
                    if (!isValid) allValid = false;
                });
                return isEmpty ? false : allValid;
            }

            function checkConfirmPassword() {
                if(!passwordInput || !confirmInput) return false;
                const p = passwordInput.value;
                const cp = confirmInput.value;
                if (cp.length === 0) {
                    confirmInput.classList.remove('is-valid', 'is-invalid');
                    return false;
                }
                if (p === cp) {
                    confirmInput.classList.remove('is-invalid');
                    confirmInput.classList.add('is-valid');
                    if(confirmFeedback) confirmFeedback.innerText = '';
                    return true;
                } else {
                    confirmInput.classList.remove('is-valid');
                    confirmInput.classList.add('is-invalid');
                    if(confirmFeedback) confirmFeedback.innerText = 'Passwords do not match.';
                    return false;
                }
            }

            if (passwordInput) {
                ['input', 'keyup', 'change'].forEach(evt => {
                    passwordInput.addEventListener(evt, function() {
                        validatePassword();
                        checkConfirmPassword();
                    });
                });
            }

            if (confirmInput) {
                confirmInput.addEventListener('input', checkConfirmPassword);
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    const isPasswordValid = validatePassword();
                    const isConfirmValid = checkConfirmPassword();

                    if (!isPasswordValid || !isConfirmValid) {
                        e.preventDefault();
                        e.stopPropagation();

                        if (!isPasswordValid) {
                            passwordInput.classList.add('is-invalid');
                        }
                        if (!isConfirmValid) {
                            confirmInput.classList.add('is-invalid');
                        }
                        return false;
                    }

                    form.classList.add('was-validated');
                });
            }

            validatePassword();
        });
    </script>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5 col-md-7 d-flex flex-column align-items-center justify-content-center">

                            <!-- Logo -->
                            <div class="d-flex justify-content-center py-4">
                                <a href="{{ url('/') }}"
                                    class="logo d-flex align-items-center w-auto text-decoration-none">
                                    <img src="{{ url('assets/img/logo.png') }}" alt="Logo" style="max-height: 45px;">
                                </a>
                            </div>

                            <!-- Card Wrapper -->
                            <div class="card mb-3"
                                style="width: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                                <div class="card-body p-4">

                                    <div class="pt-2 pb-3">
                                        <h5 class="card-title text-center pb-0 fs-4 text-primary">Reset Password</h5>
                                        <p class="text-center small text-muted">Create a strong and secure password for your
                                            account</p>
                                    </div>

                                    <!-- Status Message -->
                                    @if (isset($message))
                                        <div class="alert alert-{{ $icon === 'danger' ? 'danger' : 'success' }} alert-dismissible fade show"
                                            role="alert">
                                            <i
                                                class="bi {{ $icon === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }} me-2"></i>
                                            {{ $message }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                    @endif

                                    <!-- Reset Form -->
                                    <form class="row g-3 needs-validation" action="{{ route('updatepassword') }}"
                                        method="POST" id="resetPasswordForm" novalidate>
                                        @csrf
                                        <input type="hidden" name="token" value="{{ $token }}" />

                                        <!-- Email Field -->
                                        <div class="col-12">
                                            <label for="email" class="form-label">Email Address</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend"><i
                                                        class="bi bi-envelope"></i></span>
                                                <input type="email" name="email" class="form-control" id="email"
                                                    placeholder="name@example.com" required>
                                                <div class="invalid-feedback">Please enter a valid email address.</div>
                                            </div>
                                        </div>

                                        <!-- Password Field -->
                                        <div class="col-12">
                                            <label for="password" class="form-label">New Password</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                                <input type="password" name="password" class="form-control" id="password"
                                                    placeholder="••••••••" required>
                                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                                    style="position: relative; z-index: 5;"
                                                    onclick="togglePasswordVisibility(event)">
                                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                                </button>
                                                <div class="invalid-feedback">Please enter your new password.</div>
                                            </div>

                                            <!-- Visual Password Criteria Indicators -->
                                            <ul class="validation-checklist" id="checklist">
                                                <li id="rule-length" class="text-muted"><i class="bi bi-circle text-muted"></i> At least 8
                                                    characters</li>
                                                <li id="rule-lowercase" class="text-muted"><i class="bi bi-circle text-muted"></i> Lowercase letter
                                                    (a-z)</li>
                                                <li id="rule-uppercase" class="text-muted"><i class="bi bi-circle text-muted"></i> Uppercase letter
                                                    (A-Z)</li>
                                                <li id="rule-number" class="text-muted"><i class="bi bi-circle text-muted"></i> At least one number
                                                    (0-9)</li>
                                                <li id="rule-special" class="text-muted"><i class="bi bi-circle text-muted"></i> At least one
                                                    special symbol (#?!@$%^&*-)</li>
                                            </ul>
                                        </div>

                                        <!-- Confirm Password Field -->
                                        <div class="col-12">
                                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                                <input type="password" name="password_confirmation" class="form-control"
                                                    id="password_confirmation" placeholder="••••••••" required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword"
                                                    style="position: relative; z-index: 5;"
                                                    onclick="toggleConfirmPasswordVisibility(event)">
                                                    <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                                </button>
                                                <div class="invalid-feedback" id="confirmFeedback">Please confirm your
                                                    password.</div>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="col-12 pt-2">
                                            <button class="btn btn-primary w-100" type="submit" id="submitBtn">Update
                                                Password</button>
                                        </div>
                                    </form>


                                </div>
                            </div>

                            <!-- Copyright info -->
                            <div class="text-center small text-muted mt-2">
                                &copy; {{ date('Y') }} NegoMaster. All rights reserved.
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection
