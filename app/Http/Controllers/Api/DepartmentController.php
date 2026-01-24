<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Return the pages/messages user can access based on their department
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Define all departments/pages
        $allDepartments = [
            'Receiving',
            'Social Service',
            'Barangay Affairs',
            'Financial Assistance',
            'Use Of Facilities',
            'Appointment Meeting',
            'Use of Vehicle Ambulance',
            'Other Request',
        ];

        // Determine what user can access
        // Here we assume the user has only one department (string field)
        // If you plan for multiple departments, we can change it to array
        $accessible = in_array($user->department, $allDepartments) ? [$user->department] : [];

        return response()->json([
            'message' => 'User departments access',
            'user' => $user->email,
            'department' => $user->department,
            'accessible_pages' => $accessible,
        ]);
    }
}
