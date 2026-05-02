<?php
// app/Http/Controllers/RoomTypeController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomType;
use App\Models\Hostel;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomTypeController extends Controller
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
     * Display a listing of room types.
     */
    public function index()
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $roomTypes = RoomType::latest()->paginate(10);

        // Stats for dashboard
        $totalTypes = RoomType::count();
        $acTypes = RoomType::where('is_ac', true)->count();
        $nonAcTypes = RoomType::where('is_ac', false)->count();
        $totalNormalCots = RoomType::sum('normal_cot_count');
        $totalBunkerCots = RoomType::sum('bunker_cot_count');
        $totalCots = $totalNormalCots + $totalBunkerCots;

        return view('admin.room-types.index', compact(
            'roomTypes',
            'totalTypes',
            'acTypes',
            'nonAcTypes',
            'totalNormalCots',
            'totalBunkerCots',
            'totalCots'
        ));
    }

    /**
     * Store a newly created room type.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name',
            'is_ac' => 'required|boolean',
            'sharing' => 'required|integer|min:1|max:50',
            'normal_cot_count' => 'required|integer|min:0',
            'bunker_cot_count' => 'required|integer|min:0',
            'rent_with_food' => 'required|numeric|min:0',
            'rent_without_food' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Validate that total cots match sharing count
        $totalCots = $request->normal_cot_count + $request->bunker_cot_count;
        if ($totalCots != $request->sharing) {
            return response()->json([
                'success' => false,
                'message' => "Total cots ({$totalCots}) must equal sharing count ({$request->sharing})"
            ], 422);
        }

        try {
            DB::beginTransaction();

            $roomType = RoomType::create([
                'name' => $request->name,
                'is_ac' => $request->is_ac,
                'sharing' => $request->sharing,
                'normal_cot_count' => $request->normal_cot_count,
                'bunker_cot_count' => $request->bunker_cot_count,
                'rent_with_food' => $request->rent_with_food,
                'rent_without_food' => $request->rent_without_food,
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room type created successfully!',
                'data' => $roomType
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Room Type Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create room type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get room type details for editing.
     */
    public function edit($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $roomType = RoomType::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $roomType
        ]);
    }

    /**
     * Update the specified room type.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $roomType = RoomType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name,' . $id,
            'is_ac' => 'required|boolean',
            'sharing' => 'required|integer|min:1|max:50',
            'normal_cot_count' => 'required|integer|min:0',
            'bunker_cot_count' => 'required|integer|min:0',
            'rent_with_food' => 'required|numeric|min:0',
            'rent_without_food' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Validate that total cots match sharing count
        $totalCots = $request->normal_cot_count + $request->bunker_cot_count;
        if ($totalCots != $request->sharing) {
            return response()->json([
                'success' => false,
                'message' => "Total cots ({$totalCots}) must equal sharing count ({$request->sharing})"
            ], 422);
        }

        try {
            DB::beginTransaction();

            $roomType->update([
                'name' => $request->name,
                'is_ac' => $request->is_ac,
                'sharing' => $request->sharing,
                'normal_cot_count' => $request->normal_cot_count,
                'bunker_cot_count' => $request->bunker_cot_count,
                'rent_with_food' => $request->rent_with_food,
                'rent_without_food' => $request->rent_without_food,
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Room type updated successfully!',
                'data' => $roomType
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Room Type Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update room type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified room type.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $roomType = RoomType::findOrFail($id);

        // Check if room type is being used in any room
        if ($roomType->rooms()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this room type because it is being used in rooms.'
            ], 400);
        }

        try {
            $roomType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room type deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Room Type Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room type: ' . $e->getMessage()
            ], 500);
        }
    }
}
