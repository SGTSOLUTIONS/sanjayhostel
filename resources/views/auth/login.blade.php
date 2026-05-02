{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.authLayout')

@section('title', 'Login - GovConnect')

@section('content')
<div class="container" style="min-height: 100vh; display: flex; align-items: center; padding: 20px;">
    <div style="width: 100%;">
        {{-- Government Logo --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #1e3a8a, #3b82f6); border-radius: 20px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-building" style="font-size: 40px; color: white;"></i>
            </div>
            <h1 style="font-size: 28px; font-weight: 700; color: #1e3a8a; margin-bottom: 8px;">Welcome Back</h1>
            <p style="color: #6b7280; font-size: 14px;">Sign in to access government services</p>
        </div>

        {{-- Login Form --}}
        <div class="gov-card" style="padding: 24px;">
            <form id="loginForm">
                @csrf

                <div class="mb-4">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">
                        <i class="bi bi-envelope me-2"></i>Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autocomplete="off">
                    <div class="invalid-feedback" id="email_error" style="font-size: 12px;"></div>
                </div>

                <div class="mb-4">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">
                        <i class="bi bi-lock me-2"></i>Password
                    </label>
                    <div style="position: relative;">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
                        <i class="bi bi-eye-slash" id="togglePassword" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #9ca3af;"></i>
                    </div>
                    <div class="invalid-feedback" id="password_error" style="font-size: 12px;"></div>
                </div>

                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" style="font-size: 14px;">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" style="font-size: 14px; color: #3b82f6; text-decoration: none;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-gov w-100 text-white" id="loginBtn">
                    <span id="btnText">Sign In</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                </button>

                <div style="text-align: center; margin-top: 24px;">
                    <span style="color: #6b7280; font-size: 14px;">Don't have an account? </span>
                    <a href="{{ route('register') }}" style="color: #3b82f6; font-weight: 600; text-decoration: none;">Register Now</a>
                </div>
            </form>
        </div>

        {{-- Government Info --}}
        <div style="text-align: center; margin-top: 24px;">
            <p style="font-size: 12px; color: #9ca3af;">
                <i class="bi bi-shield-check me-1"></i>Secure Government Portal
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    // Toggle password visibility
    $('#togglePassword').click(function() {
        const password = $('#password');
        const type = password.attr('type') === 'password' ? 'text' : 'password';
        password.attr('type', type);
        $(this).toggleClass('bi-eye bi-eye-slash');
    });

    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        // Reset errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        const btn = $('#loginBtn');
        const btnText = $('#btnText');
        const btnSpinner = $('#btnSpinner');

        btnText.text('Signing in...');
        btnSpinner.removeClass('d-none');
        btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('login.post') }}",
            method: "POST",
            data: $(this).serialize(),
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message, 'Welcome!');
                    setTimeout(() => {
                        window.location.href = res.redirect;
                    }, 1000);
                }
            },
            error: function(xhr) {
                btnText.text('Sign In');
                btnSpinner.addClass('d-none');
                btn.prop('disabled', false);

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    for (let key in errors) {
                        $(`#${key}`).addClass('is-invalid');
                        $(`#${key}_error`).text(errors[key][0]);
                    }
                    showToast('error', 'Please check your inputs', 'Validation Error');
                } else if (xhr.status === 403) {
                    showToast('error', xhr.responseJSON.message, 'Account Inactive');
                } else if (xhr.status === 401 || xhr.status === 404) {
                    showToast('error', xhr.responseJSON.message, 'Login Failed');
                } else {
                    showToast('error', 'Something went wrong. Please try again.', 'Error');
                }
            }
        });
    });
});
</script>
@endsection
