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
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Cache;
use App\Models\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


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
            'age' => $user->birthday ? now()->diffInYears($user->birthday) : null,
            'birthday' => $user->birthday,
            'bio' => $user->bio,
            'country' => $user->country,
            'city' => $user->city,
            'provider' => $user->provider,
            'image' => $user->provider === 'google' ? $user->img : ($user->img ? config('app.url') . asset('storage/' . $user->img) : null),
            'referral_code' => $user->referral_code,
            'referral_link' => $user->referral_link,
            'reffered_by' => $user->reffered_by,
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
            // if (!$completedInterests || !$completedGender || !$completedBirthday || !$completedImage) {
            //     return response()->json([
            //         'status_code' => 403,
            //         'success' => false,
            //         'message' => 'Please complete your profile first',
            //         'completed_on_boarding' => [
            //             'interests' => $completedInterests,
            //             'gender' => $completedGender,
            //             'birthday' => $completedBirthday,
            //             'image' => $completedImage
            //         ]
            //     ], 403);
            // }
            
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
                    'completed_on_boarding' => [
                        'interests' => $completedInterests,
                        'gender' => $completedGender,
                        'birthday' => $completedBirthday,
                        'image' => $completedImage
                    ]
                ],
               
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

            $phone_login = Setting::where('key', 'mobile_login')->value('value');

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
            
            $user = User::where('phone', $validatedData['phone'])->first();

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
            // if (!$completedInterests || !$completedGender || !$completedBirthday || !$completedImage) {
            //     return response()->json([
            //         'status_code' => 403,
            //         'success' => false,
            //         'message' => 'Please complete your profile first',
            //         'completed_on_boarding' => [
            //             'interests' => $completedInterests,
            //             'gender' => $completedGender,
            //             'birthday' => $completedBirthday,
            //             'image' => $completedImage
            //         ]
            //     ], 403);
            // }
            
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
                    'completed_on_boarding' => [
                        'interests' => $completedInterests,
                        'gender' => $completedGender,
                        'birthday' => $completedBirthday,
                        'image' => $completedImage
                    ]
                ],
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
        // catch (Exception $e) {
        //     return response()->json([
        //         'status_code' => 500,
        //         'success' => false,
        //         'message' => 'An unexpected error occurred',
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }

    public function googleLogin(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'token' => 'required|string',
                'device_token' => 'nullable|string',
                'device_type' => 'nullable|string|in:ios,android,web'
            ]);

            $completedInterests = true;
            $completedGender = true;
            $completedBirthday = true;
            $completedImage = true;

            $google_login = Setting::where('key', 'google_login')->value('value');

            if(!$google_login){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Google login is not allowed',
                ], 404);
            }

            try {
                $googleUser = Socialite::driver('google')->userFromToken($validatedData['token']);
                if (empty($googleUser->email)) {
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'Email not found, try another Google email',
                    ], 400);
                }
            } catch (Exception $e) {
                return response()->json([
                    'status_code' => 401,
                    'success' => false,
                    'message' => 'Invalid Google token',
                ], 401);
            }
            
            // Find user by Google ID and email
            $user = User::where('email', $googleUser->email)->where('google_id', $googleUser->id)->first();
            
            if (!$user) {
                // Generate a secure random password
                $password = substr(str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&'), 0, 10);
                
                // Create a new user
                $user = User::create([
                    'email' => $googleUser->email,
                    'password' => bcrypt($password),
                    'first_name' => $googleUser->user['given_name'] ?? '',
                    'last_name' => $googleUser->user['family_name'] ?? '',
                    'google_id' => $googleUser->id,
                    'image' => $googleUser->avatar,
                    'provider' => 'google',
                    'email_verified_at' => now(),
                    'status' => 'active'
                ]);

                // Get fresh user data
                $user->refresh();
                
                // Map user details
                $userDetails = $this->mapUserDetails($user);

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => [
                        'user' => $userDetails,
                        'completed_on_boarding' => [
                            'interests' => $completedInterests,
                            'gender' => $completedGender,
                            'birthday' => $completedBirthday,
                            'image' => $completedImage
                        ]
                    ]
                ], 200);
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
            
            // // Check if any onboarding item is incomplete
            // if (!$completedInterests || !$completedGender || !$completedBirthday || !$completedImage) {
            //     // Update device info and last login without giving token
            //     $user->update([
            //         'device_token' => $validatedData['device_token'] ?? null,
            //         'device_type' => $validatedData['device_type'] ?? null,
            //         'last_login_at' => now()
            //     ]);
                
            //     $user->refresh();
            //     $userDetails = $this->mapUserDetails($user);
                
            //     return response()->json([
            //         'status_code' => 200,
            //         'success' => true,
            //         'message' => 'Login successful',
            //         'user' => $userDetails,
            //         'completed_on_boarding' => [
            //             'interests' => $completedInterests,
            //             'gender' => $completedGender,
            //             'birthday' => $completedBirthday,
            //             'image' => $completedImage
            //         ]
            //     ], 200);
            // }
            
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
                    'completed_on_boarding' => [
                        'interests' => $completedInterests,
                        'gender' => $completedGender,
                        'birthday' => $completedBirthday,
                        'image' => $completedImage
                    ]
                ],
                'token_type' => 'Bearer',
            ], 200)->header('Authorization', $token);

        } catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }
            
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
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

    public function appleLogin(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'token' => 'required|string',
                'first_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'device_token' => 'nullable|string',
                'device_type' => 'nullable|string|in:ios,android,web'
            ]);

            $completedInterests = true;
            $completedGender = true;
            $completedBirthday = true;
            $completedImage = true;

            $apple_login = Setting::where('key', 'apple_login')->value('value');

            if(!$apple_login){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Apple login is not allowed',
                ], 404);
            }

            // Decode Apple JWT
            try {
                $response = file_get_contents('https://appleid.apple.com/auth/keys');
                if (!$response) {
                    return response()->json([
                        'status_code' => 500,
                        'success' => false,
                        'message' => 'Failed to fetch Apple authentication keys',
                    ], 500);
                }

                $decodedToken = JWT::decode(
                    $validatedData['token'],
                    JWK::parseKeySet(json_decode($response, true))
                );

                $userEmail = $decodedToken->email ?? null;

                if (empty($userEmail)) {
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'Email not found in Apple authentication',
                    ], 400);
                }
            } catch (Exception $e) {
                return response()->json([
                    'status_code' => 401,
                    'success' => false,
                    'message' => 'Invalid Apple token',
                    'error' => $e->getMessage()
                ], 401);
            }
            
            // Find user by email
            $user = User::where('email', $userEmail)->first();
            
            if (!$user) {
                // Create a new user
                $password = substr(str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&'), 0, 10);
                
                $user = User::create([
                    'email' => $userEmail,
                    'password' => bcrypt($password),
                    'first_name' => $validatedData['first_name'] ?? '',
                    'last_name' => $validatedData['last_name'] ?? '',
                    'provider' => 'apple',
                    'email_verified_at' => now(),
                    'status' => 'active'
                ]);
                
                // Get fresh user data
                $user->refresh();
                
                // Map user details
                $userDetails = $this->mapUserDetails($user);

                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => [
                        'user' => $userDetails,
                        'completed_on_boarding' => [
                            'interests' => $completedInterests,
                            'gender' => $completedGender,
                            'birthday' => $completedBirthday,
                            'image' => $completedImage
                        ]
                    ]
                ], 200);
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
            // if (!$completedInterests || !$completedGender || !$completedBirthday || !$completedImage) {
            //     // Update device info and last login without giving token
            //     $user->update([
            //         'device_token' => $validatedData['device_token'] ?? null,
            //         'device_type' => $validatedData['device_type'] ?? null,
            //         'last_login_at' => now()
            //     ]);
                
            //     $user->refresh();
            //     $userDetails = $this->mapUserDetails($user);
                
            //     return response()->json([
            //         'status_code' => 200,
            //         'success' => true,
            //         'message' => 'Login successful',
            //         'user' => $userDetails,
            //         'completed_on_boarding' => [
            //             'interests' => $completedInterests,
            //             'gender' => $completedGender,
            //             'birthday' => $completedBirthday,
            //             'image' => $completedImage
            //         ]
            //     ], 200);
            // }
            
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
                    'completed_on_boarding' => [
                        'interests' => $completedInterests,
                        'gender' => $completedGender,
                        'birthday' => $completedBirthday,
                        'image' => $completedImage
                    ]
                ],
                'token_type' => 'Bearer',
            ], 200)->header('Authorization', $token);

        } catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }
            
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
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

    public function sendMobileOtp($userPhone, $otpCode)
    {
        try {
           
            Cache::put('otp_' . $userPhone, $otpCode, now()->addMinutes(5));
        
            // Send email
            Mail::to($userPhone)->send(new SendOtpMail($otpCode));

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'OTP sent successfully',
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }   
    }

    public function sendEmailOtp($userEmail, $otpCode)
    {
        try {
           
            // Save OTP in cache for 5 minutes
            Cache::put('otp_' . $userEmail, $otpCode, now()->addMinutes(5));
        
            // Send email
            Mail::to($userEmail)->send(new SendOtpMail($otpCode));

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'OTP sent successfully',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }   
    }

    public function loginOtp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
                'phone' => 'required_if:type,phone|string|exists:users,phone|regex:/^\+[1-9]\d{1,14}$/',
                'email' => 'required_if:type,email|email|exists:users,email',
            ]);
            $checkOtpLogin = Setting::where('key', 'otp_'.$validatedData['type'].'_login')->value('value');

            if(!$checkOtpLogin){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'OTP '.$validatedData['type'].' login is not allowed',
                ], 404);
            }

            $user = User::where($validatedData['type'], $validatedData[$validatedData['type']])->first();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $otp = rand(100000, 999999);

            if($validatedData['type'] === 'phone'){
                // Delete expired OTP records for this phone
                Otp::where('phone', $validatedData['phone'])
                   ->where('type', 'login')
                   ->where('expires_at', '<=', now())
                   ->delete();

                // Check for existing valid OTP
                $existingOtp = Otp::where('phone', $validatedData['phone'])
                                 ->where('type', 'login')
                                 ->where('expires_at', '>', now())
                                 ->first();

                if($existingOtp){
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'OTP already sent wait 5 minutes to send again',
                    ], 400);
                }

                // Create new OTP record
                Otp::create([
                    'otp' => $otp,
                    'type' => 'login',
                    'phone' => $validatedData['phone'],
                    'expires_at' => now()->addMinutes(5),
                ]);

                return $this->sendMobileOtp($validatedData['phone'], $otp);
            } else {
                // Delete expired OTP records for this email
                Otp::where('email', $validatedData['email'])
                   ->where('type', 'login')
                   ->where('expires_at', '<=', now())
                   ->delete();

                // Check for existing valid OTP
                $existingOtp = Otp::where('email', $validatedData['email'])
                                 ->where('type', 'login')
                                 ->where('expires_at', '>', now())
                                 ->first();

                if($existingOtp){
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'OTP already sent wait 5 minutes to send again',
                    ], 400);
                }

                // Create new OTP record
                Otp::create([
                    'otp' => $otp,
                    'type' => 'login',
                    'email' => $validatedData['email'],
                    'expires_at' => now()->addMinutes(5),
                ]);

                return $this->sendEmailOtp($validatedData['email'], $otp);
            }
        }
        catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }
            
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function verifyLoginOtp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
                'phone' => 'required_if:type,phone|string|exists:users,phone|regex:/^\+[1-9]\d{1,14}$/',
                'email' => 'required_if:type,email|email|exists:users,email',
                'otp' => 'required|string',
            ]);

            $otp = Otp::where('otp', $validatedData['otp'])
                      ->where('type', 'login')
                      ->where($validatedData['type'], $validatedData[$validatedData['type']])
                      ->where('expires_at', '>', now())
                      ->first();

            if(!$otp){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 400);
            }

            $user = User::where($validatedData['type'], $validatedData[$validatedData['type']])->first();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if($user->is_blocked){
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Your account has been blocked. Please contact support.',
                ], 403);
            }

            if($user->status === 'inactive'){
                return response()->json([
                    'status_code' => 403,
                    'success' => false,
                    'message' => 'Your account has been inactive. Please contact support.',
                ], 403);
            }

            $completedInterests = true;
            $completedGender = true;
            $completedBirthday = true;
            $completedImage = true;


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

            $token = $user->createToken('auth_token')->accessToken;

            $otp->delete();
            $user->update([
                'last_login_at' => now()
            ]);

            // Map user details
            $userDetails = $this->mapUserDetails($user);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $userDetails,
                    'completed_on_boarding' => [
                        'interests' => $completedInterests,
                        'gender' => $completedGender,
                        'birthday' => $completedBirthday,
                        'image' => $completedImage
                    ]
                ],
                'token_type' => 'Bearer',
            ], 200)->header('Authorization', $token);
            
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }

            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function sendForgotPasswordOtp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
                'phone' => 'required_if:type,phone|string|exists:users,phone|regex:/^\+[1-9]\d{1,14}$/',
                'email' => 'required_if:type,email|email|exists:users,email',
            ]);

            $checkForgotPassword = Setting::where('key', 'forget_password')->value('value');

            if(!$checkForgotPassword){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Forgot password is not allowed',
                ], 404);
            }

            $user = User::where($validatedData['type'], $validatedData[$validatedData['type']])->first();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $checkOtpForgotPassword = Setting::where('key', 'otp_'.$validatedData['type'].'_forgot_password')->value('value');

            if(!$checkOtpForgotPassword){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'OTP '.$validatedData['type'].' forgot password is not allowed',
                ], 404);
            }

            $otp = rand(100000, 999999);

            if($validatedData['type'] === 'phone'){
                Otp::where('phone', $validatedData['phone'])
                   ->where('type', 'forgot_password')
                   ->where('expires_at', '<=', now())
                   ->delete();

                $existingOtp = Otp::where('phone', $validatedData['phone'])
                                 ->where('type', 'forgot_password')
                                 ->where('expires_at', '>', now())
                                 ->first();

                if($existingOtp){
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'OTP already sent wait 5 minutes to send again',
                    ], 400);
                }

                Otp::create([
                    'otp' => $otp,
                    'type' => 'forgot_password',
                    'phone' => $validatedData['phone'],
                    'expires_at' => now()->addMinutes(5),
                ]);
                
                return $this->sendMobileOtp($validatedData['phone'], $otp);
            } else {
                Otp::where('email', $validatedData['email'])
                   ->where('type', 'forgot_password')
                   ->where('expires_at', '<=', now())
                   ->delete();

                $existingOtp = Otp::where('email', $validatedData['email'])
                                 ->where('type', 'forgot_password')
                                 ->where('expires_at', '>', now())
                                 ->first();

                if($existingOtp){
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'OTP already sent wait 5 minutes to send again',
                    ], 400);
                }

                Otp::create([
                    'otp' => $otp,
                    'type' => 'forgot_password',
                    'email' => $validatedData['email'],
                    'expires_at' => now()->addMinutes(5),
                ]);

                return $this->sendEmailOtp($validatedData['email'], $otp);
            }
        }
        catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }

            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function verifyForgotPasswordOtp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
                'phone' => 'required_if:type,phone|string|exists:users,phone|regex:/^\+[1-9]\d{1,14}$/',
                'email' => 'required_if:type,email|email|exists:users,email',
                'otp' => 'required|string',
            ]);

            $otp = Otp::where('otp', $validatedData['otp'])
                      ->where('type', 'forgot_password')
                      ->where($validatedData['type'], $validatedData[$validatedData['type']])
                      ->where('expires_at', '>', now())
                      ->first();

            if(!$otp){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 400);
            }

            $user = User::where($validatedData['type'], $validatedData[$validatedData['type']])->first();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if($validatedData['type'] === 'phone'){
                $otp->update([
                    'phone_verified' => true
                ]);
            } else if($validatedData['type'] === 'email') {
                $otp->update([
                    'email_verified' => true
                ]);
            }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'OTP verified successfully',
            ], 200);
        }
        catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }
            
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function resetForgotPassword(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
                'phone' => 'required_if:type,phone|string|exists:users,phone|regex:/^\+[1-9]\d{1,14}$/',
                'email' => 'required_if:type,email|email|exists:users,email',
                'password' => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8|same:password',
            ]);

            $user = User::where($validatedData['type'], $validatedData[$validatedData['type']])->first();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $otp = Otp::where('type', 'forgot_password')
                      ->where($validatedData['type'], $validatedData[$validatedData['type']])
                      ->where($validatedData['type'].'_verified', '1')
                      ->where('expires_at', '>', now())
                      ->first();

            if(!$otp){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 400);
            }

            $user->update([
                'password' => Hash::make($validatedData['password'])
            ]);
            $otp->delete();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Password reset successfully',
            ], 200);
        }
        catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }

            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function sendResetPasswordOtp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
            ]);

            $checkResetPassword = Setting::where('key', 'reset_password')->value('value');

            if(!$checkResetPassword){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'Reset password is not allowed',
                ], 404);
            }
            $user = Auth::guard('user')->user();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
            if($validatedData['type'] === 'phone'){
                $checkOtpResetPhonePassword = Setting::where('key', 'otp_mobile_reset_password')->value('value');
                if(!$checkOtpResetPhonePassword){
                    return response()->json([
                        'status_code' => 404,
                        'success' => false,
                        'message' => 'OTP mobile reset password is not allowed',
                    ], 404);
                }
                $otp = rand(100000, 999999);

                Otp::where('phone', $user->phone)
                   ->where('type', 'reset_password')
                   ->where('expires_at', '<=', now())
                   ->delete();

                $existingOtp = Otp::where('phone', $user->phone)
                                 ->where('type', 'reset_password')
                                 ->where('expires_at', '>', now())
                                 ->first();

                if($existingOtp){
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'OTP already sent wait 5 minutes to send again',
                    ], 400);
                }

                Otp::create([
                    'otp' => $otp,
                    'type' => 'reset_password',
                    'phone' => $user->phone,
                    'expires_at' => now()->addMinutes(5),
                ]);

                return $this->sendMobileOtp($user->phone, $otp);

            } else {
                $checkOtpResetEmailPassword = Setting::where('key', 'otp_email_reset_password')->value('value');
                if(!$checkOtpResetEmailPassword){
                    return response()->json([
                        'status_code' => 404,
                        'success' => false,
                        'message' => 'OTP email reset password is not allowed',
                    ], 404);
                }
                $otp = rand(100000, 999999);

                Otp::where('email', $user->email)
                   ->where('type', 'reset_password')
                   ->where('expires_at', '<=', now())
                   ->delete();

                $existingOtp = Otp::where('email', $user->email)
                                 ->where('type', 'reset_password')
                                 ->where('expires_at', '>', now())
                                 ->first();

                if($existingOtp){
                    return response()->json([
                        'status_code' => 400,
                        'success' => false,
                        'message' => 'OTP already sent wait 5 minutes to send again',
                    ], 400);
                }

                Otp::create([
                    'otp' => $otp,
                    'type' => 'reset_password',
                    'email' => $user->email,
                    'expires_at' => now()->addMinutes(5),
                ]);

                return $this->sendEmailOtp($user->email, $otp);

            }
        }
        catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }

            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function verifyResetPasswordOtp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
                'otp' => 'required|string',
            ]);

            $user = Auth::guard('user')->user();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $otp = Otp::where('otp', $validatedData['otp'])
                      ->where('type', 'reset_password')
                      ->where($validatedData['type'], $user->{$validatedData['type']})
                      ->where('expires_at', '>', now())
                      ->first();

            if(!$otp){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 400);
            }
           

                if($validatedData['type'] === 'phone'){
                    $otp->update([
                        'phone_verified' => true
                    ]);
                } else {
                    $otp->update([
                        'email_verified' => true
                    ]);
                }

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'OTP verified successfully',
            ], 200);

        }
        catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }

            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function resetResetPassword(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type' => 'required|string|in:email,phone',
                'old_password' => 'required|string|min:8',
                'new_password' => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8|same:new_password',
            ]);

            $user = Auth::guard('user')->user();

            if(!$user){
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $otp = Otp::where('type', 'reset_password')
                      ->where($validatedData['type'] , $user->{$validatedData['type']})
                      ->where($validatedData['type'].'_verified', '1')
                      ->where('expires_at', '>', now())
                      ->first();

            if(!$otp){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 400);
            }


            if(!Hash::check($validatedData['old_password'], $user->password)){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Old password is incorrect',
                ], 400);
            }

            if(Hash::check($validatedData['new_password'], $user->password)){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'New password cannot be the same as the old password',
                ], 400);
            }

            $user->update([
                'password' => Hash::make($validatedData['new_password'])
            ]);

            $otp->delete();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Password reset successfully',
            ], 200);
        }
        catch (ValidationException $e) {
            $errors = $e->errors();
            $formattedErrors = [];
            foreach ($errors as $field => $messages) {
                $formattedErrors[$field] = implode(', ', $messages);
            }

            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $formattedErrors
            ], 422);
        }
    }

    public function verifyToken(Request $request)
    {

       $user = Auth::guard('user')->user();
        if(!$user){
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

            $completedInterests = true;
            $completedGender = true;
            $completedBirthday = true;
            $completedImage = true;

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

        $userDetails = $this->mapUserDetails($user);

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Token verified successfully',
            'data' => [
                'user' => $userDetails,
                'completed_on_boarding' => [
                    'interests' => $completedInterests,
                    'gender' => $completedGender,
                    'birthday' => $completedBirthday,
                    'image' => $completedImage
                ]
            ]
        ], 200);
    }
}