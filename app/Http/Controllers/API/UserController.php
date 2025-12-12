<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError('Unauthorised.', ['error' => 'User not logged in']);
            }

            $validator = Validator::make($request->all(), [
                'email'    => 'nullable|email|unique:users,email,' . $user->id,
                'username' => 'nullable|string|max:50|unique:users,username,' . $user->id,
                'phone'    => 'nullable|string|max:20',
                'bio'      => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // UPDATE ONLY FIELDS PASSED IN REQUEST
            $user->update([
                'email'    => $request->email ?? $user->email,
                'username' => $request->username ?? $user->username,
                'phone'    => $request->phone ?? $user->phone,
                'bio'      => $request->bio ?? $user->bio,
            ]);

            return $this->sendResponse(
                [
                    'name'     => $user->name,
                    'username' => $user->username,
                    'phone'    => $user->phone,
                    'bio'      => $user->bio,
                    'email'    => $user->email
                ],
                'Profile updated successfully'
            );
        } catch (\Throwable $e) {

            // FULL ERROR DETAILS
            $errorDetail = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ];

            return $this->sendError('Throwable Error', $errorDetail);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError('Unauthorised.', ['error' => 'User not logged in']);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string|min:6',
                'new_password'     => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/[A-Z]/',     // Uppercase
                    'regex:/[0-9]/',     // Number
                    'regex:/[@$!%*?&]/'  // Special character
                ], // expects new_password_confirmation
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Check if current password matches
            if (!\Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Error', ['current_password' => 'Current password is incorrect.']);
            }

            // Update password
            $user->password = \Hash::make($request->new_password);
            $user->save();

            return $this->sendResponse([], 'Password changed successfully.');
        } catch (\Throwable $e) {
            $errorDetail = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ];

            return $this->sendError('Throwable Error', $errorDetail);
        }
    }
}
