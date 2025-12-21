<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get user profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['full_name', 'email', 'phone', 'bio']);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('organizers', 'public');
        }

        // Handle logo upload (organizers only)
        if ($request->hasFile('logo')) {
            if (!$user->isOrganizer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only organizers can upload logos'
                ], 403);
            }

            if ($user->logo) {
                Storage::disk('public')->delete($user->logo);
            }
            $data['logo'] = $request->file('logo')->store('organizers', 'public');
        }

        // Handle signature upload (organizers only)
        if ($request->hasFile('signature')) {
            if (!$user->isOrganizer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only organizers can upload signatures'
                ], 403);
            }

            if ($user->signature) {
                Storage::disk('public')->delete($user->signature);
            }
            $data['signature'] = $request->file('signature')->store('organizers', 'public');
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Update organizer status
     */
    public function updateOrganizerStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,participant',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update([
            'role' => $request->role,
            // is_organizer removed; organizer determined by role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully. Admin = Event Organizer',
            'data' => $user->fresh()
        ]);
    }
}
