<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = auth('api')->user();
        $query = User::query();

        // Scope by department if not Super Admin
        if (!$currentUser->isSuperAdmin()) {
            $query->where('department', $currentUser->department);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filter by department (Super Admin only can filter by specific department)
        if ($currentUser->isSuperAdmin() && $request->has('department')) {
            $query->where('department', $request->department);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentUser = auth('api')->user();

        $rules = [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'role' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\UserRole::class)],
            'department' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\Department::class)],
            'position' => 'nullable|string',
        ];

        $validated = $request->validate($rules);

        // Enforce department for non-Super Admins
        if (!$currentUser->isSuperAdmin()) {
            if (strtolower($validated['department']) !== strtolower($currentUser->department)) {
                return response()->json(['message' => 'You can only create users in your own department.'], 403);
            }
            // Optional: Prevent Admin from creating Super Admin
            if ($validated['role'] === \App\Enums\UserRole::SUPER_ADMIN->value) {
                return response()->json(['message' => 'You cannot create a Super Admin.'], 403);
            }
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $currentUser = auth('api')->user();
        $user = User::findOrFail($id);

        if (!$currentUser->isSuperAdmin() && strtolower($user->department) !== strtolower($currentUser->department ?? '')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $currentUser = auth('api')->user();
        $user = User::findOrFail($id);

        // Check Access
        if (!$currentUser->isSuperAdmin() && strtolower($user->department) !== strtolower($currentUser->department ?? '')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = [
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'middle_name' => 'nullable|string',
            'role' => ['sometimes', \Illuminate\Validation\Rule::enum(\App\Enums\UserRole::class)],
            'department' => ['sometimes', \Illuminate\Validation\Rule::enum(\App\Enums\Department::class)],
            'position' => 'nullable|string',
            'password' => 'nullable|string|min:8',
            'status' => 'sometimes|in:active,inactive'
        ];

        $validated = $request->validate($rules);

         // Enforce department/role checks for non-Super Admins
        if (!$currentUser->isSuperAdmin()) {
             if (isset($validated['department']) && strtolower($validated['department']) !== strtolower($currentUser->department)) {
                 return response()->json(['message' => 'You cannot change users to another department.'], 403);
             }
             if (isset($validated['role']) && $validated['role'] === \App\Enums\UserRole::SUPER_ADMIN->value) {
                 return response()->json(['message' => 'You cannot promote to Super Admin.'], 403);
             }
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === auth('api')->id()) {
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
