<?php

namespace App\Services;

use App\Models\User;
use App\Models\SmsTransactionHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TxtBoxSmsService
{
    public function send(string $recipient, string $message, $to)
    {
        $config = config("services.txtbox");
        $user = auth('api')->user();

        if (!$config || !isset($config['url']) || empty($config['api_key'])) {
            return response()->json([
                'message' => 'SMS Service is not configured. Please add TXTBOX_API_KEY to your .env file.'
            ], 500);
        }

        if (!$user) {
            throw new \Exception("User not authenticated.");
        }

        $creditsNeeded = round((mb_strlen($message) / 150) * 0.40, 2);

        if ($creditsNeeded > $user->sms_credits) {
            return response()->json([
                'message' => 'Insufficient SMS credits. Please add more credits.'
            ], 422);
        }

        $newBalance = $user->sms_credits - $creditsNeeded;
        $payload = [
            'message' => $message,
            'number' => $recipient,
        ];

        Log::info('TxtBox Request (PGO)', [
            'url' => $config['url'],
            'payload' => $payload,
        ]);

        $response = Http::withHeaders([
            'X-TXTBOX-Auth' => $config['api_key'],
        ])->asForm()->post($config['url'], $payload);

        $monthYear = now()->format('mY');
        $existingCount = SmsTransactionHistory::where('name', 'like', "SMSTRNSCT-{$monthYear}-%")->count();
        $increment = str_pad($existingCount + 1, 3, '0', STR_PAD_LEFT);
        $transactionName = "SMSTRNSCT-{$monthYear}-{$increment}";
        $status = $response->successful() ? 'Approved' : 'Failed';

        DB::transaction(function () use ($user, $newBalance, $creditsNeeded, $transactionName, $status, $message, $to, $recipient) {
            $user->update(['sms_credits' => $newBalance]);

            SmsTransactionHistory::create([
                'name' => $transactionName,
                'credit_amount' => $creditsNeeded,
                'status' => $status,
                'initiated_by' => $user->email,
                'message' => $message,
                'recipient' => "{$to}({$recipient})",
                'user_id' => $user->id,
            ]);
        });

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to send SMS. Please contact your admin.'
            ], 500);
        }

        return $response->json();
    }
}
