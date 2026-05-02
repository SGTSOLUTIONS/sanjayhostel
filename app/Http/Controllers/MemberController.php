<?php
// app/Http/Controllers/MemberController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Hostel;
use App\Models\Room;
use App\Models\Bed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
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
     * Display a listing of members.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $query = Member::with(['hostel', 'room', 'bed'])
            ->whereIn('hostel_id', $allowedHostelIds);

        // Filters
        if ($request->has('hostel_id') && $request->hostel_id) {
            $query->where('hostel_id', $request->hostel_id);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        $members = $query->latest()->paginate(15);

        // For filters - only accessible hostels
        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();

        // Stats
        $totalMembers = Member::whereIn('hostel_id', $allowedHostelIds)->count();
        $activeMembers = Member::whereIn('hostel_id', $allowedHostelIds)->where('status', 'active')->count();
        $leftMembers = Member::whereIn('hostel_id', $allowedHostelIds)->where('status', 'left')->count();
        $withFood = Member::whereIn('hostel_id', $allowedHostelIds)->where('with_food', true)->count();
        $withoutFood = Member::whereIn('hostel_id', $allowedHostelIds)->where('with_food', false)->count();

        return view('admin.members.index', compact(
            'members', 'hostels', 'totalMembers', 'activeMembers',
            'leftMembers', 'withFood', 'withoutFood'
        ));
    }

    /**
     * Store a newly created member.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'hostel_id' => 'required|exists:hostels,id',
            'room_id' => 'required|exists:rooms,id',
            'bed_id' => 'required|exists:beds,id',
            'with_food' => 'required|boolean',
            'join_date' => 'required|date',
            'status' => 'required|in:active,left',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'addmissionform' => 'nullable|mimes:pdf,jpeg,png,jpg|max:2048',
            'aadharimage' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Check access to hostel
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($request->hostel_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this hostel.'
            ], 403);
        }

        // Check if bed is available
        $bed = Bed::find($request->bed_id);
        if ($bed->is_occupied) {
            return response()->json([
                'success' => false,
                'message' => 'This bed is already occupied!'
            ], 422);
        }

        // Get room type to calculate rent
        $room = Room::find($request->room_id);
        $roomType = $room->roomType;

        if ($request->with_food) {
            $rentAmount = $roomType->rent_with_food;
        } else {
            $rentAmount = $roomType->rent_without_food;
        }

        try {
            DB::beginTransaction();

            $member = new Member();
            $member->name = $request->name;
            $member->phone = $request->phone;
            $member->hostel_id = $request->hostel_id;
            $member->room_id = $request->room_id;
            $member->bed_id = $request->bed_id;
            $member->with_food = $request->with_food;
            $member->rent_amount = $rentAmount;
            $member->join_date = $request->join_date;
            $member->exit_date = $request->exit_date;
            $member->status = $request->status;
            $member->save();

            // Upload Images
            $folderPath = "uploads/hostels/{$request->hostel_id}/rooms/{$request->room_id}/members/{$member->id}";

            if ($request->hasFile('image')) {
                $imageName = 'profile_' . time() . '.' . $request->image->extension();
                $request->image->move(public_path($folderPath), $imageName);
                $member->image = $folderPath . '/' . $imageName;
            }

            if ($request->hasFile('addmissionform')) {
                $formName = 'admission_' . time() . '.' . $request->addmissionform->extension();
                $request->addmissionform->move(public_path($folderPath), $formName);
                $member->addmissionform = $folderPath . '/' . $formName;
            }

            if ($request->hasFile('aadharimage')) {
                $aadharName = 'aadhar_' . time() . '.' . $request->aadharimage->extension();
                $request->aadharimage->move(public_path($folderPath), $aadharName);
                $member->aadharimage = $folderPath . '/' . $aadharName;
            }

            $member->save();

            // Mark bed as occupied
            $bed->is_occupied = true;
            $bed->current_member_id = $member->id;
            $bed->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member added successfully!',
                'data' => $member
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Member Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get member details for viewing.
     */
    public function show($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member = Member::with(['hostel', 'room', 'room.roomType', 'bed', 'payments'])->findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'hostel_name' => $member->hostel->name ?? 'N/A',
                'room_number' => $member->room->room_number ?? 'N/A',
                'bed_number' => $member->bed->bed_number ?? 'N/A',
                'bed_type' => $member->bed->bed_type ?? 'N/A',
                'with_food' => $member->with_food,
                'rent_amount' => $member->rent_amount,
                'join_date' => $member->join_date ? date('d F Y', strtotime($member->join_date)) : '—',
                'exit_date' => $member->exit_date ? date('d F Y', strtotime($member->exit_date)) : '—',
                'status' => $member->status,
                'image' => $member->image && file_exists(public_path($member->image)) ? asset($member->image) : null,
                'addmissionform' => $member->addmissionform && file_exists(public_path($member->addmissionform)) ? asset($member->addmissionform) : null,
                'aadharimage' => $member->aadharimage && file_exists(public_path($member->aadharimage)) ? asset($member->aadharimage) : null,
                'payments' => $member->payments->map(function($payment) {
                    return [
                        'month' => $payment->month,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'paid_date' => $payment->paid_date ? date('d M Y', strtotime($payment->paid_date)) : '—'
                    ];
                })
            ]
        ]);
    }

    /**
     * Get member data for editing.
     */
    public function edit($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member = Member::with(['hostel', 'room', 'bed'])->findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'hostel_id' => $member->hostel_id,
                'room_id' => $member->room_id,
                'bed_id' => $member->bed_id,
                'with_food' => $member->with_food,
                'rent_amount' => $member->rent_amount,
                'join_date' => $member->join_date,
                'status' => $member->status,
                'image' => $member->image,
                'addmissionform' => $member->addmissionform,
                'aadharimage' => $member->aadharimage
            ]
        ]);
    }

    /**
     * Update the specified member.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member = Member::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'hostel_id' => 'required|exists:hostels,id',
            'room_id' => 'required|exists:rooms,id',
            'bed_id' => 'required|exists:beds,id',
            'with_food' => 'required|boolean',
            'join_date' => 'required|date',
            'status' => 'required|in:active,left',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'addmissionform' => 'nullable|mimes:pdf,jpeg,png,jpg|max:2048',
            'aadharimage' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Check bed availability if changing bed
        if ($member->bed_id != $request->bed_id) {
            $newBed = Bed::find($request->bed_id);
            if ($newBed->is_occupied) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected bed is already occupied!'
                ], 422);
            }
        }

        // Calculate rent based on room type
        $room = Room::find($request->room_id);
        $roomType = $room->roomType;
        $rentAmount = $request->with_food ? $roomType->rent_with_food : $roomType->rent_without_food;

        try {
            DB::beginTransaction();

            // Handle old bed
            if ($member->bed_id != $request->bed_id) {
                $oldBed = Bed::find($member->bed_id);
                if ($oldBed) {
                    $oldBed->is_occupied = false;
                    $oldBed->current_member_id = null;
                    $oldBed->save();
                }
            }

            $member->name = $request->name;
            $member->phone = $request->phone;
            $member->hostel_id = $request->hostel_id;
            $member->room_id = $request->room_id;
            $member->bed_id = $request->bed_id;
            $member->with_food = $request->with_food;
            $member->rent_amount = $rentAmount;
            $member->join_date = $request->join_date;
            $member->exit_date = $request->exit_date;
            $member->status = $request->status;

            // Upload new images
            $folderPath = "uploads/hostels/{$request->hostel_id}/rooms/{$request->room_id}/members/{$member->id}";

            if ($request->hasFile('image')) {
                if ($member->image && file_exists(public_path($member->image))) {
                    unlink(public_path($member->image));
                }
                $imageName = 'profile_' . time() . '.' . $request->image->extension();
                $request->image->move(public_path($folderPath), $imageName);
                $member->image = $folderPath . '/' . $imageName;
            }

            if ($request->hasFile('addmissionform')) {
                if ($member->addmissionform && file_exists(public_path($member->addmissionform))) {
                    unlink(public_path($member->addmissionform));
                }
                $formName = 'admission_' . time() . '.' . $request->addmissionform->extension();
                $request->addmissionform->move(public_path($folderPath), $formName);
                $member->addmissionform = $folderPath . '/' . $formName;
            }

            if ($request->hasFile('aadharimage')) {
                if ($member->aadharimage && file_exists(public_path($member->aadharimage))) {
                    unlink(public_path($member->aadharimage));
                }
                $aadharName = 'aadhar_' . time() . '.' . $request->aadharimage->extension();
                $request->aadharimage->move(public_path($folderPath), $aadharName);
                $member->aadharimage = $folderPath . '/' . $aadharName;
            }

            $member->save();

            // Mark new bed as occupied
            $newBed = Bed::find($request->bed_id);
            $newBed->is_occupied = true;
            $newBed->current_member_id = $member->id;
            $newBed->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member updated successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Member Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified member.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member = Member::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            // Free the bed
            $bed = Bed::find($member->bed_id);
            if ($bed) {
                $bed->is_occupied = false;
                $bed->current_member_id = null;
                $bed->save();
            }

            // Delete images folder
            $folderPath = "uploads/hostels/{$member->hostel_id}/rooms/{$member->room_id}/members/{$member->id}";
            if (file_exists(public_path($folderPath))) {
                $this->deleteDirectory(public_path($folderPath));
            }

            $member->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Member Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rooms by hostel (AJAX)
     */
    public function getRoomsByHostel($hostelId)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($hostelId)) {
            return response()->json(['success' => false, 'data' => [], 'message' => 'Unauthorized'], 403);
        }

        $rooms = Room::where('hostel_id', $hostelId)
            ->with('roomType')
            ->get()
            ->map(function($room) {
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'room_type' => $room->roomType->name ?? 'N/A',
                    'sharing' => $room->roomType->sharing ?? 0
                ];
            });

        return response()->json(['success' => true, 'data' => $rooms]);
    }

    /**
     * Get beds by room (AJAX)
     */
    public function getBedsByRoom($roomId)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $room = Room::find($roomId);
        if ($room) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($room->hostel_id)) {
                return response()->json(['success' => false, 'data' => [], 'message' => 'Unauthorized'], 403);
            }
        }

        $beds = Bed::where('room_id', $roomId)
            ->where('is_occupied', false)
            ->get()
            ->map(function($bed) {
                return [
                    'id' => $bed->id,
                    'bed_number' => $bed->bed_number,
                    'bed_type' => $bed->bed_type
                ];
            });

        return response()->json(['success' => true, 'data' => $beds]);
    }

    /**
     * Get rent by room and food preference (AJAX)
     */
    public function getRentByRoom($roomId, $withFood)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $room = Room::with('roomType')->find($roomId);
        if ($room) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($room->hostel_id)) {
                return response()->json(['success' => false, 'rent' => 0], 403);
            }
        }

        if ($room && $room->roomType) {
            $rent = $withFood == 'true' || $withFood == '1' ? $room->roomType->rent_with_food : $room->roomType->rent_without_food;
            return response()->json(['success' => true, 'rent' => $rent]);
        }
        return response()->json(['success' => false, 'rent' => 0]);
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
