<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not create token',
                'message' => $e->getMessage()
            ], 500);
        }

        // Since attempt was successful, we can retrieve the user
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'User not found after successful login attempt'], 500);
        }

        // Define all possible departments/pages
        $allDepartments = [
            'Receiving',
            'Social Service',
            'Barangay Affairs',
            'Financial Assistance',
            'Use Of Facilities',
            'Appointment Meeting',
            'Use of Vehicle Ambulance',
            'Other Request'
        ];

        $allDepartmentsLower = array_map('strtolower', $allDepartments);

        $accessiblePages = in_array(
            strtolower($user->department),
            $allDepartmentsLower
        ) ? [$user->department] : [];


        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'department' => $user->department,
                'accessible_pages' => $accessiblePages,
            ]
        ])->cookie(
            'token',
            $token,
            config('jwt.ttl'),
            '/',
            null,
            false, // Set to true if using HTTPS
            true,  // HttpOnly
            false, // Raw
            'Lax'  // SameSite
        );
    }



    public function logout(Request $request)
    {
        try {
            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Successfully logged out'])
                ->cookie('token', null, -1, '/');
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }
    }


    // AUTHENTICATED USER
    public function me()
    {
        return response()->json(auth('api')->user());
    }
}
