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

        // Get data for each month of the current year
        for ($monthIndex = 0; $monthIndex < 12; $monthIndex++) {
            $monthStart = now()->startOfYear()->addMonths($monthIndex)->startOfMonth();
            $monthEnd = now()->startOfYear()->addMonths($monthIndex)->endOfMonth();

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
                'month' => $months[$monthIndex],
                'econcern' => $econcern,
                'fbPages' => $fbPages,
                'contact' => $contact,
            ];
        }

        return response()->json($analytics);
    }

    /**
     * Get municipality statistics for heatmap visualization
     */
    public function municipalityStats()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        // Tarlac Province municipalities with coordinates for map
        $tarlacMunicipalities = [
            'Anao' => ['lat' => 15.7322, 'lng' => 120.6283],
            'Bamban' => ['lat' => 15.2833, 'lng' => 120.5580],
            'Camiling' => ['lat' => 15.6847, 'lng' => 120.4114],
            'Capas' => ['lat' => 15.3289, 'lng' => 120.5911],
            'Concepcion' => ['lat' => 15.3264, 'lng' => 120.6558],
            'Gerona' => ['lat' => 15.6067, 'lng' => 120.5000],
            'La Paz' => ['lat' => 15.4428, 'lng' => 120.7283],
            'Mayantoc' => ['lat' => 15.6175, 'lng' => 120.3761],
            'Moncada' => ['lat' => 15.7339, 'lng' => 120.5700],
            'Paniqui' => ['lat' => 15.6667, 'lng' => 120.5833],
            'Pura' => ['lat' => 15.6225, 'lng' => 120.6522],
            'Ramos' => ['lat' => 15.6636, 'lng' => 120.6397],
            'San Clemente' => ['lat' => 15.7108, 'lng' => 120.3608],
            'San Jose' => ['lat' => 15.4706, 'lng' => 120.4953],
            'San Manuel' => ['lat' => 15.8272, 'lng' => 120.6133],
            'Santa Ignacia' => ['lat' => 15.6117, 'lng' => 120.4378],
            'Tarlac City' => ['lat' => 15.4866, 'lng' => 120.5941],
            'Victoria' => ['lat' => 15.5750, 'lng' => 120.6800],
        ];

        $stats = [];

        foreach ($tarlacMunicipalities as $municipality => $coords) {
            $query = ReceivingRecord::where('municipality_address', 'LIKE', "%{$municipality}%");
            
            $total = (clone $query)->count();
            $pending = (clone $query)->whereIn('status', ['pending', 'on process'])->count();
            $approved = (clone $query)->whereIn('status', ['approved', 'served', 'for releasing'])->count();
            $rejected = (clone $query)->where('status', 'disapproved')->count();

            $stats[] = [
                'municipality' => $municipality,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
            ];
        }

        // Sort by total requests descending
        usort($stats, function($a, $b) {
            return $b['total'] - $a['total'];
        });

        return response()->json($stats);
    }

    /**
     * Get all records for a specific municipality
     */
    public function municipalityRecords(Request $request, string $municipality)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $query = ReceivingRecord::where('municipality_address', 'LIKE', "%{$municipality}%");

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('barangay') && $request->barangay) {
            $query->where('organization_barangay', 'LIKE', "%{$request->barangay}%");
        }

        if ($request->has('department') && $request->department) {
            $query->where('department', $request->department);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Get records with pagination
        $perPage = $request->get('per_page', 15);
        $records = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Transform the data
        $records->getCollection()->transform(function ($record) {
            return [
                'id' => $record->id,
                'control_no' => $record->control_no,
                'date' => $record->date?->format('Y-m-d'),
                'name' => $record->name,
                'organization_barangay' => $record->organization_barangay,
                'municipality_address' => $record->municipality_address,
                'category' => $record->category,
                'department' => $record->department,
                'status' => $record->status,
                'particulars' => $record->particulars,
                'contact' => $record->contact,
                'created_at' => $record->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        // Get summary stats
        $baseQuery = ReceivingRecord::where('municipality_address', 'LIKE', "%{$municipality}%");
        $summary = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'on process'])->count(),
            'approved' => (clone $baseQuery)->whereIn('status', ['approved', 'served', 'for releasing'])->count(),
            'rejected' => (clone $baseQuery)->where('status', 'disapproved')->count(),
        ];

        // Get unique barangays and departments for filters
        $barangays = ReceivingRecord::where('municipality_address', 'LIKE', "%{$municipality}%")
            ->whereNotNull('organization_barangay')
            ->distinct()
            ->pluck('organization_barangay')
            ->filter()
            ->values();

        $departments = ReceivingRecord::where('municipality_address', 'LIKE', "%{$municipality}%")
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->filter()
            ->values();

        $categories = ReceivingRecord::where('municipality_address', 'LIKE', "%{$municipality}%")
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json([
            'municipality' => $municipality,
            'summary' => $summary,
            'filters' => [
                'barangays' => $barangays,
                'departments' => $departments,
                'categories' => $categories,
            ],
            'records' => $records,
        ]);
    }
}
