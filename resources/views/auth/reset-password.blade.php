{{-- resources/views/auth/reset-password.blade.php --}}
@extends('layouts.authLayout')

@section('title', 'Reset Password - GovConnect')

@section('styles')
<style>
    .password-strength-meter {
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
        margin-top: 8px;
    }

    .strength-bar {
        height: 100%;
        width: 0%;
        transition: width 0.3s ease, background-color 0.3s ease;
        border-radius: 2px;
    }

    .strength-weak { background: #ef4444; width: 25%; }
    .strength-fair { background: #f59e0b; width: 50%; }
    .strength-good { background: #10b981; width: 75%; }
    .strength-strong { background: #059669; width: 100%; }

    .password-requirements {
        font-size: 12px;
        margin-top: 8px;
        padding: 8px;
        background: #f9fafb;
        border-radius: 8px;
    }

    .requirement {
        color: #6b7280;
        margin: 4px 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .requirement.valid {
        color: #10b981;
    }

    .requirement i {
        font-size: 12px;
    }
</style>
@endsection

@section('content')
<div class="container" style="min-height: 100vh; display: flex; align-items: center; padding: 20px;">
    <div style="width: 100%;">
        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 20px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-lock-fill" style="font-size: 40px; color: white;"></i>
            </div>
            <h1 style="font-size: 28px; font-weight: 700; color: #1e3a8a; margin-bottom: 8px;">Create New Password</h1>
            <p style="color: #6b7280; font-size: 14px;">
                Please create a strong password for your account
            </p>
        </div>

        {{-- Reset Password Form --}}
        <div class="gov-card" style="padding: 24px;">
            <form id="resetPasswordForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Email (readonly) --}}
                <div class="mb-4">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">
                        <i class="bi bi-envelope me-2"></i>Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="{{ $email ?? old('email') }}" readonly
                           style="background: #f9fafb; cursor: not-allowed;">
                    <div class="invalid-feedback" id="email_error"></div>
                </div>

                {{-- New Password --}}
                <div class="mb-3">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">
                        <i class="bi bi-lock me-2"></i>New Password
                    </label>
                    <div style="position: relative;">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter new password" style="padding: 14px;">
                        <i class="bi bi-eye-slash" id="togglePassword"
                           style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #9ca3af;"></i>
                    </div>
                    <div class="invalid-feedback" id="password_error"></div>

                    {{-- Password Strength Meter --}}
                    <div class="password-strength-meter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>

                    {{-- Password Requirements --}}
                    <div class="password-requirements">
                        <div class="requirement" id="req-length">
                            <i class="bi bi-circle"></i>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="requirement" id="req-upper">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 uppercase letter</span>
                        </div>
                        <div class="requirement" id="req-lower">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 lowercase letter</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 number</span>
                        </div>
                        <div class="requirement" id="req-special">
                            <i class="bi bi-circle"></i>
                            <span>At least 1 special character</span>
                        </div>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div class="mb-4">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">
                        <i class="bi bi-lock-fill me-2"></i>Confirm Password
                    </label>
                    <div style="position: relative;">
                        <input type="password" class="form-control" id="password_confirmation"
                               name="password_confirmation" placeholder="Confirm your password" style="padding: 14px;">
                        <i class="bi bi-eye-slash" id="toggleConfirmPassword"
                           style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #9ca3af;"></i>
                    </div>
                    <div class="invalid-feedback" id="password_confirmation_error"></div>
                    <div id="matchStatus" style="font-size: 12px; margin-top: 5px;"></div>
                </div>

                <div class="mb-4">
                    <div class="alert alert-warning" style="background: #fef3c7; border: none; border-radius: 10px; font-size: 13px;">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        <strong>Password Tips:</strong>
                        <ul style="margin: 8px 0 0 20px; padding: 0;">
                            <li>Use a mix of letters, numbers, and symbols</li>
                            <li>Avoid common words or personal information</li>
                            <li>Don't reuse passwords from other accounts</li>
                        </ul>
                    </div>
                </div>

                <button type="submit" class="btn btn-gov w-100 text-white" id="resetBtn">
                    <span id="btnText">Reset Password</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                </button>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{ route('login') }}" style="color: #3b82f6; text-decoration: none; font-weight: 500;">
                        <i class="bi bi-arrow-left me-1"></i>Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    // Toggle password visibility
    $('#togglePassword, #toggleConfirmPassword').click(function() {
        const targetId = $(this).attr('id') === 'togglePassword' ? '#password' : '#password_confirmation';
        const input = $(targetId);
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).toggleClass('bi-eye-slash bi-eye');
    });

    // Password strength and validation
    function checkPasswordStrength(password) {
        let strength = 0;

        // Length check
        if (password.length >= 8) {
            $('#req-length i').removeClass('bi-circle').addClass('bi-check-circle-fill');
            $('#req-length').addClass('valid');
            strength++;
        } else {
            $('#req-length i').removeClass('bi-check-circle-fill').addClass('bi-circle');
            $('#req-length').removeClass('valid');
        }

        // Uppercase check
        if (/[A-Z]/.test(password)) {
            $('#req-upper i').removeClass('bi-circle').addClass('bi-check-circle-fill');
            $('#req-upper').addClass('valid');
            strength++;
        } else {
            $('#req-upper i').removeClass('bi-check-circle-fill').addClass('bi-circle');
            $('#req-upper').removeClass('valid');
        }

        // Lowercase check
        if (/[a-z]/.test(password)) {
            $('#req-lower i').removeClass('bi-circle').addClass('bi-check-circle-fill');
            $('#req-lower').addClass('valid');
            strength++;
        } else {
            $('#req-lower i').removeClass('bi-check-circle-fill').addClass('bi-circle');
            $('#req-lower').removeClass('valid');
        }

        // Number check
        if (/\d/.test(password)) {
            $('#req-number i').removeClass('bi-circle').addClass('bi-check-circle-fill');
            $('#req-number').addClass('valid');
            strength++;
        } else {
            $('#req-number i').removeClass('bi-check-circle-fill').addClass('bi-circle');
            $('#req-number').removeClass('valid');
        }

        // Special character check
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            $('#req-special i').removeClass('bi-circle').addClass('bi-check-circle-fill');
            $('#req-special').addClass('valid');
            strength++;
        } else {
            $('#req-special i').removeClass('bi-check-circle-fill').addClass('bi-circle');
            $('#req-special').removeClass('valid');
        }

        // Update strength meter
        const strengthBar = $('#strengthBar');
        strengthBar.removeClass('strength-weak strength-fair strength-good strength-strong');

        if (strength <= 2) {
            strengthBar.addClass('strength-weak');
        } else if (strength === 3) {
            strengthBar.addClass('strength-fair');
        } else if (strength === 4) {
            strengthBar.addClass('strength-good');
        } else if (strength === 5) {
            strengthBar.addClass('strength-strong');
        }

        return strength;
    }

    // Password match check
    function checkPasswordMatch() {
        const password = $('#password').val();
        const confirm = $('#password_confirmation').val();

        if (confirm.length > 0) {
            if (password === confirm) {
                $('#matchStatus').html('<i class="bi bi-check-circle-fill" style="color: #10b981;"></i> <span style="color: #10b981;">Passwords match!</span>');
                return true;
            } else {
                $('#matchStatus').html('<i class="bi bi-x-circle-fill" style="color: #ef4444;"></i> <span style="color: #ef4444;">Passwords do not match</span>');
                return false;
            }
        } else {
            $('#matchStatus').html('');
            return false;
        }
    }

    // Real-time password validation
    $('#password').on('input', function() {
        const password = $(this).val();
        checkPasswordStrength(password);
        checkPasswordMatch();
    });

    $('#password_confirmation').on('input', function() {
        checkPasswordMatch();
    });

    // Form submission
    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();

        // Reset errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Check password match
        if ($('#password').val() !== $('#password_confirmation').val()) {
            $('#password_confirmation').addClass('is-invalid');
            $('#password_confirmation_error').text('Passwords do not match.');
            showToast('error', 'Please make sure your passwords match', 'Validation Error');
            return;
        }

        const btn = $('#resetBtn');
        const btnText = $('#btnText');
        const btnSpinner = $('#btnSpinner');

        btnText.text('Resetting...');
        btnSpinner.removeClass('d-none');
        btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('password.update') }}",
            method: "POST",
            data: $(this).serialize(),
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message, 'Password Reset Successful!');
                    setTimeout(() => {
                        window.location.href = res.redirect;
                    }, 2000);
                } else {
                    showToast('error', res.message, 'Reset Failed');
                    btnText.text('Reset Password');
                    btnSpinner.addClass('d-none');
                    btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                btnText.text('Reset Password');
                btnSpinner.addClass('d-none');
                btn.prop('disabled', false);

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    for (let key in errors) {
                        $(`#${key}`).addClass('is-invalid');
                        $(`#${key}_error`).text(errors[key][0]);
                    }
                    showToast('error', 'Please check the form for errors', 'Validation Error');
                } else if (xhr.status === 400) {
                    showToast('error', xhr.responseJSON.message || 'Invalid or expired reset link', 'Invalid Token');
                } else {
                    showToast('error', 'Something went wrong. Please try again.', 'Error');
                }
            }
        });
    });
});
</script>
@endsection
