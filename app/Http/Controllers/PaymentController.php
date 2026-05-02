<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Member;
use App\Models\Hostel;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
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
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $query = Payment::with(['member', 'member.hostel', 'member.room'])
            ->whereHas('member.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            });

        // Filters
        if ($request->has('member_id') && $request->member_id) {
            $query->where('member_id', $request->member_id);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('month') && $request->month) {
            $query->where('month', $request->month);
        }

        $payments = $query->latest()->paginate(15);

        // Only accessible members and hostels
        $members = Member::whereIn('hostel_id', $allowedHostelIds)
            ->where('status', 'active')
            ->get();
        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();

        // ✅ STATS
        $currentMonth = date('Y-m');

        // Total collected
        $totalCollected = Payment::whereIn('status', ['paid', 'pending'])
            ->whereHas('member.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })
            ->sum('amount');

        // Total from full payments only
        $totalFullPayments = Payment::where('status', 'paid')
            ->whereHas('member.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })
            ->sum('amount');

        // Total from partial payments only
        $totalPartialPayments = Payment::where('status', 'pending')
            ->whereHas('member.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })
            ->sum('amount');

        // This month collected
        $thisMonthCollected = Payment::whereIn('status', ['paid', 'pending'])
            ->where('month', $currentMonth)
            ->whereHas('member.hostel', function($q) use ($allowedHostelIds) {
                $q->whereIn('id', $allowedHostelIds);
            })
            ->sum('amount');

        // Total transactions count
        $totalPayments = Payment::whereHas('member.hostel', function($q) use ($allowedHostelIds) {
            $q->whereIn('id', $allowedHostelIds);
        })->count();

        // ✅ Calculate total pending dues
        $totalPendingDues = 0;
        $membersWithDues = [];

        $activeMembers = Member::whereIn('hostel_id', $allowedHostelIds)
            ->where('status', 'active')
            ->get();

        foreach ($activeMembers as $member) {
            $monthlyRent = $member->rent_amount ?? 0;
            if ($monthlyRent <= 0) continue;

            $totalPaidThisMonth = Payment::where('member_id', $member->id)
                ->where('month', $currentMonth)
                ->whereIn('status', ['paid', 'pending'])
                ->sum('amount');

            $pendingAmount = $monthlyRent - $totalPaidThisMonth;

            if ($pendingAmount > 0) {
                $totalPendingDues += $pendingAmount;
                $membersWithDues[] = [
                    'member' => $member,
                    'pending_amount' => $pendingAmount,
                    'monthly_rent' => $monthlyRent,
                    'paid_amount' => $totalPaidThisMonth
                ];
            }
        }

        $totalExpected = $totalCollected + $totalPendingDues;
        $collectionRate = $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100) : 0;

        $fullyPaidCount = 0;
        foreach ($activeMembers as $member) {
            $monthlyRent = $member->rent_amount ?? 0;
            if ($monthlyRent <= 0) continue;

            $totalPaid = Payment::where('member_id', $member->id)
                ->where('month', $currentMonth)
                ->whereIn('status', ['paid', 'pending'])
                ->sum('amount');

            if ($totalPaid >= $monthlyRent) {
                $fullyPaidCount++;
            }
        }

        $totalMembers = $activeMembers->count();
        $paymentProgress = $totalMembers > 0 ? round(($fullyPaidCount / $totalMembers) * 100) : 0;

        return view('admin.payments.index', compact(
            'payments', 'members', 'hostels',
            'totalCollected', 'totalPendingDues', 'collectionRate',
            'thisMonthCollected', 'totalPayments',
            'totalFullPayments', 'totalPartialPayments',
            'fullyPaidCount', 'totalMembers', 'paymentProgress',
            'currentMonth', 'membersWithDues'
        ));
    }

    /**
     * Get previous pending dues for a member
     */
    private function getPreviousPendingDues($memberId, $currentMonth)
    {
        $member = Member::find($memberId);

        if (!$member) {
            return ['total_pending' => 0, 'months' => []];
        }

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($member->hostel_id)) {
            return ['total_pending' => 0, 'months' => []];
        }

        $monthlyRent = $member->rent_amount ?? 0;

        $previousMonths = Payment::where('member_id', $memberId)
            ->where('month', '<', $currentMonth)
            ->select('month', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('month')
            ->get();

        $totalPendingDues = 0;
        $pendingMonths = [];

        foreach ($previousMonths as $monthData) {
            $pendingForMonth = $monthlyRent - $monthData->total_paid;

            if ($pendingForMonth > 0) {
                $totalPendingDues += $pendingForMonth;
                $pendingMonths[] = [
                    'month' => $monthData->month,
                    'month_name' => date('F Y', strtotime($monthData->month . '-01')),
                    'pending' => $pendingForMonth,
                    'rent' => $monthlyRent,
                    'paid' => $monthData->total_paid
                ];
            }
        }

        return [
            'total_pending' => $totalPendingDues,
            'months' => $pendingMonths
        ];
    }

    /**
     * Store a newly created payment with previous dues check.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'member_id' => 'required|exists:members,id',
            'month' => 'required|string|max:7',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:paid,pending',
            'paid_date' => 'nullable|date',
        ]);

        $member = Member::find($request->member_id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $monthlyRent = $member->rent_amount ?? 0;
        $currentMonth = $request->month;

        // CHECK FOR PREVIOUS PENDING DUES
        $previousDues = $this->getPreviousPendingDues($member->id, $currentMonth);

        if ($previousDues['total_pending'] > 0) {
            $amountPaid = $request->amount;

            if ($amountPaid < $previousDues['total_pending']) {
                $pendingMonthsList = '';
                foreach ($previousDues['months'] as $pending) {
                    $pendingMonthsList .= "\n- " . $pending['month_name'] . ": ₹" . number_format($pending['pending']);
                }

                return response()->json([
                    'success' => false,
                    'message' => "Cannot accept payment. Member has pending dues from previous months:{$pendingMonthsList}\n\nTotal pending: ₹" . number_format($previousDues['total_pending']) . "\nPlease clear previous dues first.",
                    'previous_dues' => $previousDues
                ], 422);
            } else {
                $remainingAmount = $amountPaid - $previousDues['total_pending'];

                DB::beginTransaction();
                try {
                    // Clear previous month dues
                    foreach ($previousDues['months'] as $pending) {
                        $shortfall = $pending['pending'];
                        if ($shortfall > 0) {
                            $clearPayment = new Payment();
                            $clearPayment->member_id = $member->id;
                            $clearPayment->month = $pending['month'];
                            $clearPayment->amount = $shortfall;
                            $clearPayment->status = 'paid';
                            $clearPayment->paid_date = now();
                            $clearPayment->save();
                        }
                    }

                    // Handle current month payment
                    if ($remainingAmount > 0) {
                        $existingCurrent = Payment::where('member_id', $member->id)
                            ->where('month', $currentMonth)
                            ->first();

                        $currentStatus = $remainingAmount >= $monthlyRent ? 'paid' : 'pending';

                        if ($existingCurrent) {
                            $existingCurrent->amount = $remainingAmount;
                            $existingCurrent->status = $currentStatus;
                            $existingCurrent->paid_date = $currentStatus == 'paid' ? now() : null;
                            $existingCurrent->save();
                        } else {
                            $currentPayment = new Payment();
                            $currentPayment->member_id = $member->id;
                            $currentPayment->month = $currentMonth;
                            $currentPayment->amount = $remainingAmount;
                            $currentPayment->status = $currentStatus;
                            $currentPayment->paid_date = $currentStatus == 'paid' ? now() : null;
                            $currentPayment->save();
                        }
                    }

                    DB::commit();

                    $message = "Previous dues cleared successfully! ";
                    if ($remainingAmount > 0) {
                        $message .= ($remainingAmount >= $monthlyRent ? "Full payment" : "Partial payment of ₹" . number_format($remainingAmount)) . " recorded for current month.";
                    } else {
                        $message .= "No payment recorded for current month.";
                    }

                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'previous_dues_cleared' => true,
                        'remaining_for_current' => $remainingAmount
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Payment Creation Error (Previous Dues): ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to process payment: ' . $e->getMessage()
                    ], 500);
                }
            }
        }

        // NO PREVIOUS DUES - Normal payment processing
        $existing = Payment::where('member_id', $request->member_id)
            ->where('month', $request->month)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Payment for this month already exists! You can edit it.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment = new Payment();
            $payment->member_id = $request->member_id;
            $payment->month = $request->month;
            $payment->amount = $request->amount;
            $payment->status = $request->status;
            $payment->paid_date = $request->paid_date ?? ($request->status == 'paid' ? now() : null);
            $payment->save();

            DB::commit();

            $payment->load('member');

            $message = $request->status == 'paid'
                ? 'Full payment recorded successfully!'
                : 'Partial payment recorded successfully!';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details for viewing.
     */
    public function show($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = Payment::with(['member', 'member.hostel', 'member.room', 'member.room.beds'])->findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($payment->member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member = $payment->member;
        $monthlyRent = $member->rent_amount ?? 0;

        $totalPaid = Payment::where('member_id', $member->id)
            ->where('month', $payment->month)
            ->whereIn('status', ['paid', 'pending'])
            ->sum('amount');

        $pendingAmount = max(0, $monthlyRent - $totalPaid);
        $previousDues = $this->getPreviousPendingDues($member->id, $payment->month);
        $bedNumbers = $member->room->beds->where('is_occupied', true)->pluck('bed_number')->implode(', ');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'member_name' => $member->name ?? 'N/A',
                'member_phone' => $member->phone ?? 'N/A',
                'member_email' => $member->email ?? 'N/A',
                'hostel_name' => $member->hostel->name ?? 'N/A',
                'room_number' => $member->room->room_number ?? 'N/A',
                'bed_numbers' => $bedNumbers ?: 'N/A',
                'monthly_rent' => $monthlyRent,
                'month' => $payment->month,
                'month_name' => date('F Y', strtotime($payment->month . '-01')),
                'amount_paid' => $payment->amount,
                'total_paid_all' => $totalPaid,
                'pending_amount' => $pendingAmount,
                'previous_dues_total' => $previousDues['total_pending'],
                'previous_dues_months' => $previousDues['months'],
                'status' => $payment->status,
                'paid_date' => $payment->paid_date ? date('d F Y', strtotime($payment->paid_date)) : '—',
                'created_at' => $payment->created_at ? date('d M Y h:i A', strtotime($payment->created_at)) : '—'
            ]
        ]);
    }

    /**
     * Get members by hostel with search/filter options.
     */
    public function getMembersByHostel(Request $request, $hostelId)
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

        $query = Member::where('hostel_id', $hostelId)
            ->where('status', 'active')
            ->with(['room', 'room.beds']);

        // Apply search filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhereHas('room', function($roomQuery) use ($search) {
                      $roomQuery->where('room_number', 'like', "%{$search}%")
                                ->orWhereHas('beds', function($bedQuery) use ($search) {
                                    $bedQuery->where('bed_number', 'like', "%{$search}%");
                                });
                  });
            });
        }

        // Filter by room number
        if ($request->has('room_number') && $request->room_number) {
            $query->whereHas('room', function($q) use ($request) {
                $q->where('room_number', $request->room_number);
            });
        }

        $members = $query->get()->map(function($member) {
            $currentMonth = date('Y-m');
            $monthlyRent = $member->rent_amount ?? 0;

            $totalPaidCurrent = Payment::where('member_id', $member->id)
                ->where('month', $currentMonth)
                ->whereIn('status', ['paid', 'pending'])
                ->sum('amount');

            $currentPending = max(0, $monthlyRent - $totalPaidCurrent);
            $previousDues = $this->getPreviousPendingDues($member->id, $currentMonth);
            $bedNumbers = $member->room->beds->where('is_occupied', true)->pluck('bed_number')->implode(', ');

            return [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'email' => $member->email,
                'room_number' => $member->room->room_number ?? 'N/A',
                'room_id' => $member->room_id,
                'bed_numbers' => $bedNumbers ?: 'N/A',
                'monthly_rent' => $monthlyRent,
                'total_paid_current' => $totalPaidCurrent,
                'current_pending' => $currentPending,
                'previous_dues_total' => $previousDues['total_pending'],
                'previous_dues_months' => $previousDues['months'],
                'total_pending_overall' => $previousDues['total_pending'] + $currentPending,
                'payment_status' => $previousDues['total_pending'] + $currentPending == 0 ? 'paid' : ($totalPaidCurrent > 0 ? 'partial' : 'unpaid'),
                'has_previous_dues' => $previousDues['total_pending'] > 0
            ];
        });

        return response()->json(['success' => true, 'data' => $members]);
    }

    /**
     * Get rooms by hostel for filtering
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
            ->where('status', 'active')
            ->select('id', 'room_number')
            ->orderBy('room_number')
            ->get();

        return response()->json(['success' => true, 'data' => $rooms]);
    }

    /**
     * Check if payment exists for a member in a month
     */
    public function checkPaymentExists($memberId, $month)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member = Member::find($memberId);
        if ($member) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($member->hostel_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $payment = Payment::where('member_id', $memberId)
            ->where('month', $month)
            ->first();

        $totalPaid = Payment::where('member_id', $memberId)
            ->where('month', $month)
            ->whereIn('status', ['paid', 'pending'])
            ->sum('amount');

        $previousDues = $this->getPreviousPendingDues($memberId, $month);

        return response()->json([
            'success' => true,
            'exists' => !is_null($payment),
            'payment' => $payment,
            'total_paid' => $totalPaid,
            'previous_dues' => $previousDues
        ]);
    }

    /**
     * Get payment data for editing.
     */
    public function edit($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = Payment::with('member')->findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($payment->member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $totalPaidForMonth = Payment::where('member_id', $payment->member_id)
            ->where('month', $payment->month)
            ->sum('amount');

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'member_id' => $payment->member_id,
                'member_name' => $payment->member->name ?? 'N/A',
                'monthly_rent' => $payment->member->rent_amount ?? 0,
                'month' => $payment->month,
                'amount' => $payment->amount,
                'total_paid_this_month' => $totalPaidForMonth,
                'status' => $payment->status,
                'paid_date' => $payment->paid_date
            ]
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = Payment::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($payment->member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:paid,pending',
            'paid_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $payment->amount = $request->amount;
            $payment->status = $request->status;
            $payment->paid_date = $request->status == 'paid' ? ($request->paid_date ?? now()) : null;
            $payment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified payment.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = Payment::findOrFail($id);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($payment->member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending dues report
     */
    public function pendingDues()
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403);
        }

        $allowedHostelIds = $this->getAllowedHostelIds();
        $currentMonth = date('Y-m');

        $members = Member::whereIn('hostel_id', $allowedHostelIds)
            ->where('status', 'active')
            ->with('room', 'hostel', 'room.beds')
            ->get();

        $pendingDuesList = [];
        $totalPendingAmount = 0;

        foreach ($members as $member) {
            $monthlyRent = $member->rent_amount ?? 0;
            if ($monthlyRent <= 0) continue;

            $totalPaidCurrent = Payment::where('member_id', $member->id)
                ->where('month', $currentMonth)
                ->whereIn('status', ['paid', 'pending'])
                ->sum('amount');

            $currentPending = max(0, $monthlyRent - $totalPaidCurrent);
            $previousDues = $this->getPreviousPendingDues($member->id, $currentMonth);
            $totalPending = $previousDues['total_pending'] + $currentPending;

            if ($totalPending > 0) {
                $bedNumbers = $member->room->beds->where('is_occupied', true)->pluck('bed_number')->implode(', ');

                $pendingDuesList[] = [
                    'member' => $member,
                    'bed_numbers' => $bedNumbers,
                    'monthly_rent' => $monthlyRent,
                    'paid_current' => $totalPaidCurrent,
                    'current_pending' => $currentPending,
                    'previous_dues_total' => $previousDues['total_pending'],
                    'previous_dues_months' => $previousDues['months'],
                    'total_pending' => $totalPending,
                    'payment_percentage' => $monthlyRent > 0 ? round((($monthlyRent - $totalPending) / $monthlyRent) * 100) : 0
                ];
                $totalPendingAmount += $totalPending;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_month' => $currentMonth,
                'current_month_name' => date('F Y'),
                'total_pending' => $totalPendingAmount,
                'members_count' => count($pendingDuesList),
                'pending_list' => $pendingDuesList
            ]
        ]);
    }

    /**
     * Get member's complete payment history
     */
    public function memberPaymentHistory($memberId)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $member = Member::with('room', 'hostel', 'room.beds')->findOrFail($memberId);

        // Check access
        $allowedHostelIds = $this->getAllowedHostelIds();
        if (!$allowedHostelIds->contains($member->hostel_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $monthlyRent = $member->rent_amount ?? 0;

        $payments = Payment::where('member_id', $memberId)
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy('month');

        $paymentHistory = [];
        $totalPaidAllTime = 0;

        $allMonths = [];
        if ($payments->isNotEmpty()) {
            $firstMonth = $payments->keys()->min();
            $currentMonth = date('Y-m');
            $startDate = new \DateTime($firstMonth . '-01');
            $endDate = new \DateTime($currentMonth . '-01');
            $interval = new \DateInterval('P1M');
            $period = new \DatePeriod($startDate, $interval, $endDate->modify('+1 month'));

            foreach ($period as $date) {
                $allMonths[] = $date->format('Y-m');
            }
        }

        foreach ($allMonths as $month) {
            $monthPayments = $payments->get($month, collect());
            $totalPaidForMonth = $monthPayments->sum('amount');
            $totalPaidAllTime += $totalPaidForMonth;
            $pendingForMonth = max(0, $monthlyRent - $totalPaidForMonth);

            $paymentHistory[] = [
                'month' => $month,
                'month_name' => date('F Y', strtotime($month . '-01')),
                'rent' => $monthlyRent,
                'paid' => $totalPaidForMonth,
                'pending' => $pendingForMonth,
                'status' => $pendingForMonth == 0 ? 'Fully Paid' : ($totalPaidForMonth > 0 ? 'Partial' : 'Unpaid'),
                'status_class' => $pendingForMonth == 0 ? 'success' : ($totalPaidForMonth > 0 ? 'warning' : 'danger'),
                'payments' => $monthPayments->map(function($payment) {
                    return [
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'paid_date' => $payment->paid_date ? date('d M Y', strtotime($payment->paid_date)) : '—',
                        'created_at' => $payment->created_at ? date('d M Y h:i A', strtotime($payment->created_at)) : '—'
                    ];
                })
            ];
        }

        $currentMonth = date('Y-m');
        $currentMonthPaid = Payment::where('member_id', $memberId)
            ->where('month', $currentMonth)
            ->sum('amount');
        $currentMonthPending = max(0, $monthlyRent - $currentMonthPaid);

        $bedNumbers = $member->room->beds->where('is_occupied', true)->pluck('bed_number')->implode(', ');

        return response()->json([
            'success' => true,
            'data' => [
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'phone' => $member->phone,
                    'email' => $member->email,
                    'hostel' => $member->hostel->name ?? 'N/A',
                    'room_number' => $member->room->room_number ?? 'N/A',
                    'bed_numbers' => $bedNumbers ?: 'N/A',
                    'monthly_rent' => $monthlyRent,
                    'joined_date' => $member->created_at ? date('d M Y', strtotime($member->created_at)) : '—'
                ],
                'current_month_status' => [
                    'month' => $currentMonth,
                    'month_name' => date('F Y'),
                    'paid' => $currentMonthPaid,
                    'pending' => $currentMonthPending,
                    'status' => $currentMonthPending == 0 ? 'Fully Paid' : ($currentMonthPaid > 0 ? 'Partial' : 'Unpaid')
                ],
                'payment_history' => $paymentHistory,
                'total_paid_all_time' => $totalPaidAllTime,
                'summary' => [
                    'total_months' => count($paymentHistory),
                    'fully_paid_months' => collect($paymentHistory)->where('pending', 0)->count(),
                    'partial_months' => collect($paymentHistory)->where('pending', '>', 0)->where('paid', '>', 0)->count(),
                    'unpaid_months' => collect($paymentHistory)->where('paid', 0)->count()
                ]
            ]
        ]);
    }
}
