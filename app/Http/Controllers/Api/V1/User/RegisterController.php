<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\User\AuthController;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Otp;
use Illuminate\Validation\ValidationException;
use App\Models\Interest;

class RegisterController extends Controller
{
    //

    public function mapInterests($interests)
    {
        $groupedInterests = $interests->groupBy('category');
        
        return $groupedInterests->map(function ($categoryInterests, $categoryName) { 
            return [
                'category' => $categoryName,
                'interests' => $categoryInterests->map(function ($interest) {
                    return [
                        'id' => $interest->id,
                        'name' => $interest->name,
                        'image' => $interest->image ? config('app.url') . asset('storage/' . $interest->image) : null,
                        'status' => $interest->status,
                        'created_at' => $interest->created_at,
                        'updated_at' => $interest->updated_at,
                    ];
                })->values()
            ];
        })->values();
    }

    public function createAccount(Request $request)
    {
        try{
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|unique:users,phone|regex:/^\+[1-9]\d{1,14}$/',
            'device_id' => 'nullable|string',
            'device_type' => 'nullable|string|in:android,ios',
        ]);

           $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'phone' => $validatedData['phone'],
            'device_id' => $validatedData['device_id'] ?? null,
            'device_type' => $validatedData['device_type'] ?? null,
            'referral_code' => Str::random(6),
           ]);
           

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Account created successfully',
                'data' => (new AuthController())->mapUserDetails($user)
            ], 200);
        }
        catch (ValidationException $e) {
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

    public function sendOtp(Request $request)
    {
        try{
        $validatedData = $request->validate([
          'type' => 'required|string|in:email,phone',
          'email' => 'required_if:type,email|email|exists:users,email',
          'phone' => 'required_if:type,phone|string|exists:users,phone|regex:/^\+[1-9]\d{1,14}$/',
        ]);

        if($validatedData['type'] === 'email'){
            $user = User::where('email', $validatedData['email'])->where('email_verified_at', null)->first();
        } else {
            $user = User::where('phone', $validatedData['phone'])->where('email_verified_at', null)->first();
        }

        if(!$user){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found or already verified',    
            ], 404);
        }
       $otpCode =  rand(100000, 999999);
       
        if($validatedData['type'] === 'email'){
            $otpExists = Otp::where('email', $user->email)
                      ->where('type', 'register')
                      ->where('expires_at', '>=', now())
                      ->first();
                 
            if($otpExists){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'OTP already sent',
                ], 400);
            }
    
            Otp::where('email', $user->email)
                ->where('type', 'register')
                ->where('expires_at', '<', now())
                ->delete();

            $otp = Otp::create([
                'otp' => $otpCode,
                'type' => 'register',
                'email' => $user->email,
                'expires_at' => now()->addMinutes(5),
            ]);
           return (new AuthController())->sendEmailOtp($user->email, $otpCode);           
        }
        else if($validatedData['type'] === 'phone'){
            $otpExists = Otp::where('phone', $user->phone)
                      ->where('type', 'register')
                      ->where('expires_at', '>=', now())
                      ->first();

            if($otpExists){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'OTP already sent',
                ], 400);
            }

            Otp::where('phone', $user->phone)
                ->where('type', 'register')
                ->where('expires_at', '<', now())
                ->delete();

            $otp = Otp::create([
                'otp' => $otpCode,
                'type' => 'register',
                'phone' => $user->phone,
                'expires_at' => now()->addMinutes(5),
            ]);
            return (new AuthController())->sendMobileOtp($user->phone, $otpCode);
        }
       }
       catch (ValidationException $e) {
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

    public function verifyOtp(Request $request)
    {
        try{
        $validatedData = $request->validate([
            'otp' => 'required|string',
            'type' => 'required|string|in:email,phone',
            'email' => 'required_if:type,email|email|exists:users,email',
            'phone' => 'required_if:type,phone|string|regex:/^\+[1-9]\d{1,14}$/|exists:users,phone',
        ]);

        if($validatedData['type'] === 'email'){
            $user = User::where('email', $validatedData['email'])->first();
        } else {
            $user = User::where('phone', $validatedData['phone'])->first();
        }
        if(!$user){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found',    
            ], 404);
        }
        if($validatedData['type'] === 'email'){
            $otp = Otp::where('email', $user->email)
                ->where('otp', $validatedData['otp'])
                ->where('type', 'register')
                ->where('expires_at', '>=', now())
                ->first();
                
            if(!$otp){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 400);
            }
            $user->email_verified_at = now();
            $user->save();
            $otp->delete();
            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Email verified successfully',
            ], 200);
         }
           else if($validatedData['type'] === 'phone'){
            $otp = Otp::where('phone', $user->phone)
                ->where('otp', $validatedData['otp'])
                ->where('type', 'register')
                ->where('expires_at', '>=', now())
                ->first();

            if(!$otp){
                return response()->json([
                    'status_code' => 400,
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 400);
            }
             $user->phone_verified_at = now();
             $user->save();
             $otp->delete();
            return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Phone verified successfully',
             ], 200);
         }
      }
       catch (ValidationException $e) {
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

    public function getInterests()
    {
        try{
        $interests = Interest::where('status', 'active')->get();
        if($interests->isEmpty()){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'No interests found',    
            ], 404);
        }
        
        $interestDetails = $this->mapInterests($interests);
        
        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Interests fetched successfully',
            'data' => $interestDetails
        ], 200);
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

    public function completeInterests(Request $request)
    {
        try{
        $validatedData = $request->validate([
            'interests' => 'required|array',
            'interests.*' => 'required|string|exists:interests,id',
        ]);

        $user = Auth::guard('user')->user();
        if(!$user){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found',    
            ], 404);
        }

        $checkExistingInterests = $user->userInterests()->whereIn('interest_id', $validatedData['interests'])->exists();
        if($checkExistingInterests){
            return response()->json([
                'status_code' => 400,
                'success' => false,
                'message' => 'Interests already completed',    
            ], 400);
        }

        $user->userInterests()->attach($validatedData['interests']);
        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Interests completed successfully',    
        ], 200);
        }
        catch (ValidationException $e) {
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

    public function completeGender(Request $request)
    {
        try{
        $validatedData = $request->validate([
            'gender' => 'required|string|in:male,female',
        ]);

        $user = Auth::guard('user')->user();
        if(!$user){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found',    
            ], 404);
        }

        $user->gender = $validatedData['gender'];
        $user->save();
        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Gender completed successfully',    
        ], 200);
        }
        catch (ValidationException $e) {
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

    public function completeBirthday(Request $request)
    {
        try{
        $validatedData = $request->validate([
            'birthday' => 'required|date|before:'.now()->subYears(13)->format('Y-m-d'),
        ]);

        $user = Auth::guard('user')->user();
        if(!$user){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found',    
            ], 404);
        }

        $user->birthday = $validatedData['birthday'];
        $user->save();
        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Birthday completed successfully',    
        ], 200);
        }
        catch (ValidationException $e) {
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

    public function completeImage(Request $request)
    {
        try{
        $validatedData = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = Auth::guard('user')->user();
        if(!$user){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found',    
            ], 404);
        }

        $image = $request->file('image')->getClientOriginalName();
        $userImagePath = 'UserImage/' . $user->id;
        $path = $request->file('image')->storeAs($userImagePath, $image, 'public');

        $user->img = $path;
        $user->save();
        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Image completed successfully',    
        ], 200);
        }
        catch (ValidationException $e) {
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

    public function completeReffer(Request $request)
    {
        try{
        $validatedData = $request->validate([
            'referred_by_code' => [
                'required',
                'string',
                'exists:users,referral_code',
                function ($attribute, $value, $fail) {
                    $user = Auth::guard('user')->user();
                    if ($user && $user->referral_code === $value) {
                        $fail('You cannot refer yourself.');
                    }
                }
            ],
        ]);

        $user = Auth::guard('user')->user();
        if(!$user){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found',    
            ], 404);
        }

        $referredByUser = User::where('referral_code', $validatedData['referred_by_code'])->first();
        if(!$referredByUser){
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'Referred by user not found',    
            ], 404);
        }

        $user->reffered_by = $referredByUser->referral_code;
        $user->save();
        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Reffered by user completed successfully',    
        ], 200);
        }
        catch (ValidationException $e) {
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
