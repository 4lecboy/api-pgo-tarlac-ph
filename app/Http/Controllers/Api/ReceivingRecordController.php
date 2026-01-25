<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReceivingRecord;

class ReceivingRecordController extends Controller
{
    /**
     * Display all receiving records
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();

        // Only allow Receiving department
        if (strtolower($user->department) !== 'receiving') {
            return response()->json(['error' => 'Forbidden: Access denied for your department'], 403);
        }

        // Get all records with user information, ordered by latest first
        $records = ReceivingRecord::with(['user', 'remarksHistory.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15); // 15 records per page

        return response()->json([
            'message' => 'Records retrieved successfully',
            'records' => $records
        ]);
    }

    /**
     * Display a specific receiving record
     */
    public function show($id)
    {
        $user = auth('api')->user();

        // Only allow Receiving department
        if (strtolower($user->department) !== 'receiving') {
            return response()->json(['error' => 'Forbidden: Access denied for your department'], 403);
        }

        $record = ReceivingRecord::with(['user', 'remarksHistory.user'])->find($id);

        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        return response()->json([
            'message' => 'Record retrieved successfully',
            'record' => $record
        ]);
    }

    /**
     * Store a new receiving record
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        // Only allow Receiving department
        if (strtolower($user->department) !== 'receiving') {
            return response()->json(['error' => 'Forbidden: Access denied for your department'], 403);
        }

        $validated = $request->validate([
            'control_no' => 'required|string|unique:receiving_records,control_no',
            'date' => 'required|date',
            'particulars' => 'nullable|string',
            'department' => 'required|string',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
            'organization_barangay' => 'nullable|string',
            'municipality_address' => 'nullable|string',
            'name' => 'nullable|string',
            'contact' => 'nullable|string',
            'action_taken' => 'nullable|string',
            'amount_approved' => 'nullable|numeric',
            'district' => 'nullable|string',
            'status' => 'required|in:pending,approved,disapproved,served,on process,for releasing',
            'requisitioner' => 'nullable|string',
            'served_request' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $validated['user_id'] = $user->id;

        $record = ReceivingRecord::create($validated);

        // Add initial remark if provided
        if (!empty($validated['remarks'])) {
            \App\Models\RecordRemark::create([
                'receiving_record_id' => $record->id,
                'user_id' => $user->id,
                'remark' => $validated['remarks']
            ]);
        }

        // Load relationships including remarks history
        $record->load(['user', 'remarksHistory.user']);

        return response()->json([
            'message' => 'Record created successfully',
            'record' => $record
        ], 201);
    }
    /**
     * Remove the specified receiving record
     */
    public function destroy($id)
    {
        $user = auth('api')->user();

        // Only allow Receiving department
        if (strtolower($user->department) !== 'receiving') {
            return response()->json(['error' => 'Forbidden: Access denied for your department'], 403);
        }

        $record = ReceivingRecord::find($id);

        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $record->delete();

        return response()->json([
            'message' => 'Record deleted successfully'
        ]);
    }
}
