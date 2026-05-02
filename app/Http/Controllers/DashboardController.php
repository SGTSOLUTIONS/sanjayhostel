<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hostel;
use App\Models\Room;
use App\Models\Bed;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // ✅ Hostel scope (Superadmin vs Admin)
        $hostelIds = $user->role === 'superadmin'
            ? Hostel::pluck('id')
            : $user->hostels()->pluck('hostel_id');

        // ✅ Basic counts
        $totalHostels = Hostel::whereIn('id', $hostelIds)->count();
        $totalRooms = Room::whereIn('hostel_id', $hostelIds)->count();
        $totalBeds = Bed::whereHas('room', function ($q) use ($hostelIds) {
            $q->whereIn('hostel_id', $hostelIds);
        })->count();
        $occupiedBeds = Bed::where('is_occupied', true)
            ->whereHas('room', function ($q) use ($hostelIds) {
                $q->whereIn('hostel_id', $hostelIds);
            })->count();
        $vacantBeds = $totalBeds - $occupiedBeds;
        $totalResidents = Member::whereIn('hostel_id', $hostelIds)
            ->where('status', 'active')
            ->count();

        // ✅ Get current month
        $currentMonth = now()->format('Y-m');
        $currentMonthAlt = now()->format('Y-n');

        // ✅ Monthly Income - Include BOTH 'paid' and 'pending' status payments
        // Because 'pending' means partial payment (money received)
        $monthlyIncome = Payment::where(function($query) use ($currentMonth, $currentMonthAlt) {
                $query->where('month', $currentMonth)
                      ->orWhere('month', $currentMonthAlt);
            })
            ->whereIn('status', ['paid', 'pending'])  // ← Include both paid and pending
            ->sum('amount');

        // ✅ Get all active members
        $members = Member::whereIn('hostel_id', $hostelIds)
            ->where('status', 'active')
            ->with('room')
            ->get();

        // ✅ Get current month payments - Include BOTH 'paid' and 'pending' status
        $currentMonthPayments = Payment::select(
                'member_id',
                DB::raw('SUM(amount) as total_paid')
            )
            ->where(function($query) use ($currentMonth, $currentMonthAlt) {
                $query->where('month', $currentMonth)
                      ->orWhere('month', $currentMonthAlt);
            })
            ->whereIn('status', ['paid', 'pending'])  // ← Include both paid and pending
            ->groupBy('member_id')
            ->pluck('total_paid', 'member_id');

        $totalPendingDues = 0;
        $membersWithPending = [];

        foreach ($members as $member) {
            $monthlyRent = $member->rent_amount ?? 0;

            if ($monthlyRent <= 0) {
                continue;
            }

            $totalPaid = $currentMonthPayments[$member->id] ?? 0;

            // ✅ PENDING = RENT - PAID (This is the remaining amount to be paid)
            $pending = $monthlyRent - $totalPaid;

            // Store ALL members (not just with pending > 0) to show their status
            $membersWithPending[] = [
                'id' => $member->id,
                'name' => $member->name,
                'room_number' => $member->room->room_number ?? 'N/A',
                'monthly_rent' => $monthlyRent,
                'total_paid' => $totalPaid,
                'pending_amount' => $pending > 0 ? $pending : 0,
                'payment_percentage' => $monthlyRent > 0 ? round(($totalPaid / $monthlyRent) * 100) : 0,
                'status' => $totalPaid >= $monthlyRent ? 'Fully Paid' : ($totalPaid > 0 ? 'Partial' : 'Unpaid')
            ];

            if ($pending > 0) {
                $totalPendingDues += $pending;
            }
        }

        // Count fully paid members
        $fullyPaidCount = 0;
        foreach ($members as $member) {
            $monthlyRent = $member->rent_amount ?? 0;
            if ($monthlyRent <= 0) continue;
            $totalPaid = $currentMonthPayments[$member->id] ?? 0;
            if ($totalPaid >= $monthlyRent) {
                $fullyPaidCount++;
            }
        }

        $totalExpected = $monthlyIncome + $totalPendingDues;
        $collectionRate = $totalExpected > 0 ? round(($monthlyIncome / $totalExpected) * 100) : 0;

        // ✅ Recent Residents
        $recentResidents = Member::with(['hostel', 'room'])
            ->whereIn('hostel_id', $hostelIds)
            ->latest()
            ->take(10)
            ->get();

        // ✅ Recent Payments
        $recentPayments = Payment::with('member')
            ->whereHas('member', function ($q) use ($hostelIds) {
                $q->whereIn('hostel_id', $hostelIds);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // ✅ Monthly Trend (last 12 months)
        $monthlyTrend = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = date('Y-m', strtotime(now()->format('Y') . '-' . $i . '-01'));
            $monthAlt = date('Y-n', strtotime(now()->format('Y') . '-' . $i . '-01'));

            $monthlyTrend[] = Payment::where(function($query) use ($month, $monthAlt) {
                    $query->where('month', $month)
                          ->orWhere('month', $monthAlt);
                })
                ->whereIn('status', ['paid', 'pending'])  // ← Include both
                ->sum('amount');
        }

        // ✅ Hostel Distribution
        $hostelDistribution = Hostel::whereIn('id', $hostelIds)
            ->withCount('rooms')
            ->get()
            ->map(function ($hostel) {
                return [
                    'name' => $hostel->name,
                    'total_rooms' => $hostel->rooms_count
                ];
            });

        return view('admin.dashboard', compact(
            'totalHostels',
            'totalRooms',
            'totalBeds',
            'occupiedBeds',
            'vacantBeds',
            'totalResidents',
            'monthlyIncome',
            'totalPendingDues',
            'recentResidents',
            'recentPayments',
            'monthlyTrend',
            'hostelDistribution',
            'membersWithPending',
            'fullyPaidCount',
            'collectionRate',
            'currentMonth'
        ));
    }
}
