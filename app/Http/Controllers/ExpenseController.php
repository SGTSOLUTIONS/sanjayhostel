<?php
// app/Http/Controllers/ExpenseController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Hostel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
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
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $query = Expense::with('creator', 'hostel')
            ->accessible($allowedHostelIds);

        // Apply filters
        if ($request->has('month') && $request->month) {
            $query->where('month', $request->month);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('expense_name', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $expenses = $query->latest()->paginate(15);

        // Get hostels for filter
        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();

        // Stats
        $currentMonth = date('Y-m');

        $totalExpenses = Expense::accessible($allowedHostelIds)->sum('amount');
        $currentMonthExpenses = Expense::accessible($allowedHostelIds)
            ->where('month', $currentMonth)
            ->sum('amount');

        $previousMonth = date('Y-m', strtotime('-1 month'));
        $previousMonthExpenses = Expense::accessible($allowedHostelIds)
            ->where('month', $previousMonth)
            ->sum('amount');

        $momChange = $previousMonthExpenses > 0
            ? round((($currentMonthExpenses - $previousMonthExpenses) / $previousMonthExpenses) * 100, 2)
            : ($currentMonthExpenses > 0 ? 100 : 0);

        // Category breakdown
        $categoryBreakdown = Expense::accessible($allowedHostelIds)
            ->where('month', $currentMonth)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        // Monthly trend
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthlyTrend[] = [
                'month' => $month,
                'month_name' => date('M Y', strtotime($month . '-01')),
                'total' => Expense::accessible($allowedHostelIds)
                    ->where('month', $month)
                    ->sum('amount')
            ];
        }

        $categories = Expense::accessible($allowedHostelIds)
            ->distinct()
            ->pluck('category');

        return view('admin.expenses.index', compact(
            'expenses',
            'hostels',
            'totalExpenses',
            'currentMonthExpenses',
            'previousMonthExpenses',
            'momChange',
            'categoryBreakdown',
            'monthlyTrend',
            'categories',
            'currentMonth'
        ));
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'expense_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|max:7',
            'expense_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'note' => 'nullable|string',
            'payment_method' => 'nullable|string|max:50',
            'hostel_id' => 'nullable|exists:hostels,id',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        // Check access to hostel
        if ($request->hostel_id) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($request->hostel_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this hostel.'
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $expense = new Expense();
            $expense->expense_name = $request->expense_name;
            $expense->amount = $request->amount;
            $expense->month = $request->month;
            $expense->expense_date = $request->expense_date;
            $expense->category = $request->category;
            $expense->note = $request->note;
            $expense->payment_method = $request->payment_method;
            $expense->hostel_id = $request->hostel_id;
            $expense->created_by = auth()->id();

            // Upload receipt
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('expenses/receipts', 'public');
                $expense->receipt = $receiptPath;
            }

            $expense->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense recorded successfully!',
                'data' => $expense->load('creator', 'hostel')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Expense Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to record expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expense details for viewing.
     */
    public function show($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $expense = Expense::with('creator', 'hostel')->findOrFail($id);

        // Check access
        if ($expense->hostel_id) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($expense->hostel_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $expense->id,
                'expense_name' => $expense->expense_name,
                'amount' => $expense->amount,
                'formatted_amount' => $expense->formatted_amount,
                'month' => $expense->month,
                'month_name' => $expense->month_name,
                'expense_date' => $expense->expense_date->format('d F Y'),
                'category' => $expense->category,
                'note' => $expense->note,
                'payment_method' => $expense->payment_method,
                'hostel_name' => $expense->hostel->name ?? 'Global',
                'created_by' => $expense->creator->name ?? 'N/A',
                'receipt_url' => $expense->receipt ? asset('storage/' . $expense->receipt) : null,
                'created_at' => $expense->created_at->format('d M Y h:i A')
            ]
        ]);
    }

    /**
     * Get expense data for editing.
     */
    public function edit($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $expense = Expense::findOrFail($id);

        // Check access
        if ($expense->hostel_id) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($expense->hostel_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'expense' => [
                'id' => $expense->id,
                'expense_name' => $expense->expense_name,
                'amount' => $expense->amount,
                'month' => $expense->month,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'category' => $expense->category,
                'note' => $expense->note,
                'payment_method' => $expense->payment_method,
                'hostel_id' => $expense->hostel_id,
                'receipt' => $expense->receipt
            ]
        ]);
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $expense = Expense::findOrFail($id);

        // Check access
        if ($expense->hostel_id) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($expense->hostel_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $request->validate([
            'expense_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|max:7',
            'expense_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'note' => 'nullable|string',
            'payment_method' => 'nullable|string|max:50',
            'hostel_id' => 'nullable|exists:hostels,id',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        try {
            DB::beginTransaction();

            $expense->expense_name = $request->expense_name;
            $expense->amount = $request->amount;
            $expense->month = $request->month;
            $expense->expense_date = $request->expense_date;
            $expense->category = $request->category;
            $expense->note = $request->note;
            $expense->payment_method = $request->payment_method;
            $expense->hostel_id = $request->hostel_id;

            // Upload new receipt
            if ($request->hasFile('receipt')) {
                // Delete old receipt
                if ($expense->receipt && Storage::disk('public')->exists($expense->receipt)) {
                    Storage::disk('public')->delete($expense->receipt);
                }
                $receiptPath = $request->file('receipt')->store('expenses/receipts', 'public');
                $expense->receipt = $receiptPath;
            }

            $expense->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Expense Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified expense.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $expense = Expense::findOrFail($id);

        // Check access
        if ($expense->hostel_id) {
            $allowedHostelIds = $this->getAllowedHostelIds();
            if (!$allowedHostelIds->contains($expense->hostel_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        try {
            // Delete receipt
            if ($expense->receipt && Storage::disk('public')->exists($expense->receipt)) {
                Storage::disk('public')->delete($expense->receipt);
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Expense Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expense summary by month
     */
    public function summary(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $year = $request->get('year', date('Y'));
        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = sprintf('%s-%02d', $year, $i);
            $total = Expense::accessible($allowedHostelIds)
                ->where('month', $month)
                ->sum('amount');

            $months[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
                'total' => $total,
                'formatted_total' => '₹' . number_format($total, 2)
            ];
        }

        $categorySummary = Expense::accessible($allowedHostelIds)
            ->where('month', 'like', $year . '-%')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'months' => $months,
                'category_summary' => $categorySummary,
                'grand_total' => array_sum(array_column($months, 'total')),
                'average_per_month' => array_sum(array_column($months, 'total')) / 12
            ]
        ]);
    }
}
