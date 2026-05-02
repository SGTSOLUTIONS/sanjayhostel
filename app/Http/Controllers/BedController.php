<?php
// app/Http/Controllers/BedController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bed;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Member;
use App\Models\Hostel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BedController extends Controller
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
     * Display a listing of beds.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $query = Bed::with(['room.hostel', 'currentMember'])
            ->whereHas('room.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            });

        // Filter by room
        if ($request->has('room_id') && $request->room_id) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by occupancy status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_occupied', $request->status);
        }

        // Filter by bed type
        if ($request->has('bed_type') && $request->bed_type) {
            $query->where('bed_type', $request->bed_type);
        }

        $beds = $query->latest()->paginate(20);

        // Get all rooms for filter (only accessible rooms)
        $rooms = Room::whereHas('hostel', function($q) use ($allowedHostelIds) {
            $q->whereIn('id', $allowedHostelIds);
        })->with(['hostel', 'roomType'])->get();

        // Get all members for assignment (only from accessible hostels)
        $members = Member::whereIn('hostel_id', $allowedHostelIds)
            ->where('status', 'active')
            ->get();

        // Stats
        $totalBeds = Bed::whereHas('room.hostel', function($q) use ($allowedHostelIds) {
            $q->whereIn('id', $allowedHostelIds);
        })->count();

        $occupiedBeds = Bed::where('is_occupied', true)
            ->whereHas('room.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })->count();

        $vacantBeds = $totalBeds - $occupiedBeds;
        $normalBeds = Bed::where('bed_type', 'normal')
            ->whereHas('room.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })->count();
        $bunkerBeds = Bed::where('bed_type', 'bunker')
            ->whereHas('room.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })->count();

        return view('admin.beds.index', compact(
            'beds',
            'rooms',
            'members',
            'totalBeds',
            'occupiedBeds',
            'vacantBeds',
            'normalBeds',
            'bunkerBeds'
        ));
    }

    /**
     * Show form for creating beds (for existing room without beds).
     */
    public function create(Request $request, $roomId = null)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $rooms = Room::with('hostel', 'roomType')
            ->whereHas('hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })
            ->whereDoesntHave('beds')
            ->get();

        return view('admin.beds.create', compact('rooms'));
    }

    /**
     * Store beds for a room (manual addition).
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'normal_cot_count' => 'required|integer|min:0',
            'bunker_cot_count' => 'required|integer|min:0',
        ]);

        $room = Room::find($request->room_id);

        // Check if admin has access to this room's hostel
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($room->hostel_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this hostel.'
            ], 403);
        }

        $roomType = $room->roomType;

        // If room type has configuration, suggest using that
        if ($roomType && ($roomType->normal_cot_count > 0 || $roomType->bunker_cot_count > 0)) {
            $totalFromType = $roomType->normal_cot_count + $roomType->bunker_cot_count;
            $requestedTotal = $request->normal_cot_count + $request->bunker_cot_count;

            if ($totalFromType != $requestedTotal) {
                return response()->json([
                    'success' => false,
                    'message' => "This room type should have {$roomType->normal_cot_count} normal and {$roomType->bunker_cot_count} bunker beds (Total: {$totalFromType}). You are adding {$requestedTotal} beds."
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $existingBedsCount = $room->beds()->count();
            $bedNumber = $existingBedsCount + 1;

            // Create normal cots
            for ($i = 1; $i <= $request->normal_cot_count; $i++) {
                Bed::create([
                    'room_id' => $request->room_id,
                    'bed_number' => $bedNumber++,
                    'bed_type' => 'normal',
                    'is_occupied' => false,
                ]);
            }

            // Create bunker cots
            for ($i = 1; $i <= $request->bunker_cot_count; $i++) {
                Bed::create([
                    'room_id' => $request->room_id,
                    'bed_number' => $bedNumber++,
                    'bed_type' => 'bunker',
                    'is_occupied' => false,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ($request->normal_cot_count + $request->bunker_cot_count) . ' beds created successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bed Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create beds: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update bed (assign/vacate).
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $bed = Bed::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($bed->room->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'is_occupied' => 'required|boolean',
            'current_member_id' => 'nullable|exists:members,id',
        ]);

        try {
            $bed->update([
                'is_occupied' => $request->is_occupied,
                'current_member_id' => $request->current_member_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bed updated successfully!',
                'data' => $bed->load('currentMember')
            ]);

        } catch (\Exception $e) {
            Log::error('Bed Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update bed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a bed.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $bed = Bed::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($bed->room->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($bed->is_occupied) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete occupied bed. Please vacate the bed first.'
            ], 400);
        }

        try {
            $bed->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bed deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Bed Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if room already has beds.
     */
    public function checkRoomHasBeds($roomId)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $room = Room::find($roomId);

        // Check access
        if ($room) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($room->hostel_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $hasBeds = $room && $room->beds()->count() > 0;
        $roomType = $room->roomType;

        return response()->json([
            'has_beds' => $hasBeds,
            'room_type' => $roomType ? [
                'name' => $roomType->name,
                'normal_cot_count' => $roomType->normal_cot_count,
                'bunker_cot_count' => $roomType->bunker_cot_count,
                'total_beds' => $roomType->normal_cot_count + $roomType->bunker_cot_count
            ] : null
        ]);
    }

    /**
     * Get members by room for assignment.
     */
    public function getMembersByRoom($bedId)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $bed = Bed::with('room')->find($bedId);

        if (!$bed) {
            return response()->json(['success' => false, 'data' => []]);
        }

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($bed->room->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $members = Member::where('room_id', $bed->room_id)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $members
        ]);
    }
}
