<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OutgoingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OutgoingRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = OutgoingRecord::query()->with('user:id,first_name,last_name,department');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('particulars', 'like', "%{$search}%")
                  ->orWhere('recipient', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('vehicle', 'like', "%{$search}%")
                  ->orWhere('driver', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(['legal_docs', 'memo', 'fuel_requests'])],
            'date' => ['required', 'date'],
            'particulars' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'recipient' => ['nullable', 'string'],
            'vehicle' => ['nullable', 'string'],
            'driver' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric'],
            'file' => ['nullable', 'file', 'max:10240'], // 10MB max
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('outgoing-docs', 'public');
        }

        $record = OutgoingRecord::create([
            ...$validated,
            'file_path' => $path,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($record, 201);
    }

    public function show($id)
    {
        $record = OutgoingRecord::with('user:id,first_name,last_name,department')->findOrFail($id);
        return response()->json($record);
    }

    public function destroy($id)
    {
        $record = OutgoingRecord::findOrFail($id);
        
        if ($record->file_path) {
            Storage::disk('public')->delete($record->file_path);
        }
        
        $record->delete();
        return response()->json(['message' => 'Record deleted successfully']);
    }
}
