<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReceivingRecord;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get quick statistics for dashboard cards
     */
    public function quickStats()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $deptLower = strtolower($user->department ?? '');
        $baseQuery = ReceivingRecord::query();

        // Only filter by department if NOT in 'receiving' department
        if ($deptLower !== 'receiving') {
            $baseQuery->whereRaw('LOWER(department) = ?', [$deptLower]);
        }

        // Calculate current month stats for trends
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $totalIncoming = (clone $baseQuery)->count();
        $totalIncomingLastMonth = (clone $baseQuery)
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->count();
        
        $totalOutgoing = (clone $baseQuery)
            ->whereIn('status', ['approved', 'served', 'for releasing'])
            ->count();
        $totalOutgoingLastMonth = (clone $baseQuery)
            ->whereIn('status', ['approved', 'served', 'for releasing'])
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->count();

        // ORM submissions (assuming ORM-related categories)
        $ormCategories = ['E-Concern', 'FB Pages', 'Contact'];
        $ormSubmissions = ReceivingRecord::whereIn('category', $ormCategories)->count();
        $ormSubmissionsLastMonth = ReceivingRecord::whereIn('category', $ormCategories)
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->count();

        $pendingAction = (clone $baseQuery)
            ->whereIn('status', ['pending', 'on process'])
            ->count();
        $pendingActionLastMonth = (clone $baseQuery)
            ->whereIn('status', ['pending', 'on process'])
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->count();

        // Calculate trends
        $incomingTrend = $totalIncomingLastMonth > 0 
            ? round((($totalIncoming - $totalIncomingLastMonth) / $totalIncomingLastMonth) * 100, 1)
            : 0;
        $outgoingTrend = $totalOutgoingLastMonth > 0
            ? round((($totalOutgoing - $totalOutgoingLastMonth) / $totalOutgoingLastMonth) * 100, 1)
            : 0;
        $ormTrend = $ormSubmissionsLastMonth > 0
            ? round((($ormSubmissions - $ormSubmissionsLastMonth) / $ormSubmissionsLastMonth) * 100, 1)
            : 0;
        $pendingTrend = $pendingActionLastMonth > 0
            ? round((($pendingAction - $pendingActionLastMonth) / $pendingActionLastMonth) * 100, 1)
            : 0;

        return response()->json([
            'totalIncoming' => $totalIncoming,
            'incomingTrend' => $incomingTrend,
            'totalOutgoing' => $totalOutgoing,
            'outgoingTrend' => $outgoingTrend,
            'ormSubmissions' => $ormSubmissions,
            'ormTrend' => $ormTrend,
            'pendingAction' => $pendingAction,
            'pendingTrend' => $pendingTrend,
        ]);
    }

    /**
     * Get incoming documents analytics by category
     */
    public function incomingAnalytics()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $deptLower = strtolower($user->department ?? '');
        
        // Get all categories with their status counts
        $categories = [
            'Barangay Affairs',
            'Financial Assistance',
            'Social Services',
            'Use of Facilities',
            'Appointment/Meeting',
            'Other Request',
            'Use of Vehicle and Ambulance'
        ];

        $analytics = [];

        foreach ($categories as $category) {
            $query = ReceivingRecord::where('category', $category);
            
            if ($deptLower !== 'receiving') {
                $query->whereRaw('LOWER(department) = ?', [$deptLower]);
            }

            $analytics[] = [
                'name' => $category,
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'approved' => (clone $query)->whereIn('status', ['approved', 'served'])->count(),
                'rejected' => (clone $query)->where('status', 'disapproved')->count(),
            ];
        }

        return response()->json($analytics);
    }

    /**
     * Get outgoing documents distribution by category
     */
    public function outgoingAnalytics()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $deptLower = strtolower($user->department ?? '');
        
        $query = ReceivingRecord::whereIn('status', ['approved', 'served', 'for releasing']);
        
        if ($deptLower !== 'receiving') {
            $query->whereRaw('LOWER(department) = ?', [$deptLower]);
        }

        $analytics = $query->select('category', DB::raw('count(*) as value'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->category,
                    'value' => $item->value
                ];
            });

        return response()->json($analytics);
    }

    /**
     * Get ORM analytics trend (monthly data for last 12 months)
     */
    public function ormAnalytics()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $analytics = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Get current year
        $year = now()->year;

        foreach (range(0, 11) as $index) {
            $monthStart = now()->month($index + 1)->startOfMonth();
            $monthEnd = now()->month($index + 1)->endOfMonth();

            $econcern = ReceivingRecord::where('category', 'E-Concern')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $fbPages = ReceivingRecord::where('category', 'FB Pages')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $contact = ReceivingRecord::where('category', 'Contact')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $analytics[] = [
                'month' => $months[$index],
                'econcern' => $econcern,
                'fbPages' => $fbPages,
                'contact' => $contact,
            ];
        }

        return response()->json($analytics);
    }
}
