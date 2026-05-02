{{-- resources/views/auth/forgot-password.blade.php --}}
@extends('layouts.authLayout')

@section('title', 'Forgot Password - GovConnect')

@section('content')
<div class="container" style="min-height: 100vh; display: flex; align-items: center; padding: 20px;">
    <div style="width: 100%;">
        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #f59e0b, #ef4444); border-radius: 20px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-key" style="font-size: 40px; color: white;"></i>
            </div>
            <h1 style="font-size: 28px; font-weight: 700; color: #1e3a8a; margin-bottom: 8px;">Forgot Password?</h1>
            <p style="color: #6b7280; font-size: 14px; margin-top: 8px;">
                No worries! Enter your email address and we'll send you a reset link.
            </p>
        </div>

        {{-- Forgot Password Form --}}
        <div class="gov-card" style="padding: 24px;">
            <form id="forgotPasswordForm">
                @csrf

                <div class="mb-4">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">
                        <i class="bi bi-envelope me-2"></i>Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Enter your registered email" autocomplete="off"
                           style="font-size: 16px; padding: 14px;">
                    <div class="invalid-feedback" id="email_error" style="font-size: 12px;"></div>
                </div>

                <div class="mb-4">
                    <div class="alert alert-info" style="background: #eff6ff; border: none; border-radius: 10px; font-size: 13px;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        We'll send a password reset link to your email address.
                    </div>
                </div>

                <button type="submit" class="btn btn-gov w-100 text-white" id="sendResetBtn">
                    <span id="btnText">Send Reset Link</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                </button>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{ route('login') }}" style="color: #3b82f6; text-decoration: none; font-weight: 500;">
                        <i class="bi bi-arrow-left me-1"></i>Back to Login
                    </a>
                </div>
            </form>
        </div>

        {{-- Help Section --}}
        <div class="gov-card" style="padding: 16px; margin-top: 16px; background: #fef3c7;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="bi bi-headset" style="font-size: 24px; color: #d97706;"></i>
                <div style="flex: 1;">
                    <p style="font-weight: 600; margin-bottom: 4px; font-size: 14px;">Need help?</p>
                    <p style="font-size: 12px; color: #92400e; margin: 0;">Contact support: support@govconnect.com</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    $('#forgotPasswordForm').on('submit', function(e) {
        e.preventDefault();

        // Reset errors
        $('#email').removeClass('is-invalid');
        $('#email_error').text('');

        const btn = $('#sendResetBtn');
        const btnText = $('#btnText');
        const btnSpinner = $('#btnSpinner');

        btnText.text('Sending...');
        btnSpinner.removeClass('d-none');
        btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('password.email') }}",
            method: "POST",
            data: $(this).serialize(),
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message, 'Email Sent!');
                    // Clear the form
                    $('#email').val('');

                    // Optional: Show success message and redirect after 3 seconds
                    setTimeout(() => {
                        window.location.href = "{{ route('login') }}";
                    }, 3000);
                } else {
                    showToast('warning', res.message, 'Notice');
                }

                btnText.text('Send Reset Link');
                btnSpinner.addClass('d-none');
                btn.prop('disabled', false);
            },
            error: function(xhr) {
                btnText.text('Send Reset Link');
                btnSpinner.addClass('d-none');
                btn.prop('disabled', false);

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    if (errors.email) {
                        $('#email').addClass('is-invalid');
                        $('#email_error').text(errors.email[0]);
                    }
                    showToast('error', 'Please check the email address', 'Validation Error');
                } else if (xhr.status === 404) {
                    showToast('error', xhr.responseJSON.message || 'Email not found', 'Account Not Found');
                } else {
                    showToast('error', 'Something went wrong. Please try again later.', 'Error');
                }
            }
        });
    });

    // Auto capitalize/handle enter key
    $('#email').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#forgotPasswordForm').submit();
        }
    });
});
</script>
@endsection
