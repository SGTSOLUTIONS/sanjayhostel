<?php
// app/Http/Controllers/StaffController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Hostel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    /**
     * Get allowed hostel IDs based on user role
     */
    private function getAllowedHostelIds()
    {
        $user = auth()->user();

        if ($user->role === 'superadmin') {
            return Hostel::pluck('id');
        } else {
            return $user->hostels()->pluck('hostel_id');
        }
    }

    /**
     * Display a listing of staff.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $query = Staff::with('hostel')
            ->whereIn('hostel_id', $allowedHostelIds);

        // Filters
        if ($request->has('hostel_id') && $request->hostel_id) {
            $query->where('hostel_id', $request->hostel_id);
        }
        if ($request->has('position') && $request->position) {
            $query->where('position', $request->position);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }

        $staff = $query->latest()->paginate(15);

        // For filters
        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();
        $positions = Staff::whereIn('hostel_id', $allowedHostelIds)
            ->distinct()
            ->pluck('position');

        // Stats
        $totalStaff = Staff::whereIn('hostel_id', $allowedHostelIds)->count();
        $activeStaff = Staff::whereIn('hostel_id', $allowedHostelIds)
            ->where('status', 'active')
            ->count();
        $currentMonth = date('Y-m');
        $totalSalary = Staff::whereIn('hostel_id', $allowedHostelIds)
            ->where('status', 'active')
            ->sum('salary');

        return view('admin.staff.index', compact(
            'staff', 'hostels', 'positions', 'totalStaff',
            'activeStaff', 'totalSalary', 'currentMonth'
        ));
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'position' => 'required|string|max:100',
            'salary' => 'required|numeric|min:0',
            'joining_date' => 'required|date',
            'address' => 'nullable|string',
            'aadhar_number' => 'nullable|string|max:20',
            'hostel_id' => 'required|exists:hostels,id',
            'status' => 'required|in:active,inactive,left',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($request->hostel_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this hostel.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $staff = new Staff();
            $staff->name = $request->name;
            $staff->phone = $request->phone;
            $staff->email = $request->email;
            $staff->position = $request->position;
            $staff->salary = $request->salary;
            $staff->joining_date = $request->joining_date;
            $staff->address = $request->address;
            $staff->aadhar_number = $request->aadhar_number;
            $staff->hostel_id = $request->hostel_id;
            $staff->status = $request->status;
            $staff->created_by = auth()->id();

            // Upload profile image
            if ($request->hasFile('profile_image')) {
                $folderPath = "uploads/hostels/{$request->hostel_id}/staff";
                $imageName = time() . '_' . $request->file('profile_image')->getClientOriginalName();
                $request->file('profile_image')->move(public_path($folderPath), $imageName);
                $staff->profile_image = $folderPath . '/' . $imageName;
            }

            $staff->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staff member added successfully!',
                'data' => $staff->load('hostel')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staff Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add staff: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get staff details for viewing.
     */
    public function show($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $staff = Staff::with('hostel', 'creator')->findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($staff->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get attendance summary for current month
        $currentMonth = date('m');
        $currentYear = date('Y');
        $presentDays = $staff->getPresentDaysCount($currentMonth, $currentYear);
        $leaveDays = $staff->getLeaveDaysCount($currentMonth, $currentYear);

        // Get recent attendance
        $recentAttendance = $staff->attendances()
            ->latest('attendance_date')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'phone' => $staff->phone,
                'email' => $staff->email,
                'position' => $staff->position,
                'salary' => $staff->salary,
                'formatted_salary' => '₹' . number_format($staff->salary, 2),
                'joining_date' => $staff->joining_date->format('d F Y'),
                'address' => $staff->address,
                'aadhar_number' => $staff->aadhar_number,
                'hostel_name' => $staff->hostel->name,
                'status' => $staff->status,
                'profile_image' => $staff->profile_image ? asset($staff->profile_image) : null,
                'created_by' => $staff->creator->name ?? 'N/A',
                'current_month_summary' => [
                    'present_days' => $presentDays,
                    'leave_days' => $leaveDays,
                    'total_days' => date('t'),
                    'salary_for_month' => '₹' . number_format(($staff->salary / date('t')) * $presentDays, 2)
                ],
                'recent_attendance' => $recentAttendance->map(function($attendance) {
                    return [
                        'date' => $attendance->attendance_date->format('d M Y'),
                        'status' => $attendance->status_text,
                        'status_badge' => $attendance->status_badge,
                        'leave_reason' => $attendance->leave_reason,
                        'work_details' => $attendance->work_details
                    ];
                })
            ]
        ]);
    }

    /**
     * Get staff data for editing.
     */
    public function edit($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $staff = Staff::findOrFail($id);

        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($staff->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'phone' => $staff->phone,
                'email' => $staff->email,
                'position' => $staff->position,
                'salary' => $staff->salary,
                'joining_date' => $staff->joining_date->format('Y-m-d'),
                'address' => $staff->address,
                'aadhar_number' => $staff->aadhar_number,
                'hostel_id' => $staff->hostel_id,
                'status' => $staff->status,
                'profile_image' => $staff->profile_image
            ]
        ]);
    }

    /**
     * Update staff member.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $staff = Staff::findOrFail($id);

        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($staff->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'position' => 'required|string|max:100',
            'salary' => 'required|numeric|min:0',
            'joining_date' => 'required|date',
            'address' => 'nullable|string',
            'aadhar_number' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,left',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        try {
            DB::beginTransaction();

            $staff->name = $request->name;
            $staff->phone = $request->phone;
            $staff->email = $request->email;
            $staff->position = $request->position;
            $staff->salary = $request->salary;
            $staff->joining_date = $request->joining_date;
            $staff->address = $request->address;
            $staff->aadhar_number = $request->aadhar_number;
            $staff->status = $request->status;

            // Upload new profile image
            if ($request->hasFile('profile_image')) {
                if ($staff->profile_image && file_exists(public_path($staff->profile_image))) {
                    unlink(public_path($staff->profile_image));
                }
                $folderPath = "uploads/hostels/{$staff->hostel_id}/staff";
                $imageName = time() . '_' . $request->file('profile_image')->getClientOriginalName();
                $request->file('profile_image')->move(public_path($folderPath), $imageName);
                $staff->profile_image = $folderPath . '/' . $imageName;
            }

            $staff->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staff Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete staff member.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $staff = Staff::findOrFail($id);

        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($staff->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            // Delete profile image
            if ($staff->profile_image && file_exists(public_path($staff->profile_image))) {
                unlink(public_path($staff->profile_image));
            }

            // Delete attendance records
            $staff->attendances()->delete();

            $staff->delete();

            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Staff Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete staff: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display attendance page.
     */
    public function attendance(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        // Get selected date
        $selectedDate = $request->get('date', date('Y-m-d'));

        // Get staff for attendance marking
        $staff = Staff::whereIn('hostel_id', $allowedHostelIds)
            ->where('status', 'active')
            ->with(['attendances' => function($q) use ($selectedDate) {
                $q->where('attendance_date', $selectedDate);
            }])
            ->get();

        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();

        return view('admin.staff.attendance', compact('staff', 'hostels', 'selectedDate'));
    }

    /**
     * Mark attendance for a staff member.
     */
    public function markAttendance(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,leave,half_day,holiday',
            'leave_reason' => 'required_if:status,leave|nullable|string',
            'work_details' => 'nullable|string',
            'notes' => 'nullable|string',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $staff = Staff::find($request->staff_id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($staff->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            // Check if attendance already exists
            $attendance = StaffAttendance::where('staff_id', $request->staff_id)
                ->where('attendance_date', $request->attendance_date)
                ->first();

            $proofPath = null;
            if ($request->hasFile('proof_image')) {
                $folderPath = "uploads/hostels/{$staff->hostel_id}/staff/proof";
                if (!file_exists(public_path($folderPath))) {
                    mkdir(public_path($folderPath), 0777, true);
                }
                $proofName = time() . '_' . $request->file('proof_image')->getClientOriginalName();
                $request->file('proof_image')->move(public_path($folderPath), $proofName);
                $proofPath = $folderPath . '/' . $proofName;
            }

            if ($attendance) {
                // Update existing
                $attendance->status = $request->status;
                $attendance->leave_reason = $request->leave_reason;
                $attendance->work_details = $request->work_details;
                $attendance->notes = $request->notes;
                if ($proofPath) {
                    // Delete old proof
                    if ($attendance->proof_image && file_exists(public_path($attendance->proof_image))) {
                        unlink(public_path($attendance->proof_image));
                    }
                    $attendance->proof_image = $proofPath;
                }
                $attendance->marked_by = auth()->id();
                $attendance->save();
                $message = 'Attendance updated successfully!';
            } else {
                // Create new
                $attendance = new StaffAttendance();
                $attendance->staff_id = $request->staff_id;
                $attendance->attendance_date = $request->attendance_date;
                $attendance->status = $request->status;
                $attendance->leave_reason = $request->leave_reason;
                $attendance->work_details = $request->work_details;
                $attendance->notes = $request->notes;
                $attendance->proof_image = $proofPath;
                $attendance->marked_by = auth()->id();
                $attendance->save();
                $message = 'Attendance marked successfully!';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendance Marking Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance report.
     */
    public function attendanceReport(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $hostelId = $request->get('hostel_id');

        $query = Staff::with(['attendances' => function($q) use ($month, $year) {
            $q->whereMonth('attendance_date', $month)
              ->whereYear('attendance_date', $year);
        }])->whereIn('hostel_id', $allowedHostelIds)->where('status', 'active');

        if ($hostelId) {
            $query->where('hostel_id', $hostelId);
        }

        $staff = $query->get();

        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();

        $reportData = [];
        foreach ($staff as $member) {
            $presentDays = $member->getPresentDaysCount($month, $year);
            $leaveDays = $member->getLeaveDaysCount($month, $year);
            $totalDays = date('t', strtotime("$year-$month-01"));
            $halfDays = $member->attendances->where('status', 'half_day')->count();

            $reportData[] = [
                'id' => $member->id,
                'name' => $member->name,
                'position' => $member->position,
                'salary' => $member->salary,
                'hostel' => $member->hostel->name,
                'present_days' => $presentDays,
                'leave_days' => $leaveDays,
                'half_days' => $halfDays,
                'total_days' => $totalDays,
                'salary_to_pay' => ($member->salary / $totalDays) * $presentDays
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'month_name' => date('F Y', strtotime("$year-$month-01")),
                'report' => $reportData,
                'total_salary' => array_sum(array_column($reportData, 'salary_to_pay'))
            ]
        ]);
    }

    /**
     * Get attendance history for a staff member.
     */
    public function attendanceHistory($id, Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $staff = Staff::findOrFail($id);

        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($staff->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $attendances = StaffAttendance::where('staff_id', $id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderBy('attendance_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'staff_name' => $staff->name,
                'staff_position' => $staff->position,
                'month' => date('F Y', strtotime("$year-$month-01")),
                'attendances' => $attendances->map(function($attendance) {
                    return [
                        'date' => $attendance->attendance_date->format('d M Y'),
                        'status' => $attendance->status_text,
                        'status_badge' => $attendance->status_badge,
                        'leave_reason' => $attendance->leave_reason,
                        'work_details' => $attendance->work_details,
                        'proof_image' => $attendance->proof_image_url,
                        'marked_by' => $attendance->marker->name ?? 'N/A'
                    ];
                }),
                'summary' => [
                    'present' => $attendances->where('status', 'present')->count(),
                    'leave' => $attendances->where('status', 'leave')->count(),
                    'half_day' => $attendances->where('status', 'half_day')->count(),
                    'holiday' => $attendances->where('status', 'holiday')->count()
                ]
            ]
        ]);
    }
}
