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

        // Get records
        $query = ReceivingRecord::with(['user', 'processedBy', 'remarksHistory.user']);

        // Only filter by department if NOT in 'receiving' department
        if (strtolower($user->department) !== 'receiving') {
            $query->whereRaw('LOWER(department) = ?', [strtolower($user->department)]);
        }

        // Filter by category if provided
        // Special case: If user is 'receiving' and category is 'Receiving', show all records (Master List)
        if ($category && (strtolower($user->department) !== 'receiving' || strtolower($category) !== 'receiving')) {
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

        $query = ReceivingRecord::with(['user', 'processedBy', 'remarksHistory.user']);

        // Only filter by department if NOT in 'receiving' department
        if (strtolower($user->department) !== 'receiving') {
            $query->whereRaw('LOWER(department) = ?', [strtolower($user->department)]);
        }

        $record = $query->find($id);

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

        $query = ReceivingRecord::query();

        // Only filter by department if NOT in 'receiving' department
        if (strtolower($user->department) !== 'receiving') {
            $query->whereRaw('LOWER(department) = ?', [strtolower($user->department)]);
        }

        $record = $query->find($id);

        if (!$record) {
            return response()->json([
                'error' => 'Record not found or not assigned to your department'
            ], 404);
        }

        $isReceiving = strtolower($user->department) === 'receiving';

        $validated = $request->validate([
            'control_no' => 'nullable|string',
            'date' => 'nullable|date',
            'particulars' => 'nullable|string',
            'department' => 'nullable|string',
            'organization_barangay' => 'nullable|string',
            'municipality_address' => 'nullable|string',
            'name' => 'nullable|string',
            'contact' => 'nullable|string',
            'action_taken' => 'nullable|string',
            'amount_approved' => 'nullable|numeric',
            'district' => 'nullable|string',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
            'requisitioner' => 'nullable|string',
            'served_request' => 'nullable|string',
            'remarks' => 'nullable|string',
            'new_remark' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,disapproved,served,on process,for releasing',
        ]);

        // Restrict certain fields for non-receiving departments
        if (!$isReceiving) {
            unset($validated['status']);
            unset($validated['department']);
            unset($validated['category']);
        }

        // Add processing metadata
        if ($request->has('status') || $request->has('remarks') || $request->has('action_taken') || $request->has('new_remark')) {
            $validated['processed_by_user_id'] = $user->id;
            $validated['processed_at'] = now();
        }

        // Handle new remark if provided
        if ($request->filled('new_remark')) {
            \App\Models\RecordRemark::create([
                'receiving_record_id' => $record->id,
                'user_id' => $user->id,
                'remark' => $request->new_remark
            ]);
            
            // Optionally update the main 'remarks' column for backward compatibility or overview
            $validated['remarks'] = $request->new_remark;
        }

        $record->update($validated);
        $record->load(['user', 'processedBy', 'remarksHistory.user']);

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

        $baseQuery = ReceivingRecord::query();

        // Only filter by department if NOT in 'receiving' department
        if ($deptLower !== 'receiving') {
            $baseQuery->whereRaw('LOWER(department) = ?', [$deptLower]);
        }

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
        $categoryQuery = ReceivingRecord::query();
        if ($deptLower !== 'receiving') {
            $categoryQuery->whereRaw('LOWER(department) = ?', [$deptLower]);
        }

        $categoryCounts = $categoryQuery->selectRaw('category, count(*) as count')
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
