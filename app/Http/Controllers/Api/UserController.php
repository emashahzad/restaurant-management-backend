<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json([
            'message' => 'Users Fetched Successfully',
            'User' => $users
        ], 200);
    }
    public function show(User $user)
    {
        $user->load('roles');
        return response()->json([
            'message' => 'User Fetched Successfully',
            'User' => $user
        ], 200);
    }

    public function store(Request $request)
    {
        // Step 1: Validate request
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user'
        ]);

        // Step 2: Hash password
        $data['password'] = Hash::make($data['password']);

        // Step 3: Create user
        $user = User::create($data);

        // Step 4: Assign Spatie role
        $user->assignRole($data['role']);
        $user->sendEmailVerificationNotification();

        // Step 6: Return JSON response
        return response()->json([
            'message' => 'User created successfully',
            'User' => $user->load('roles'),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'required|in:admin,user'
        ]);
        $user->update($data);
        $user->syncRoles($data['role']);
        return response()->json([
            'message' => 'User updated successfully',
            'User' => $user->load('roles'),
        ], 200);
    }
    public function patch(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,user'
        ]);
        if (isset($data['role'])) {
            $user->syncRoles($data['role']);
            unset($data['role']);
        }
        $user->update($data);
        return response()->json([
            'message' => 'User updated successfully',
            'User' => $user->load('roles'),
        ], 200);
    }
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }
        ;
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'You cannot delete another admin'
            ], 403);
        }
        ;
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }
    public function trash()
    {
        $user = User::onlyTrashed()->get();
        return response()->json([
            'message' => 'Deleted users fetched successfully',
            'data' => $user
        ], 200);
    }
    public function restore($id)
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 400);
        }
        if (!$user->trashed()) {
            return response()->json([
                'message' => 'User is not deleted',
            ], 400);
        }
        $user->restore();
        return response()->json([
            'message' => 'User restored successfully',
            'data' => $user
        ], 200);
    }


public function forceDelete($id)
{
    // Find only permanently deleted users
    $user = User::onlyTrashed()->find($id);

    if (!$user) {
        return response()->json([
            'message' => 'Deleted user not found'
        ], 404);
    }

    // Prevent deleting another admin
    if ($user->hasRole('admin')) {
        return response()->json([
            'message' => 'You cannot permanently delete an admin'
        ], 403);
    }

    try {
        DB::transaction(function () use ($user) {

            // Remove Spatie role assignments
            $user->roles()->detach();

            // Remove direct permission assignments
            $user->permissions()->detach();

            // Permanently delete user
            $user->forceDelete();
        });

        return response()->json([
            'message' => 'User permanently deleted successfully'
        ], 200);

    } catch (QueryException $e) {

        return response()->json([
            'message' => 'User cannot be permanently deleted because related records exist.'
        ], 409);
    }
}

}
