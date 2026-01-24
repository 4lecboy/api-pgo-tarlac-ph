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
        $user = auth()->user();

        // Get records where department matches user's department
        // Normalize to lowercase for comparison
        $records = ReceivingRecord::with(['user', 'processedBy'])
            ->whereRaw('LOWER(department) = ?', [strtolower($user->department)])
            ->orderBy('created_at', 'desc')
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
        $user = auth()->user();

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
        $user = auth()->user();

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
            'status' => 'nullable|in:pending,approved,disapproved',
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
        $user = auth()->user();

        $stats = [
            'total' => ReceivingRecord::whereRaw('LOWER(department) = ?', [strtolower($user->department)])->count(),
            'pending' => ReceivingRecord::whereRaw('LOWER(department) = ?', [strtolower($user->department)])
                ->where('status', 'pending')->count(),
            'approved' => ReceivingRecord::whereRaw('LOWER(department) = ?', [strtolower($user->department)])
                ->where('status', 'approved')->count(),
            'disapproved' => ReceivingRecord::whereRaw('LOWER(department) = ?', [strtolower($user->department)])
                ->where('status', 'disapproved')->count(),
            'processed' => ReceivingRecord::whereRaw('LOWER(department) = ?', [strtolower($user->department)])
                ->whereNotNull('processed_by_user_id')->count(),
            'unprocessed' => ReceivingRecord::whereRaw('LOWER(department) = ?', [strtolower($user->department)])
                ->whereNull('processed_by_user_id')->count(),
        ];

        return response()->json([
            'message' => 'Statistics retrieved successfully',
            'statistics' => $stats,
            'department' => $user->department
        ]);
    }
}
