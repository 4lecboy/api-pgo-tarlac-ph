<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
