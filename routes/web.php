<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HostelController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ==================== SUPERADMIN ONLY ROUTES ====================
    Route::middleware('role:superadmin')->group(function () {

        // PGS/Hostel Management
        Route::prefix('admin/pgs')->name('pgs.')->group(function () {
            Route::get('/', [HostelController::class, 'index'])->name('index');
            Route::post('/store', [HostelController::class, 'store'])->name('store');
            Route::put('/{id}', [HostelController::class, 'update'])->name('update');
            Route::delete('/{id}', [HostelController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/edit', [HostelController::class, 'edit'])->name('edit');
        });


        // Bed Management Routes


        // User Management Routes
        Route::prefix('admin/users')->name('admin.users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UserController::class, 'update'])->name('update');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/admins/list', [UserController::class, 'getAdmins'])->name('admins');
        });
    });

    // ==================== ADMIN & SUPERADMIN ROUTES ====================
    Route::middleware('role:admin,superadmin')->group(function () {
        // Room Types Management
        Route::prefix('admin/room-types')->name('room-types.')->group(function () {
            Route::get('/', [RoomTypeController::class, 'index'])->name('index');
            Route::post('/store', [RoomTypeController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RoomTypeController::class, 'edit'])->name('edit');
            Route::put('/{id}', [RoomTypeController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoomTypeController::class, 'destroy'])->name('destroy');
        });

        // Rooms Management
        Route::prefix('admin/rooms')->name('rooms.')->group(function () {
            Route::get('/', [RoomController::class, 'index'])->name('index');
            Route::post('/store', [RoomController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RoomController::class, 'edit'])->name('edit');
            Route::put('/{id}', [RoomController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoomController::class, 'destroy'])->name('destroy');
            Route::get('/by-hostel/{hostelId}', [RoomController::class, 'getRoomsByHostel'])->name('by-hostel');
        });

        Route::prefix('admin/beds')->name('beds.')->group(function () {
            Route::get('/', [BedController::class, 'index'])->name('index');
            Route::get('/create/{roomId?}', [BedController::class, 'create'])->name('create');
            Route::post('/store', [BedController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [BedController::class, 'edit'])->name('edit');
            Route::put('/{id}', [BedController::class, 'update'])->name('update');
            Route::delete('/{id}', [BedController::class, 'destroy'])->name('destroy');
            Route::get('check-room/{roomId}', [BedController::class, 'checkRoomHasBeds'])->name('check-room');
            Route::post('/bulk-delete', [BedController::class, 'bulkDelete'])->name('bulk-delete');
        });
        // Member Management Routes
        Route::prefix('admin/members')->name('admin.members.')->group(function () {
            // Main CRUD
            Route::get('/', [MemberController::class, 'index'])->name('index');
            Route::post('/store', [MemberController::class, 'store'])->name('store');
            Route::get('/{id}', [MemberController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [MemberController::class, 'edit'])->name('edit');
            Route::put('/{id}', [MemberController::class, 'update'])->name('update');
            Route::delete('/{id}', [MemberController::class, 'destroy'])->name('destroy');

            // AJAX Routes for Dynamic Loading
            Route::get('/rooms/{hostelId}', [MemberController::class, 'getRoomsByHostel'])->name('rooms');
            Route::get('/beds/{roomId}', [MemberController::class, 'getBedsByRoom'])->name('beds');
            Route::get('/rent/{roomId}/{withFood}', [MemberController::class, 'getRentByRoom'])->name('rent');
        });

        // Payment Management Routes
        Route::prefix('admin/payments')->name('admin.payments.')->group(function () {
            // AJAX Routes (must be first)
            Route::get('/members/{hostelId}', [PaymentController::class, 'getMembersByHostel'])->name('members');
            Route::get('/rooms/{hostelId}', [PaymentController::class, 'getRoomsByHostel'])->name('rooms');
            Route::get('/check/{memberId}/{month}', [PaymentController::class, 'checkPaymentExists'])->name('check');
            Route::get('/pending-dues', [PaymentController::class, 'pendingDues'])->name('pending-dues');
            Route::get('/member-history/{memberId}', [PaymentController::class, 'memberPaymentHistory'])->name('member-history');

            // Main CRUD Routes
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::post('/store', [PaymentController::class, 'store'])->name('store');
            Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [PaymentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [PaymentController::class, 'update'])->name('update');
            Route::delete('/{id}', [PaymentController::class, 'destroy'])->name('destroy');
        });
        // Add inside the admin & superadmin middleware group
        Route::prefix('admin/expenses')->name('admin.expenses.')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])->name('index');
            Route::post('/store', [ExpenseController::class, 'store'])->name('store');
            Route::get('/{id}', [ExpenseController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ExpenseController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ExpenseController::class, 'update'])->name('update');
            Route::delete('/{id}', [ExpenseController::class, 'destroy'])->name('destroy');
            Route::get('/summary/data', [ExpenseController::class, 'summary'])->name('summary');
        });

        Route::prefix('admin/staff')->name('admin.staff.')->group(function () {
            // Staff CRUD
            Route::get('/', [StaffController::class, 'index'])->name('index');
            Route::post('/store', [StaffController::class, 'store'])->name('store');
            Route::get('/{id}', [StaffController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{id}', [StaffController::class, 'update'])->name('update');
            Route::delete('/{id}', [StaffController::class, 'destroy'])->name('destroy');

            // Attendance Routes
            Route::get('/attendance/mark', [StaffController::class, 'attendance'])->name('attendance');
            Route::post('/attendance/mark', [StaffController::class, 'markAttendance'])->name('attendance.mark');
            Route::get('/attendance/report', [StaffController::class, 'attendanceReport'])->name('attendance.report');
            Route::get('/{id}/attendance-history', [StaffController::class, 'attendanceHistory'])->name('attendance.history');
        });

        Route::prefix('admin/reports')->name('admin.reports.')->group(function () {
            Route::get('/financial', [ReportController::class, 'index'])->name('financial');
            Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        });
    });

    // ==================== DASHBOARD (All authenticated users) ====================
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});
