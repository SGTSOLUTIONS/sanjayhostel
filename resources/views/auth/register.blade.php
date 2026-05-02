{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.authLayout')

@section('title', 'Register - GovConnect')

@section('content')
<div class="container" style="padding: 20px 0 40px;">
    <div style="text-align: center; margin-bottom: 24px;">
        <h2 style="font-weight: 700; color: #1e3a8a;">Create Account</h2>
        <p style="color: #6b7280; font-size: 14px;">Register for government services</p>
    </div>

    <div class="gov-card" style="padding: 24px;">
        <form id="registerForm" enctype="multipart/form-data">
            @csrf

            {{-- Profile Picture --}}
            <div class="mb-4">
                <label style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">
                    <i class="bi bi-camera me-2"></i>Profile Photo
                </label>
                <div class="upload-area" id="uploadArea">
                    <i class="bi bi-cloud-upload" style="font-size: 40px; color: #9ca3af;"></i>
                    <p style="margin-top: 8px; font-size: 14px; color: #6b7280;">Tap to upload photo</p>
                    <small style="font-size: 12px; color: #9ca3af;">JPG, PNG up to 2MB</small>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display: none;">
                </div>
                <div id="imagePreview" style="display: none; margin-top: 12px; text-align: center;">
                    <img id="previewImg" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                    <button type="button" id="removeImage" class="btn btn-sm btn-danger mt-2">Remove</button>
                </div>
                <div class="invalid-feedback" id="profile_picture_error"></div>
            </div>

            {{-- Full Name --}}
            <div class="mb-3">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-person me-2"></i>Full Name
                </label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" value="{{ old('name') }}">
                <div class="invalid-feedback" id="name_error"></div>
            </div>

            {{-- Email --}}
            <div class="mb-3">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-envelope me-2"></i>Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" value="{{ old('email') }}">
                <div class="invalid-feedback" id="email_error"></div>
            </div>

            {{-- Password --}}
            <div class="mb-3">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Create password">
                <div class="invalid-feedback" id="password_error"></div>
                <small class="text-muted" style="font-size: 12px;">Minimum 8 characters</small>
            </div>

            {{-- Confirm Password --}}
            <div class="mb-3">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-lock-fill me-2"></i>Confirm Password
                </label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm password">
                <div class="invalid-feedback" id="password_confirmation_error"></div>
            </div>

            {{-- Gender --}}
            <div class="mb-3">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-gender-ambiguous me-2"></i>Gender
                </label>
                <select class="form-select" id="gender" name="gender">
                    <option value="">Select gender</option>
                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
                <div class="invalid-feedback" id="gender_error"></div>
            </div>

            {{-- Phone --}}
            <div class="mb-3">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-telephone me-2"></i>Phone Number
                </label>
                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter phone number" value="{{ old('phone') }}">
                <div class="invalid-feedback" id="phone_error"></div>
            </div>

            {{-- Date of Birth --}}
            <div class="mb-3">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-calendar me-2"></i>Date of Birth
                </label>
                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                <div class="invalid-feedback" id="date_of_birth_error"></div>
            </div>

            {{-- City --}}
            <div class="mb-4">
                <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                    <i class="bi bi-building me-2"></i>City
                </label>
                <input type="text" class="form-control" id="city" name="city" placeholder="Enter your city" value="{{ old('city') }}">
                <div class="invalid-feedback" id="city_error"></div>
            </div>

            <button type="submit" class="btn btn-gov w-100 text-white" id="registerBtn">
                <span id="btnText">Create Account</span>
                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
            </button>

            <div style="text-align: center; margin-top: 20px;">
                <span style="font-size: 14px; color: #6b7280;">Already have an account? </span>
                <a href="{{ route('login') }}" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Sign In</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Wait for DOM and jQuery to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }

    console.log('jQuery version:', jQuery.fn.jquery);

    // Use jQuery in no-conflict mode
    jQuery(document).ready(function($) {
        console.log('Document ready - Register form loaded');

        // File upload handling
        $('#uploadArea').click(function() {
            $('#profile_picture').click();
        });

        $('#profile_picture').change(function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    if (typeof showToast !== 'undefined') {
                        showToast('error', 'Please select a valid image file (JPG, PNG, GIF)', 'Invalid File');
                    } else {
                        alert('Please select a valid image file (JPG, PNG, GIF)');
                    }
                    $(this).val('');
                    return;
                }

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    if (typeof showToast !== 'undefined') {
                        showToast('error', 'File size must be less than 2MB', 'File Too Large');
                    } else {
                        alert('File size must be less than 2MB');
                    }
                    $(this).val('');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#previewImg').attr('src', e.target.result);
                    $('#imagePreview').show();
                    $('#uploadArea').hide();
                };
                reader.readAsDataURL(file);
            }
        });

        $('#removeImage').click(function(e) {
            e.preventDefault();
            $('#profile_picture').val('');
            $('#imagePreview').hide();
            $('#uploadArea').show();
        });

        // Form submission
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');

            // Clear previous errors
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            // Get form data
            const formData = new FormData(this);

            // Log form data for debugging
            console.log('Form Data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            const btn = $('#registerBtn');
            const btnText = $('#btnText');
            const btnSpinner = $('#btnSpinner');

            btnText.text('Creating Account...');
            btnSpinner.removeClass('d-none');
            btn.prop('disabled', true);

            // Get CSRF token
            const csrfToken = $('input[name="_token"]').val();
            console.log('CSRF Token:', csrfToken);

            $.ajax({
                url: "{{ route('register.post') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(res) {
                    console.log('Success response:', res);
                    btnText.text('Create Account');
                    btnSpinner.addClass('d-none');
                    btn.prop('disabled', false);

                    if (res.status === 'success') {
                        if (typeof showToast !== 'undefined') {
                            showToast('success', res.message, 'Registration Successful!');
                        } else {
                            alert(res.message);
                        }
                        setTimeout(() => {
                            window.location.href = res.redirect;
                        }, 2000);
                    } else {
                        if (typeof showToast !== 'undefined') {
                            showToast('error', res.message || 'Registration failed', 'Error');
                        } else {
                            alert(res.message || 'Registration failed');
                        }
                    }
                },
                error: function(xhr) {
                    console.log('Error response:', xhr);
                    btnText.text('Create Account');
                    btnSpinner.addClass('d-none');
                    btn.prop('disabled', false);

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        console.log('Validation errors:', errors);

                        for (let key in errors) {
                            if (key === 'profile_picture') {
                                $('#profile_picture_error').text(errors[key][0]);
                                $('#profile_picture_error').show();
                            } else {
                                $(`#${key}`).addClass('is-invalid');
                                $(`#${key}_error`).text(errors[key][0]);
                            }
                        }
                        if (typeof showToast !== 'undefined') {
                            showToast('error', 'Please check the form for errors', 'Validation Error');
                        } else {
                            alert('Please check the form for errors');
                        }
                    } else if (xhr.status === 419) {
                        if (typeof showToast !== 'undefined') {
                            showToast('error', 'Session expired. Please refresh the page.', 'Session Error');
                        } else {
                            alert('Session expired. Please refresh the page.');
                        }
                    } else if (xhr.status === 500) {
                        console.log('Server error details:', xhr.responseJSON);
                        const errorMsg = xhr.responseJSON?.message || 'Server error. Please try again.';
                        if (typeof showToast !== 'undefined') {
                            showToast('error', errorMsg, 'Server Error');
                        } else {
                            alert(errorMsg);
                        }
                    } else {
                        const errorMsg = xhr.responseJSON?.message || 'Registration failed. Please try again.';
                        if (typeof showToast !== 'undefined') {
                            showToast('error', errorMsg, 'Error');
                        } else {
                            alert(errorMsg);
                        }
                    }
                }
            });
        });
    });
});
</script>
@endsection
