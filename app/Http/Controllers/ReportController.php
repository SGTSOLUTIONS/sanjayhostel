<?php
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Staff;
use App\Models\Member;
use App\Models\Hostel;
use App\Models\StaffAttendance;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportController extends Controller
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
     * Display financial reports page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        // Get date range
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        // Get hostels for filter
        $hostels = Hostel::whereIn('id', $allowedHostelIds)->get();
        $selectedHostelId = $request->get('hostel_id');

        // Calculate report data
        $reportData = $this->calculateReportData($startDate, $endDate, $selectedHostelId, $allowedHostelIds);

        return view('admin.reports.financial', compact(
            'reportData',
            'hostels',
            'selectedHostelId',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Calculate all report data
     */
    private function calculateReportData($startDate, $endDate, $selectedHostelId, $allowedHostelIds)
    {
        // Get date range months
        $startMonth = date('Y-m', strtotime($startDate));
        $endMonth = date('Y-m', strtotime($endDate));

        // Base query for payments (income)
        $paymentQuery = Payment::whereIn('status', ['paid', 'pending'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // Base query for expenses
        $expenseQuery = Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate]);

        // Apply hostel filter
        if ($selectedHostelId) {
            $paymentQuery->whereHas('member', function($q) use ($selectedHostelId) {
                $q->where('hostel_id', $selectedHostelId);
            });
            $expenseQuery->where(function($q) use ($selectedHostelId) {
                $q->where('hostel_id', $selectedHostelId)
                  ->orWhereNull('hostel_id');
            });
        } else {
            $paymentQuery->whereHas('member', function($q) use ($allowedHostelIds) {
                $q->whereIn('hostel_id', $allowedHostelIds);
            });
            $expenseQuery->where(function($q) use ($allowedHostelIds) {
                $q->whereIn('hostel_id', $allowedHostelIds)
                  ->orWhereNull('hostel_id');
            });
        }

        // Calculate totals
        $totalIncome = $paymentQuery->sum('amount');
        $totalExpenses = $expenseQuery->sum('amount');
        $netProfit = $totalIncome - $totalExpenses;

        // Get member payments details
        $memberPayments = $paymentQuery->with('member')
            ->select('member_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('member_id')
            ->get();

        // Calculate pending income (members who haven't paid fully)
        $pendingIncome = 0;
        $pendingMembers = [];

        $activeMembers = Member::where('status', 'active');
        if ($selectedHostelId) {
            $activeMembers->where('hostel_id', $selectedHostelId);
        } else {
            $activeMembers->whereIn('hostel_id', $allowedHostelIds);
        }
        $activeMembers = $activeMembers->get();

        foreach ($activeMembers as $member) {
            $monthlyRent = $member->rent_amount ?? 0;
            $totalPaid = $paymentQuery->where('member_id', $member->id)->sum('amount');
            $pendingAmount = $monthlyRent - $totalPaid;

            if ($pendingAmount > 0) {
                $pendingIncome += $pendingAmount;
                $pendingMembers[] = [
                    'member' => $member,
                    'monthly_rent' => $monthlyRent,
                    'total_paid' => $totalPaid,
                    'pending_amount' => $pendingAmount
                ];
            }
        }

        // Get expenses by category
        $expensesByCategory = $expenseQuery->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        // Get staff salary calculations for the period
        $staffSalaryData = $this->calculateStaffSalary($startDate, $endDate, $selectedHostelId, $allowedHostelIds);

        // Get daily breakdown
        $dailyIncome = $paymentQuery->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(amount) as total')
        )->groupBy('date')->orderBy('date')->get();

        $dailyExpenses = $expenseQuery->select(
            DB::raw('DATE(expense_date) as date'),
            DB::raw('SUM(amount) as total')
        )->groupBy('date')->orderBy('date')->get();

        // Merge daily data
        $dailyBreakdown = [];
        $allDates = array_unique(array_merge(
            $dailyIncome->pluck('date')->toArray(),
            $dailyExpenses->pluck('date')->toArray()
        ));
        sort($allDates);

        foreach ($allDates as $date) {
            $income = $dailyIncome->where('date', $date)->first()->total ?? 0;
            $expense = $dailyExpenses->where('date', $date)->first()->total ?? 0;
            $dailyBreakdown[] = [
                'date' => $date,
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense
            ];
        }

        return [
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'pending_income' => $pendingIncome,
                'expected_income' => $totalIncome + $pendingIncome,
                'profit_margin' => $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 2) : 0,
                'collection_rate' => ($totalIncome + $pendingIncome) > 0 ? round(($totalIncome / ($totalIncome + $pendingIncome)) * 100, 2) : 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1,
                'avg_daily_income' => $totalIncome / max(1, count($dailyBreakdown)),
                'avg_daily_expense' => $totalExpenses / max(1, count($dailyBreakdown))
            ],
            'member_payments' => $memberPayments,
            'pending_members' => $pendingMembers,
            'expenses_by_category' => $expensesByCategory,
            'staff_salary' => $staffSalaryData,
            'daily_breakdown' => $dailyBreakdown,
            'pending_count' => count($pendingMembers),
            'member_count' => $activeMembers->count(),
            'fully_paid_count' => $activeMembers->count() - count($pendingMembers)
        ];
    }

    /**
     * Calculate staff salary for the period
     */
    private function calculateStaffSalary($startDate, $endDate, $selectedHostelId, $allowedHostelIds)
    {
        $staffQuery = Staff::where('status', 'active');

        if ($selectedHostelId) {
            $staffQuery->where('hostel_id', $selectedHostelId);
        } else {
            $staffQuery->whereIn('hostel_id', $allowedHostelIds);
        }

        $staff = $staffQuery->get();
        $staffSalaryData = [];
        $totalSalary = 0;

        foreach ($staff as $staffMember) {
            $monthlySalary = $staffMember->salary;

            // Calculate working days in period
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));

            $totalWorkingDays = 0;
            $presentDays = 0;
            $leaveDays = 0;
            $halfDays = 0;

            foreach ($period as $date) {
                $currentDate = $date->format('Y-m-d');
                $totalWorkingDays++;

                $attendance = StaffAttendance::where('staff_id', $staffMember->id)
                    ->where('attendance_date', $currentDate)
                    ->first();

                if (!$attendance || $attendance->status == 'present') {
                    $presentDays++;
                } elseif ($attendance->status == 'leave') {
                    $leaveDays++;
                } elseif ($attendance->status == 'half_day') {
                    $halfDays++;
                    $presentDays += 0.5;
                }
            }

            // Calculate salary for period
            $dailyRate = $monthlySalary / 30;
            $salaryForPeriod = $dailyRate * $presentDays;

            $staffSalaryData[] = [
                'id' => $staffMember->id,
                'name' => $staffMember->name,
                'position' => $staffMember->position,
                'hostel' => $staffMember->hostel->name ?? 'N/A',
                'monthly_salary' => $monthlySalary,
                'daily_rate' => round($dailyRate, 2),
                'total_working_days' => $totalWorkingDays,
                'present_days' => $presentDays,
                'leave_days' => $leaveDays,
                'half_days' => $halfDays,
                'salary_for_period' => round($salaryForPeriod, 2),
                'payment_status' => 'pending'
            ];

            $totalSalary += $salaryForPeriod;
        }

        return [
            'staff_list' => $staffSalaryData,
            'total_salary' => $totalSalary,
            'staff_count' => $staff->count()
        ];
    }

    /**
     * Export report to Excel
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $allowedHostelIds = $this->getAllowedHostelIds();

        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $selectedHostelId = $request->get('hostel_id');

        $reportData = $this->calculateReportData($startDate, $endDate, $selectedHostelId, $allowedHostelIds);

        // Get hostel name
        $hostelName = 'All Hostels';
        if ($selectedHostelId) {
            $hostel = Hostel::find($selectedHostelId);
            $hostelName = $hostel->name ?? 'Unknown';
        }

        // Create Excel file
        $spreadsheet = new Spreadsheet();

        // Remove default sheet
        $spreadsheet->removeSheetByIndex(0);

        // Create sheets
        $this->createSummarySheet($spreadsheet, $reportData, $hostelName, $startDate, $endDate);
        $this->createIncomeSheet($spreadsheet, $reportData);
        $this->createExpenseSheet($spreadsheet, $reportData);
        $this->createStaffSalarySheet($spreadsheet, $reportData);
        $this->createDailyBreakdownSheet($spreadsheet, $reportData);
        $this->createPendingDuesSheet($spreadsheet, $reportData);

        // Set active sheet to first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Save file
        $filename = "Financial_Report_{$hostelName}_{$startDate}_to_{$endDate}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Create Summary Sheet
     */
    private function createSummarySheet($spreadsheet, $reportData, $hostelName, $startDate, $endDate)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'FINANCIAL REPORT SUMMARY');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;

        // Report Info
        $sheet->setCellValue('A' . $row, 'Hostel:');
        $sheet->setCellValue('B' . $row, $hostelName);
        $sheet->setCellValue('C' . $row, 'Period:');
        $sheet->setCellValue('D' . $row, date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate)));
        $row += 2;

        // Summary Stats
        $sheet->setCellValue('A' . $row, 'SUMMARY STATISTICS');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $row++;

        $summary = $reportData['summary'];
        $stats = [
            ['Total Income', '₹' . number_format($summary['total_income'], 2), 'Expected Income', '₹' . number_format($summary['expected_income'], 2)],
            ['Total Expenses', '₹' . number_format($summary['total_expenses'], 2), 'Pending Income', '₹' . number_format($summary['pending_income'], 2)],
            ['Net Profit', '₹' . number_format($summary['net_profit'], 2), 'Collection Rate', $summary['collection_rate'] . '%'],
            ['Profit Margin', $summary['profit_margin'] . '%', 'Total Days', $summary['total_days']],
            ['Avg Daily Income', '₹' . number_format($summary['avg_daily_income'], 2), 'Avg Daily Expense', '₹' . number_format($summary['avg_daily_expense'], 2)],
        ];

        foreach ($stats as $stat) {
            $sheet->setCellValue('A' . $row, $stat[0]);
            $sheet->setCellValue('B' . $row, $stat[1]);
            $sheet->setCellValue('C' . $row, $stat[2]);
            $sheet->setCellValue('D' . $row, $stat[3]);
            $row++;
        }
        $row += 2;

        // Member Stats
        $sheet->setCellValue('A' . $row, 'MEMBER STATISTICS');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $row++;

        $memberStats = [
            ['Total Members', $reportData['member_count']],
            ['Fully Paid Members', $reportData['fully_paid_count']],
            ['Members with Pending Dues', $reportData['pending_count']],
        ];

        foreach ($memberStats as $stat) {
            $sheet->setCellValue('A' . $row, $stat[0]);
            $sheet->setCellValue('B' . $row, $stat[1]);
            $row++;
        }

        // Apply styling
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(20);
    }

    /**
     * Create Income Sheet
     */
    private function createIncomeSheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Income Details');

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'INCOME DETAILS');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $row += 2;

        // Headers
        $headers = ['S.No', 'Member Name', 'Phone', 'Room', 'Amount Paid'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . $row, $header);
            $sheet->getStyle(chr(65 + $index) . $row)->getFont()->setBold(true);
            $sheet->getStyle(chr(65 + $index) . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
            $sheet->getStyle(chr(65 + $index) . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        }
        $row++;

        $sno = 1;
        $totalIncome = 0;
        foreach ($reportData['member_payments'] as $payment) {
            $member = $payment->member;
            $sheet->setCellValue('A' . $row, $sno++);
            $sheet->setCellValue('B' . $row, $member->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $member->phone ?? 'N/A');
            $sheet->setCellValue('D' . $row, $member->room->room_number ?? 'N/A');
            $sheet->setCellValue('E' . $row, $payment->total_paid);
            $totalIncome += $payment->total_paid;
            $row++;
        }

        // Total row
        $sheet->setCellValue('D' . $row, 'TOTAL:');
        $sheet->setCellValue('E' . $row, $totalIncome);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC2C2C2');

        // Apply column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
    }

    /**
     * Create Expense Sheet
     */
    private function createExpenseSheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Expense Details');

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'EXPENSE DETAILS BY CATEGORY');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $row += 2;

        // Headers
        $sheet->setCellValue('A' . $row, 'Category');
        $sheet->setCellValue('B' . $row, 'Amount');
        $sheet->setCellValue('C' . $row, 'Percentage');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFED7D31');
        $row++;

        $totalExpenses = $reportData['summary']['total_expenses'];
        foreach ($reportData['expenses_by_category'] as $expense) {
            $category = $expense->category ? ucfirst($expense->category) : 'Other';
            $amount = $expense->total;
            $percentage = $totalExpenses > 0 ? round(($amount / $totalExpenses) * 100, 2) : 0;

            $sheet->setCellValue('A' . $row, $category);
            $sheet->setCellValue('B' . $row, $amount);
            $sheet->setCellValue('C' . $row, $percentage . '%');
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, $totalExpenses);
        $sheet->setCellValue('C' . $row, '100%');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);

        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
    }

    /**
     * Create Staff Salary Sheet
     */
    private function createStaffSalarySheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Staff Salary Details');

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'STAFF SALARY DETAILS');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $row += 2;

        // Headers
        $headers = ['S.No', 'Name', 'Position', 'Hostel', 'Monthly Salary', 'Present Days', 'Leave Days', 'Salary for Period'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . $row, $header);
            $sheet->getStyle(chr(65 + $index) . $row)->getFont()->setBold(true);
            $sheet->getStyle(chr(65 + $index) . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF70AD47');
        }
        $row++;

        $sno = 1;
        $totalSalary = 0;
        foreach ($reportData['staff_salary']['staff_list'] as $staff) {
            $sheet->setCellValue('A' . $row, $sno++);
            $sheet->setCellValue('B' . $row, $staff['name']);
            $sheet->setCellValue('C' . $row, ucfirst($staff['position']));
            $sheet->setCellValue('D' . $row, $staff['hostel']);
            $sheet->setCellValue('E' . $row, $staff['monthly_salary']);
            $sheet->setCellValue('F' . $row, $staff['present_days']);
            $sheet->setCellValue('G' . $row, $staff['leave_days']);
            $sheet->setCellValue('H' . $row, $staff['salary_for_period']);
            $totalSalary += $staff['salary_for_period'];
            $row++;
        }

        // Total row
        $sheet->setCellValue('G' . $row, 'TOTAL SALARY:');
        $sheet->setCellValue('H' . $row, $totalSalary);
        $sheet->getStyle('G' . $row . ':H' . $row)->getFont()->setBold(true);

        // Apply column widths
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setWidth(15);
        }
        $sheet->getColumnDimension('B')->setWidth(25);
    }

    /**
     * Create Daily Breakdown Sheet
     */
    private function createDailyBreakdownSheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Daily Breakdown');

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'DAILY INCOME & EXPENSE BREAKDOWN');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $row += 2;

        // Headers
        $headers = ['Date', 'Income (₹)', 'Expense (₹)', 'Profit (₹)'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . $row, $header);
            $sheet->getStyle(chr(65 + $index) . $row)->getFont()->setBold(true);
            $sheet->getStyle(chr(65 + $index) . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF9C27B0');
            $sheet->getStyle(chr(65 + $index) . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        }
        $row++;

        $totalIncome = 0;
        $totalExpense = 0;
        $totalProfit = 0;

        foreach ($reportData['daily_breakdown'] as $day) {
            $sheet->setCellValue('A' . $row, date('d M Y', strtotime($day['date'])));
            $sheet->setCellValue('B' . $row, $day['income']);
            $sheet->setCellValue('C' . $row, $day['expense']);
            $sheet->setCellValue('D' . $row, $day['profit']);

            // Color code profit/loss
            if ($day['profit'] < 0) {
                $sheet->getStyle('D' . $row)->getFont()->getColor()->setARGB('FFFF0000');
            } elseif ($day['profit'] > 0) {
                $sheet->getStyle('D' . $row)->getFont()->getColor()->setARGB('FF00B050');
            }

            $totalIncome += $day['income'];
            $totalExpense += $day['expense'];
            $totalProfit += $day['profit'];
            $row++;
        }

        // Total row
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, $totalIncome);
        $sheet->setCellValue('C' . $row, $totalExpense);
        $sheet->setCellValue('D' . $row, $totalProfit);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        // Apply column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
    }

    /**
     * Create Pending Dues Sheet
     */
    private function createPendingDuesSheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pending Dues');

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'MEMBERS WITH PENDING DUES');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $row += 2;

        // Headers
        $headers = ['S.No', 'Member Name', 'Phone', 'Room', 'Monthly Rent', 'Paid Amount', 'Pending Amount'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . $row, $header);
            $sheet->getStyle(chr(65 + $index) . $row)->getFont()->setBold(true);
            $sheet->getStyle(chr(65 + $index) . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE74C3C');
            $sheet->getStyle(chr(65 + $index) . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        }
        $row++;

        $sno = 1;
        $totalPending = 0;
        foreach ($reportData['pending_members'] as $pending) {
            $member = $pending['member'];
            $sheet->setCellValue('A' . $row, $sno++);
            $sheet->setCellValue('B' . $row, $member->name);
            $sheet->setCellValue('C' . $row, $member->phone ?? 'N/A');
            $sheet->setCellValue('D' . $row, $member->room->room_number ?? 'N/A');
            $sheet->setCellValue('E' . $row, $pending['monthly_rent']);
            $sheet->setCellValue('F' . $row, $pending['total_paid']);
            $sheet->setCellValue('G' . $row, $pending['pending_amount']);
            $totalPending += $pending['pending_amount'];
            $row++;
        }

        if ($sno == 1) {
            $sheet->setCellValue('A' . $row, 'No members with pending dues');
            $sheet->mergeCells('A' . $row . ':G' . $row);
        } else {
            // Total row
            $sheet->setCellValue('F' . $row, 'TOTAL PENDING:');
            $sheet->setCellValue('G' . $row, $totalPending);
            $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->setBold(true);
            $sheet->getStyle('G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC7CE');
            $sheet->getStyle('G' . $row)->getFont()->getColor()->setARGB('FFC00000');
        }

        // Apply column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
    }
}
