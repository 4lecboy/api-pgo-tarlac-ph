<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsTransactionHistory;
use App\Services\TxtBoxSmsService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function sendOne(TxtBoxSmsService $sms, Request $request)
    {
        $validated = $request->validate([
            'contact_number' => ['required', 'string'],
            'message' => 'required',
            'recipient' => 'required'
        ]);

        return $sms->send($validated['contact_number'], $validated['message'], $validated['recipient']);
    }

    public function getBalance()
    {
        return response()->json([
            'sms_credits' => auth('api')->user()->sms_credits
        ]);
    }

    public function getLogs(Request $request)
    {
        $user = auth('api')->user();
        $filter = $request->query('filter', 'daily');
        
        $query = SmsTransactionHistory::where('user_id', $user->id);
        
        $now = now();
        switch ($filter) {
            case 'daily':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'weekly':
                $query->whereBetween('created_at', [
                    $now->startOfWeek()->toDateTimeString(),
                    $now->copy()->endOfWeek()->toDateTimeString()
                ]);
                break;
            case 'monthly':
                $query->whereMonth('created_at', $now->month)
                      ->whereYear('created_at', $now->year);
                break;
        }
        
        $logs = $query->orderBy('created_at', 'desc')
                      ->paginate(15);
        
        return response()->json($logs);
    }
}
