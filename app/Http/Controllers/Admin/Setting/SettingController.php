<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    //
    public function index()
    {
        
        $allSettings = Setting::all();
        
        $groupedSettings = [
            'general' => $allSettings->filter(function($setting) {
                // Include settings that don't belong to other specific categories
                $otherCategories = [
                    'on_boarding', 'show_interest_in_on_boarding', 'require_interest_in_on_boarding',
                    'show_gender_in_on_boarding', 'require_gender_in_on_boarding',
                    'show_birthday_in_on_boarding', 'require_birthday_in_on_boarding',
                    'show_fill_account_in_on_boarding', 'require_fill_account_in_on_boarding',
                    'show_reffered_by_in_on_boarding', 'required_upload_user_image_in_on_boarding',
                    'google_login', 'apple_login', 'email_login', 'mobile_login', 'otp_login', 
                    'forget_password', 'create_user_normal_post', 'show_user_normal_post', 
                    '_in_user_normal_post_live', '_in_post_audience_settings', 'show_post_audience', 
                    'show_story_user', 'create_story_user_', 'show_editing_story_user_',
                    'show_setting_story_user', '_in_story_user_settings', 'create_opinion_poll_in_story_user',
                    '_in_user_story', 'show_daily_status_user', 'active_ai_chat', 
                    'show_normal_notification_user', 'active_send_normal_notification_user', 
                    'setting_normal_notification_user', 'show_admin_notification_in_user'
                ];
                
                foreach ($otherCategories as $category) {
                    if (strpos($setting->key, $category) !== false) {
                        return false;
                    }
                }
                return true;
            }),
            'onboarding' => $allSettings->filter(function($setting) {
                return strpos($setting->key, 'on_boarding') !== false || 
                       strpos($setting->key, 'show_interest_in_on_boarding') !== false || 
                       strpos($setting->key, 'require_interest_in_on_boarding') !== false ||
                       strpos($setting->key, 'show_gender_in_on_boarding') !== false ||
                       strpos($setting->key, 'require_gender_in_on_boarding') !== false ||
                       strpos($setting->key, 'show_birthday_in_on_boarding') !== false ||
                       strpos($setting->key, 'require_birthday_in_on_boarding') !== false ||
                       strpos($setting->key, 'show_fill_account_in_on_boarding') !== false ||
                       strpos($setting->key, 'require_fill_account_in_on_boarding') !== false ||
                       strpos($setting->key, 'show_reffered_by_in_on_boarding') !== false ||
                       strpos($setting->key, 'required_upload_user_image_in_on_boarding') !== false;
            }),
            'authentication' => $allSettings->filter(function($setting) {
                return in_array($setting->key, [
                    'google_login', 'apple_login', 'email_login', 
                    'mobile_login', 'otp_login', 'forget_password'
                ]);
            }),
            'normal_post' => $allSettings->filter(function($setting) {
                return strpos($setting->key, 'create_user_normal_post') === 0 || 
                       strpos($setting->key, 'show_user_normal_post') === 0 ||
                       strpos($setting->key, 'add_') === 0 && strpos($setting->key, '_to_normal_post_user') !== false;
            }),
            'post_live' => $allSettings->filter(function($setting) {
                return strpos($setting->key, '_in_user_normal_post_live') !== false;
            }),
            'post_audience' => $allSettings->filter(function($setting) {
                return strpos($setting->key, 'show_') === 0 && strpos($setting->key, '_in_post_audience_settings') !== false ||
                       $setting->key === 'show_post_audience';
            }),
            'story' => $allSettings->filter(function($setting) {
                return strpos($setting->key, 'show_story_user') === 0 || 
                       strpos($setting->key, 'create_story_user_') === 0 ||
                       strpos($setting->key, 'show_editing_story_user_') === 0 ||
                       strpos($setting->key, 'show_setting_story_user') === 0 ||
                       strpos($setting->key, '_in_story_user_settings') !== false ||
                       strpos($setting->key, 'create_opinion_poll_in_story_user') === 0 ||
                       strpos($setting->key, 'add_') === 0 && strpos($setting->key, '_in_user_story') !== false;
            }),
            'miscellaneous' => $allSettings->filter(function($setting) {
                return strpos($setting->key, 'show_daily_status_user') === 0 || 
                       strpos($setting->key, 'active_ai_chat') === 0 ||
                       strpos($setting->key, 'show_normal_notification_user') === 0 ||
                       strpos($setting->key, 'active_send_normal_notification_user') === 0 ||
                       strpos($setting->key, 'setting_normal_notification_user') === 0 ||
                       strpos($setting->key, 'show_admin_notification_in_user') === 0;
            }),
        ];
        
        return view('admin.Setting.index', compact('groupedSettings'));
    }
    
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string|exists:settings,key',
                'settings.*.value' => 'required|string',
                'settings.*.description' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            
            foreach ($request->settings as $settingData) {
                $setting = Setting::where('key', $settingData['key'])->first();
                
                if ($setting) {
                    $setting->update([
                        'value' => $settingData['value'],
                        'description' => $settingData['description'] ?? $setting->description,
                    ]);
                }
            }
            
            return redirect()->route('admin.settings.index')->with('success_message', 'Settings updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error_message', 'Failed to update settings: ' . $e->getMessage());
        }
    }
    
    public function toggleValue(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'key' => 'required|string|exists:settings,key',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }
            
            $setting = Setting::where('key', $request->key)->first();
            
            if ($setting) {
                // Toggle value between '0' and '1'
                $newValue = $setting->value == '1' ? '0' : '1';
                
                $setting->update([
                    'value' => $newValue
                ]);
                
                return response()->json([
                    'success' => true, 
                    'message' => 'Setting updated successfully',
                    'value' => $newValue
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'Setting not found']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update setting: ' . $e->getMessage()]);
        }
    }
    
    public function updateDescription(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'key' => 'required|string|exists:settings,key',
                'description' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }
            
            $setting = Setting::where('key', $request->key)->first();
            
            if ($setting) {
                $setting->update([
                    'description' => $request->description
                ]);
                
                return response()->json([
                    'success' => true, 
                    'message' => 'Description updated successfully'
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'Setting not found']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update description: ' . $e->getMessage()]);
        }
    }
}
