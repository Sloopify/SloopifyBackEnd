<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utils\PhoneNumberHelper;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Setting;
use Exception;

class AuthController extends Controller
{
    //

    public function mapUserDetails($user)
    {
        $phoneDetails = PhoneNumberHelper::parsePhoneNumber($user->phone);

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => [
                'full' => $phoneDetails['formatted'],
                'code' => $phoneDetails['code'],
                'number' => $phoneDetails['number'],
                'valid' => $phoneDetails['valid']
            ],
            'email' => $user->email,
            'email_verified' => !is_null($user->email_verified_at),
            'gender' => $user->gender,
            'status' => $user->status,
            'is_blocked' => (bool)$user->is_blocked,
            'age' => $user->age,
            'birthday' => $user->birthday,
            'bio' => $user->bio,
            'country' => $user->country,
            'city' => $user->city,
            'provider' => $user->provider,
            'image' => $user->provider === 'google' ? $user->image : ($user->image ? asset('storage/' . $user->image) : null),
            'referral_code' => $user->referral_code,
            'referral_link' => $user->referral_link,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }


    public function loginEmail(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                ],
                'device_token' => 'nullable|string',
                'device_type' => 'nullable|string|in:ios,android,web'
            ]);

            $completedInterests = true;
            $completedGender = true;
            $completedBirthday = true;
            $completedImage = true;

            $email_login = Setting::where('key' , 'email_login')->value('value');

            if(!$email_login){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Email login is not allowed',
                ], 404);
            }

            $user = User::where('email', $validatedData['email'])->first();

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->is_blocked) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Your account has been blocked. Please contact support.',
                ], 403);
            }

            if($user->status === 'inactive')
            {
                return response()->json([
                    'status_code' => 403,
                    'success' => 'false',
                    'message' => 'Your account has been inactive. Please contact support.',
                ], 403);
            }

            if (!Hash::check($validatedData['password'], $user->password)) {
                return response()->json([
                    'status_code' => 401,
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }


            $userInterests = $user->userInterests;
            $interestsRequired = Setting::where('key' , 'require_interest_in_on_boarding')->value('value');
             
            if(count($userInterests) === 0 && $interestsRequired){
                $completedInterests = false;
            }

            $genderRequired = Setting::where('key' , 'require_gender_in_on_boarding')->value('value');

            if($user->gender === null && $genderRequired){
                $completedGender = false;
            }

            $birthdayRequired = Setting::where('key' , 'require_birthday_in_on_boarding')->value('value');

            if($user->birthday === null && $birthdayRequired){
                $completedBirthday = false;
            }

            $imageRequired = Setting::where('key' , 'required_upload_user_image_in_on_boarding')->value('value');

            if($user->img === null && $imageRequired){
                $completedImage = false;
            }
            
            // Check if any onboarding item is incomplete
            if (!$completedInterests || !$completedGender || !$completedBirthday || !$completedImage) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Please complete your profile first',
                    'completed_on_boarding' => [
                        'interests' => $completedInterests,
                        'gender' => $completedGender,
                        'birthday' => $completedBirthday,
                        'image' => $completedImage
                    ]
                ], 403);
            }
            
            // Generate token
            $token = $user->createToken('auth_token')->accessToken;

            // Update user device info and last login
            $user->update([
                'device_token' => $validatedData['device_token'] ?? null,
                'device_type' => $validatedData['device_type'] ?? null,
                'last_login_at' => now()
            ]);

            // Get fresh user data
            $user->refresh();

            // Map user details
            $userDetails = $this->mapUserDetails($user);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $userDetails,
                ],
                'completed_on_boarding' => [
                    'interests' => $completedInterests,
                    'gender' => $completedGender,
                    'birthday' => $completedBirthday,
                    'image' => $completedImage
                ]
            ], 200)->withHeaders([
                'Authorization' => $token,
                'Access-Control-Expose-Headers' => 'Authorization'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } 
        catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function loginMobile(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'phone' => [
                    'required',
                    'string',
                    'regex:/^\+[1-9]\d{1,14}$/' // E.164 format validation (e.g. +963997482515)
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                ],
                'device_token' => 'nullable|string',
                'device_type' => 'nullable|string|in:ios,android,web'
            ]);

            $completedInterests = true;
            $completedGender = true;
            $completedBirthday = true;
            $completedImage = true;

            $phone_login = Setting::where('key', 'phone_login')->value('value');

            if(!$phone_login){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Phone login is not allowed',
                ], 404);
            }

            // Parse and validate phone number
            $phoneDetails = PhoneNumberHelper::parsePhoneNumber($validatedData['phone']);
            
            if (!$phoneDetails['valid']) {
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid phone number format',
                ], 400);
            }

            $user = User::where('phone', $phoneDetails['formatted'])->first();

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->is_blocked) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Your account has been blocked. Please contact support.',
                ], 403);
            }

            if($user->status === 'inactive')
            {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Your account has been inactive. Please contact support.',
                ], 403);
            }

            if (!Hash::check($validatedData['password'], $user->password)) {
                return response()->json([
                    'status_code' => 401,
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $userInterests = $user->userInterests;
            $interestsRequired = Setting::where('key', 'require_interest_in_on_boarding')->value('value');
             
            if(count($userInterests) === 0 && $interestsRequired){
                $completedInterests = false;
            }

            $genderRequired = Setting::where('key', 'require_gender_in_on_boarding')->value('value');

            if($user->gender === null && $genderRequired){
                $completedGender = false;
            }

            $birthdayRequired = Setting::where('key', 'require_birthday_in_on_boarding')->value('value');

            if($user->birthday === null && $birthdayRequired){
                $completedBirthday = false;
            }

            $imageRequired = Setting::where('key', 'required_upload_user_image_in_on_boarding')->value('value');

            if($user->img === null && $imageRequired){
                $completedImage = false;
            }
            
            // Check if any onboarding item is incomplete
            if (!$completedInterests || !$completedGender || !$completedBirthday || !$completedImage) {
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Please complete your profile first',
                    'completed_on_boarding' => [
                        'interests' => $completedInterests,
                        'gender' => $completedGender,
                        'birthday' => $completedBirthday,
                        'image' => $completedImage
                    ]
                ], 403);
            }
            
            // Generate token
            $token = $user->createToken('auth_token')->accessToken;

            // Update user device info and last login
            $user->update([
                'device_token' => $validatedData['device_token'] ?? null,
                'device_type' => $validatedData['device_type'] ?? null,
                'last_login_at' => now()
            ]);

            // Get fresh user data
            $user->refresh();

            // Map user details
            $userDetails = $this->mapUserDetails($user);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $userDetails,
                ],
                'completed_on_boarding' => [
                    'interests' => $completedInterests,
                    'gender' => $completedGender,
                    'birthday' => $completedBirthday,
                    'image' => $completedImage
                ]
            ], 200)->withHeaders([
                'Authorization' => $token,
                'Access-Control-Expose-Headers' => 'Authorization'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } 
        catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
