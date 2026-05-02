<?php
// app/Http/Controllers/HostelController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hostel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HostelController extends Controller
{
    /**
     * Display a listing of the hostels (PGs).
     */
    public function index()
    {
        // Check if user is super admin
        if (auth()->user()->role !== 'superadmin') {
            abort(403, 'Unauthorized access. Only Super Admin can manage PGs.');
        }

        $hostels = Hostel::with('creator')->latest()->paginate(10);

        // Stats for dashboard
        $totalHostels = Hostel::count();
        $mensHostels = Hostel::where('type', 'mens')->count();
        $womensHostels = Hostel::where('type', 'womens')->count();

        return view('admin.pgs.index', compact('hostels', 'totalHostels', 'mensHostels', 'womensHostels'));
    }

    /**
     * Store a newly created hostel in storage.
     */
    public function store(Request $request)
    {
        // Check if user is super admin
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:hostels,name',
            'type' => 'required|in:mens,womens',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $hostel = Hostel::create([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'city' => $request->city,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PG created successfully!',
                    'data' => $hostel
                ]);
            }

            return redirect()->route('pgs.index')->with('success', 'PG created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create PG: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to create PG: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified hostel.
     */
    public function update(Request $request, $id)
    {
        // Check if user is super admin
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $hostel = Hostel::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:hostels,name,' . $id,
            'type' => 'required|in:mens,womens',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $hostel->update([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'city' => $request->city,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PG updated successfully!',
                    'data' => $hostel
                ]);
            }

            return redirect()->route('pgs.index')->with('success', 'PG updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update PG: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update PG: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified hostel.
     */
    public function destroy($id)
    {
        // Check if user is super admin
        if (auth()->user()->role !== 'superadmin') {
            if (request()->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access. Only Super Admin can delete PGs.');
        }

        $hostel = Hostel::findOrFail($id);

        // Check if hostel has any related records
        if ($hostel->rooms()->count() > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete PG because it has associated rooms. Please delete rooms first.'
                ], 400);
            }
            return back()->with('error', 'Cannot delete PG because it has associated rooms.');
        }

        try {
            $hostel->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PG deleted successfully!'
                ]);
            }

            return redirect()->route('pgs.index')->with('success', 'PG deleted successfully!');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete PG: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete PG: ' . $e->getMessage());
        }
    }

    /**
     * Get hostel details for editing (AJAX)
     */
    public function edit($id)
    {
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $hostel = Hostel::findOrFail($id);
        return response()->json($hostel);
    }
}
