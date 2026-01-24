<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReceivingRecord;

class DepartmentRecordsController extends Controller
{
    /**
     * Get all records assigned to the logged-in user's department
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        if (!$user->department) {
            return response()->json([
                'error' => 'User does not have an assigned department',
            ], 403);
        }

        $category = $request->query('category');

        // Get records where department matches user's department
        // Normalize to lowercase for comparison
        $query = ReceivingRecord::with(['user', 'processedBy'])
            ->whereRaw('LOWER(department) = ?', [strtolower($user->department)]);

        // Filter by category if provided
        if ($category) {
            $query->where('category', $category);
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'message' => 'Records retrieved successfully',
            'records' => $records,
            'department' => $user->department
        ]);
    }

    /**
     * Get a specific record assigned to the department
     */
    public function show($id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        if (!$user->department) {
            return response()->json([
                'error' => 'User does not have an assigned department',
            ], 403);
        }

        $record = ReceivingRecord::with(['user', 'processedBy'])
            ->whereRaw('LOWER(department) = ?', [strtolower($user->department)])
            ->find($id);

        if (!$record) {
            return response()->json([
                'error' => 'Record not found or not assigned to your department'
            ], 404);
        }

        return response()->json([
            'message' => 'Record retrieved successfully',
            'record' => $record
        ]);
    }

    /**
     * Update department-specific fields
     */
    public function update(Request $request, $id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        if (!$user->department) {
            return response()->json([
                'error' => 'User does not have an assigned department',
            ], 403);
        }

        // Find record assigned to this department
        $record = ReceivingRecord::whereRaw('LOWER(department) = ?', [strtolower($user->department)])
            ->find($id);

        if (!$record) {
            return response()->json([
                'error' => 'Record not found or not assigned to your department'
            ], 404);
        }

        $validated = $request->validate([
            'district' => 'nullable|string',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
            'requisitioner' => 'nullable|string',
            'served_request' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,disapproved,served,on process,for releasing',
        ]);

        // Add processing metadata
        $validated['processed_by_user_id'] = $user->id;
        $validated['processed_at'] = now();

        $record->update($validated);
        $record->load(['user', 'processedBy']);

        return response()->json([
            'message' => 'Record updated successfully',
            'record' => $record
        ]);
    }

    /**
     * Get dashboard statistics for the department
     */
    public function statistics()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        if (!$user->department) {
            return response()->json([
                'error' => 'User does not have an assigned department',
            ], 403);
        }

        $deptLower = strtolower($user->department);

        $baseQuery = ReceivingRecord::whereRaw('LOWER(department) = ?', [$deptLower]);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'disapproved' => (clone $baseQuery)->where('status', 'disapproved')->count(),
            'served' => (clone $baseQuery)->where('status', 'served')->count(),
            'on_process' => (clone $baseQuery)->where('status', 'on process')->count(),
            'for_releasing' => (clone $baseQuery)->where('status', 'for releasing')->count(),
            'processed' => (clone $baseQuery)->whereNotNull('processed_by_user_id')->count(),
            'unprocessed' => (clone $baseQuery)->whereNull('processed_by_user_id')->count(),
        ];

        // Group by category counts
        $categoryCounts = ReceivingRecord::whereRaw('LOWER(department) = ?', [$deptLower])
            ->selectRaw('category, count(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('count', 'category');

        return response()->json([
            'message' => 'Statistics retrieved successfully',
            'statistics' => $stats,
            'category_counts' => $categoryCounts,
            'department' => $user->department
        ]);
    }
}
