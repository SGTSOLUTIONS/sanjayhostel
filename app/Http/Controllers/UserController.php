<?php
// app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Hostel;
use App\Enums\RoleEnum;
use App\Enums\ActiveStatusEnum;
use App\Enums\GenderEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        // Only superadmin can access
        if (auth()->user()->role !== RoleEnum::SUPERADMIN->value) {
            abort(403, 'Unauthorized access. Only Super Admin can manage users.');
        }

        $query = User::with('hostels');

        // Filters
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);
        $hostels = Hostel::all();

        // Stats
        $totalUsers = User::count();
        $totalAdmins = User::where('role', RoleEnum::ADMIN->value)->count();
        $totalSuperAdmins = User::where('role', RoleEnum::SUPERADMIN->value)->count();
        $activeUsers = User::where('status', ActiveStatusEnum::ACTIVE->value)->count();
        $inactiveUsers = User::where('status', ActiveStatusEnum::INACTIVE->value)->count();

        return view('admin.users.index', compact(
            'users', 'hostels', 'totalUsers', 'totalAdmins',
            'totalSuperAdmins', 'activeUsers', 'inactiveUsers'
        ));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== RoleEnum::SUPERADMIN->value) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role' => 'required|in:' . implode(',', array_column(RoleEnum::cases(), 'value')),
            'gender' => 'nullable|in:' . implode(',', array_column(GenderEnum::cases(), 'value')),
            'city' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'status' => 'required|in:' . implode(',', array_column(ActiveStatusEnum::cases(), 'value')),
            'hostel_ids' => 'required_if:role,admin|array',
            'hostel_ids.*' => 'exists:hostels,id'
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'gender' => $request->gender,
                'city' => $request->city,
                'date_of_birth' => $request->date_of_birth,
                'status' => $request->status,
            ]);

            // Assign hostels if user is admin
            if ($request->role === RoleEnum::ADMIN->value && $request->has('hostel_ids')) {
                $user->hostels()->sync($request->hostel_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'user' => $user->load('hostels')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user details for editing.
     */
    public function edit($id)
    {
        if (auth()->user()->role !== RoleEnum::SUPERADMIN->value) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::with('hostels')->findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'gender' => $user->gender,
                'city' => $user->city,
                'date_of_birth' => $user->date_of_birth,
                'status' => $user->status,
                'hostel_ids' => $user->hostels->pluck('id')
            ]
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== RoleEnum::SUPERADMIN->value) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:' . implode(',', array_column(RoleEnum::cases(), 'value')),
            'gender' => 'nullable|in:' . implode(',', array_column(GenderEnum::cases(), 'value')),
            'city' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'status' => 'required|in:' . implode(',', array_column(ActiveStatusEnum::cases(), 'value')),
            'hostel_ids' => 'required_if:role,admin|array',
            'hostel_ids.*' => 'exists:hostels,id'
        ]);

        try {
            DB::beginTransaction();

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'gender' => $request->gender,
                'city' => $request->city,
                'date_of_birth' => $request->date_of_birth,
                'status' => $request->status,
            ]);

            // Update password if provided
            if ($request->filled('password')) {
                $request->validate(['password' => 'min:8']);
                $user->password = Hash::make($request->password);
                $user->save();
            }

            // Update hostel assignments
            if ($request->role === RoleEnum::ADMIN->value) {
                $user->hostels()->sync($request->hostel_ids ?? []);
            } else {
                $user->hostels()->detach();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== RoleEnum::SUPERADMIN->value) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account!'
            ], 422);
        }

        try {
            $user->hostels()->detach();
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('User Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus($id)
    {
        if (auth()->user()->role !== RoleEnum::SUPERADMIN->value) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own status!'
            ], 422);
        }

        $user->status = $user->status === ActiveStatusEnum::ACTIVE->value
            ? ActiveStatusEnum::INACTIVE->value
            : ActiveStatusEnum::ACTIVE->value;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully!',
            'status' => $user->status
        ]);
    }

    /**
     * Get admins for dropdown.
     */
    public function getAdmins()
    {
        $admins = User::where('role', RoleEnum::ADMIN->value)
            ->where('status', ActiveStatusEnum::ACTIVE->value)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json(['success' => true, 'data' => $admins]);
    }
}
