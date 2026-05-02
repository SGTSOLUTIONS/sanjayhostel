<?php
// app/Http/Controllers/RoomController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Hostel;
use App\Models\RoomType;
use App\Models\Bed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
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
     * Display a listing of rooms.
     */
    public function index()
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $rooms = Room::with(['hostel', 'roomType'])
            ->whereIn('hostel_id', $allowedHostelIds)
            ->latest()
            ->paginate(10);

        // Get hostels and room types for filters (only accessible ones)
        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();
        $roomTypes = RoomType::all();

        // Stats for dashboard
        $totalRooms = Room::whereIn('hostel_id', $allowedHostelIds)->count();
        $totalRoomsByHostel = Hostel::whereIn('id', $allowedHostelIds)
            ->withCount('rooms')
            ->get();

        return view('admin.rooms.index', compact('rooms', 'hostels', 'roomTypes', 'totalRooms', 'totalRoomsByHostel'));
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $request->validate([
            'hostel_id' => 'required|exists:hostels,id',
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:50',
        ]);

        // Check if admin has access to this hostel
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($request->hostel_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this hostel.'
            ], 403);
        }

        // Check if room number already exists in the same hostel
        $exists = Room::where('hostel_id', $request->hostel_id)
            ->where('room_number', $request->room_number)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Room number already exists in this hostel'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $room = Room::create([
                'hostel_id' => $request->hostel_id,
                'room_type_id' => $request->room_type_id,
                'room_number' => $request->room_number,
            ]);

            // Create beds based on room type configuration
            $roomType = RoomType::find($request->room_type_id);
            $this->createBedsForRoom($room->id, $roomType);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully with ' . ($roomType->normal_cot_count + $roomType->bunker_cot_count) . ' beds!',
                'data' => $room->load(['hostel', 'roomType'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Room Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create beds for a room based on room type
     */
    private function createBedsForRoom($roomId, $roomType)
    {
        $bedNumber = 1;

        // Create normal cots
        for ($i = 1; $i <= $roomType->normal_cot_count; $i++) {
            Bed::create([
                'room_id' => $roomId,
                'bed_number' => $bedNumber++,
                'bed_type' => 'normal',
                'is_occupied' => false,
            ]);
        }

        // Create bunker cots
        for ($i = 1; $i <= $roomType->bunker_cot_count; $i++) {
            Bed::create([
                'room_id' => $roomId,
                'bed_number' => $bedNumber++,
                'bed_type' => 'bunker',
                'is_occupied' => false,
            ]);
        }
    }

    /**
     * Get room details for editing.
     */
    public function edit($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $room = Room::with(['hostel', 'roomType'])->findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($room->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $room = Room::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($room->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'hostel_id' => 'required|exists:hostels,id',
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:50',
        ]);

        // Check if room number already exists in the same hostel (excluding current room)
        $exists = Room::where('hostel_id', $request->hostel_id)
            ->where('room_number', $request->room_number)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Room number already exists in this hostel'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldRoomTypeId = $room->room_type_id;

            $room->update([
                'hostel_id' => $request->hostel_id,
                'room_type_id' => $request->room_type_id,
                'room_number' => $request->room_number,
            ]);

            // If room type changed, update beds
            if ($oldRoomTypeId != $request->room_type_id) {
                // Delete existing beds
                $room->beds()->delete();

                // Create new beds based on new room type
                $newRoomType = RoomType::find($request->room_type_id);
                $this->createBedsForRoom($room->id, $newRoomType);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully!',
                'data' => $room->load(['hostel', 'roomType'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Room Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified room.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $room = Room::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($room->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if room has any members
        if ($room->members()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this room because it has members assigned.'
            ], 400);
        }

        try {
            // Delete associated beds first
            $room->beds()->delete();

            // Delete the room
            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Room Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rooms by hostel (for AJAX filtering)
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
                    'total_beds' => $room->beds()->count(),
                    'occupied_beds' => $room->beds()->where('is_occupied', true)->count()
                ];
            });

        return response()->json(['success' => true, 'data' => $rooms]);
    }
}
