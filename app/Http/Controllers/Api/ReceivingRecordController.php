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
        $records = ReceivingRecord::with(['user', 'remarksHistory.user', 'images'])
            ->orderBy('created_at', 'desc')
            ->paginate(15); // 15 records per page

        return response()->json([
            'message' => 'Records retrieved successfully',
            'records' => $records,
            'assignable_departments' => [
                'Barangay Affairs',
                'Financial Assistance',
                'Use Of Facilities',
                'Appointment Meeting',
                'Use of Vehicle Ambulance',
                'Other Request'
            ]
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

        $record = ReceivingRecord::with(['user', 'remarksHistory.user', 'images'])->find($id);

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
            'province' => 'nullable|string',
            'name' => 'nullable|string',
            'contact' => 'nullable|string',
            'action_taken' => 'nullable|string',
            'amount_approved' => 'nullable|numeric',
            'district' => 'nullable|string',
            'status' => 'required|in:pending,approved,disapproved,served,on process,for releasing',
            'requisitioner' => 'nullable|string',
            'served_request' => 'nullable|string',
            'remarks' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validated['status'] === 'approved') {
            $validated['approved_at'] = now();
        }

        $validated['user_id'] = $user->id;

        $record = ReceivingRecord::create($validated);

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('documents', 'public');
                \App\Models\DocumentImage::create([
                    'receiving_record_id' => $record->id,
                    'file_path' => $path,
                ]);
            }
        }

        // Add initial remark if provided
        if (!empty($validated['remarks'])) {
            \App\Models\RecordRemark::create([
                'receiving_record_id' => $record->id,
                'user_id' => $user->id,
                'remark' => $validated['remarks']
            ]);
        }

        // Load relationships including remarks history and images
        $record->load(['user', 'remarksHistory.user', 'images']);

        return response()->json([
            'message' => 'Record created successfully',
            'record' => $record
        ], 201);
    }
    /**
     * Update the specified receiving record
     */
    public function update(Request $request, $id)
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

        $validated = $request->validate([
            'control_no' => 'nullable|string|unique:receiving_records,control_no,' . $id,
            'date' => 'nullable|date',
            'particulars' => 'nullable|string',
            'department' => 'nullable|string',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
            'organization_barangay' => 'nullable|string',
            'municipality_address' => 'nullable|string',
            'province' => 'nullable|string',
            'name' => 'nullable|string',
            'contact' => 'nullable|string',
            'action_taken' => 'nullable|string',
            'amount_approved' => 'nullable|numeric',
            'district' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,disapproved,served,on process,for releasing',
            'requisitioner' => 'nullable|string',
            'served_request' => 'nullable|string',
            'remarks' => 'nullable|string',
            'new_remark' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Add processing metadata
        $validated['processed_by_user_id'] = $user->id;
        $validated['processed_at'] = now();

        // Handle new remark if provided
        if ($request->filled('new_remark')) {
            \App\Models\RecordRemark::create([
                'receiving_record_id' => $record->id,
                'user_id' => $user->id,
                'remark' => $request->new_remark
            ]);
            $validated['remarks'] = $request->new_remark;
        }

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('documents', 'public');
                \App\Models\DocumentImage::create([
                    'receiving_record_id' => $record->id,
                    'file_path' => $path,
                ]);
            }
        }

        // Set approved_at when status changes to 'approved' for the first time
        if (isset($validated['status']) && $validated['status'] === 'approved' && $record->status !== 'approved' && is_null($record->approved_at)) {
            $validated['approved_at'] = now();
        }

        $record->update($validated);
        $record->load(['user', 'remarksHistory.user', 'images']);

        return response()->json([
            'message' => 'Record updated successfully',
            'record' => $record
        ]);
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

    /**
     * Mark the record as completed (Final logging by Receiving Dept)
     */
    public function markAsCompleted($id)
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

        // Only allowed if status is approved or disapproved
        if (!in_array($record->status, ['approved', 'disapproved'])) {
            return response()->json(['error' => 'Record must be Approved or Disapproved before final logging'], 400);
        }

        $record->update([
            'status' => 'completed',
            'processed_by_user_id' => $user->id,
            'processed_at' => now(),
        ]);

        \App\Models\RecordRemark::create([
            'receiving_record_id' => $record->id,
            'user_id' => $user->id,
            'remark' => 'Final log completed. Status set to Completed.'
        ]);

        return response()->json([
            'message' => 'Record marked as completed successfully',
            'record' => $record->load(['user', 'remarksHistory.user', 'images'])
        ]);
    }
}
