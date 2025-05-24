<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Setting::create([
            'key' => 'sidebar_logo',
            'value' => 'sloopify-logo.svg',
            'description' => 'Sidebar Logo',
        ]);

        Setting::create([
            'key' => 'on_boarding1_text',
            'value' => 'Create an account to get started',
            'description' => 'On Boarding 1 Text',
        ]);

        Setting::create([
            'key' => 'show_on_boarding1_text',
            'value' => '1',
            'description' => 'Show On Boarding 1 Text',
        ]);

        Setting::create([
            'key' => 'on_boarding2_text',
            'value' => 'Create an account to get started',
            'description' => 'On Boarding 2 Text',
        ]);

        Setting::create([
            'key' => 'show_on_boarding2_text',
            'value' => '1',
            'description' => 'Show On Boarding 2 Text',
        ]);

        Setting::create([
            'key' => 'on_boarding3_text',   
            'value' => 'Create an account to get started',
            'description' => 'On Boarding 3 Text',
        ]);

        Setting::create([
            'key' => 'show_on_boarding3_text',
            'value' => '1',
            'description' => 'Show On Boarding 3 Text',
        ]);


        Setting::create([
            'key' => 'require_interest_in_on_boarding',
            'value' => '1',
            'description' => 'Require Interest in On Boarding',
        ]);


        Setting::create([
            'key' => 'require_gender_in_on_boarding',
            'value' => '1',
            'description' => 'Require Gender in On Boarding',
        ]);


        Setting::create([
            'key' => 'require_birthday_in_on_boarding',
            'value' => '1',
            'description' => 'Require Birthday in On Boarding',
        ]);

        Setting::create([
            'key' => 'required_upload_user_image_in_on_boarding',
            'value' => '1',
            'description' => 'Required Upload User Image in On Boarding',
        ]);

        Setting::create([
            'key' => 'show_reffered_by_in_on_boarding',
            'value' => '1',
            'description' => 'Show Reffered By in On Boarding',
        ]);

        Setting::create([
            'key' => 'google_login',
            'value' => '1',
            'description' => 'Google Login',
        ]);

        Setting::create([
            'key' => 'apple_login',
            'value' => '1',
            'description' => 'Apple Login',
        ]);

        Setting::create([
            'key' => 'email_login',
            'value' => '1',
            'description' => 'Email Login',
        ]);

        Setting::create([
            'key' => 'mobile_login',
            'value' => '1',
            'description' => 'Mobile Login',
        ]);

        Setting::create([
            'key' => 'otp_mobile_login',
            'value' => '1',
            'description' => 'OTP Mobile Login',
        ]);

        Setting::create([
            'key' => 'otp_email_login',
            'value' => '1',
            'description' => 'OTP Email Login',
        ]);

        Setting::create([
            'key' => 'forget_password',
            'value' => '1',
            'description' => 'Forget Password',
        ]);

        Setting::create([
            'key' => 'otp_mobile_forgot_password',
            'value' => '1',
            'description' => 'OTP Mobile Forgot Password',
        ]);

        Setting::create([
            'key' => 'otp_email_forgot_password',
            'value' => '1',
            'description' => 'OTP Email Forgot Password',
        ]);

        Setting::create([
            'key' => 'create_user_normal_post',
            'value' => '1',
            'description' => 'Create User Normal Post',
        ]);

        Setting::create([
            'key' => 'create_user_normal_post_text',
            'value' => '1',
            'description' => 'Create User Normal Post Text',
        ]);

        Setting::create([
            'key' => 'show_user_normal_post_text_editor',
            'value' => '1',
            'description' => 'Show User Normal Post Text Editor',
        ]);
        
        Setting::create([
            'key' => 'create_user_normal_post_photo',
            'value' => '1',
            'description' => 'Create User Normal Post Photo',
        ]);

        Setting::create([
            'key' => 'create_user_normal_post_video',
            'value' => '1',
            'description' => 'Create User Normal Post Video',
        ]);

        Setting::create([ 
            'key' => 'create_user_normal_post_live',
            'value' => '1',
            'description' => 'Create User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_flash_light_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Flash Light in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_mute_microphone_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Mute Microphone in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_rotate_camera_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Rotate Camera in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_noise_cancellation_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Noise Cancellation in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_invite_friends_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Invite Friends in User Normal Post Live',
        ]);
        
        Setting::create([ 
            'key' => 'show_insert_location_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Insert Location in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_add_to_post_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Add to Post in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_add_to_post_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Add to Post in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_share_in_story_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Share in Story in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_comment_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Comment in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'add_comment_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Add Comment in User Normal Post Live',
        ]);

        Setting::create([ 
            'key' => 'show_comment_details_in_user_normal_post_live',
            'value' => '1',
            'description' => 'Show Comment Details in User Normal Post Live',
        ]);

        Setting::create([
            'key' => 'create_user_normal_post_camera',
            'value' => '1',
            'description' => 'Create User Normal Post Camera',
        ]);

        Setting::create([
            'key' => 'create_user_normal_post_opinion_poll',
            'value' => '1',
            'description' => 'Create User Normal Post Opinion Poll',
        ]);

        Setting::create([
            'key' => 'create_user_normal_post_personal_occasion',
            'value' => '1',
            'description' => 'Create User Normal Post Personal Occasion',
        ]);

        Setting::create([
            'key' => 'add_friend_to_normal_post_user',
            'value' => '1',
            'description' => 'Add Friend to Normal Post User',
        ]);

        Setting::create([
            'key' => 'add_mention_to_normal_post_user',
            'value' => '1',
            'description' => 'Add Mention to Normal Post User',
        ]);

        Setting::create([
            'key' => 'add_albums_to_normal_post_user',
            'value' => '1',
            'description' => 'Add Albums to Normal Post User',
        ]);

        Setting::create([
            'key' => 'add_feelings_to_normal_post_user',
            'value' => '1',
            'description' => 'Add Feelings to Normal Post User',
        ]);

        Setting::create([
            'key' => 'add_activity_to_normal_post_user',
            'value' => '1',
            'description' => 'Add Activity to Normal Post User',
        ]);

        Setting::create([
            'key' => 'add_location_to_normal_post_user',
            'value' => '1',
            'description' => 'Add Location to Normal Post User',
        ]);

        Setting::create([
            'key' => 'create_user_24_hours_post',
            'value' => '1',
            'description' => 'Create User 24 Hours Post',
        ]);

        Setting::create([
            'key' => 'schedule_normal_post_user',
            'value' => '1',
            'description' => 'Schedule Normal Post User',
        ]);

        Setting::create([
            'key' => 'show_post_audience',
            'value' => '1',
            'description' => 'Show Post Audience',
        ]);

        Setting::create([
            'key' => 'show_public_in_post_audience_settings',
            'value' => '1',
            'description' => 'Show Public in Post Audience Settings',
        ]);

        Setting::create([
            'key' => 'show_friends_in_post_audience_settings',
            'value' => '1',
            'description' => 'Show Friends in Post Audience Settings',
        ]);

        Setting::create([
            'key' => 'show_friends_except_me_in_post_audience_settings',
            'value' => '1',
            'description' => 'Show Friends Except Me in Post Audience Settings',
        ]);

        Setting::create([
            'key' => 'show_specific_friends_in_post_audience_settings',
            'value' => '1',
            'description' => 'Show Specific Friends in Post Audience Settings',
        ]);

        Setting::Create([
            'key' => 'show_only_me_in_post_audience_settings',
            'value' => '1',
            'description' => 'Show Only Me in Post Audience Settings',
        ]);

        Setting::create([
            'key' => 'show_story_user',
            'value' => '1',
            'description' => 'Show Story User',
        ]);

        Setting::create([
            'key' => 'create_story_user_text',
            'value' => '1',
            'description' => 'Create Story User Text',
        ]);

        Setting::create([
            'key' => 'create_story_user_photo',
            'value' => '1',
            'description' => 'Create Story User Photo',
        ]);

        Setting::create([
            'key' => 'create_story_user_video',
            'value' => '1',
            'description' => 'Create Story User Video',
        ]);

        Setting::create([
            'key' => 'create_story_user_live',
            'value' => '1',
            'description' => 'Create Story User Live',
        ]);
        
        Setting::create([
            'key' => 'show_editing_story_user_text',
            'value' => '1',
            'description' => 'Show Editing Story User Text',
        ]);

        Setting::create([
            'key' => 'show_editing_story_user_photo',
            'value' => '1',
            'description' => 'Show Editing Story User Photo',
        ]);

        Setting::create([
            'key' => 'show_editing_story_user_video',
            'value' => '1',
            'description' => 'Show Editing Story User Video',
        ]);

        Setting::create([
            'key' => 'show_setting_story_user',
            'value' => '1',
            'description' => 'Show Setting Story User',
        ]);

        Setting::create([
            'key' => 'show_public_in_story_user_settings',
            'value' => '1',
            'description' => 'Show Public in Story User Settings',
        ]);

        Setting::create([
            'key' => 'show_friends_in_story_user_settings',
            'value' => '1',
            'description' => 'Show Friends in Story User Settings',
        ]);

            Setting::create([
                'key' => 'show_friends_except_me_in_story_user_settings',
                'value' => '1',
                'description' => 'Show Friends Except Me in Story User Settings',
            ]);

        Setting::create([
            'key' => 'show_specific_friends_in_story_user_settings',
            'value' => '1',
            'description' => 'Show Specific Friends in Story User Settings',
        ]);
        
        Setting::create([
            'key' => 'show_only_me_in_story_user_settings',
            'value' => '1',
            'description' => 'Show Only Me in Story User Settings',
        ]);

        Setting::create([
            'key' => 'create_opinion_poll_in_story_user',
            'value' => '1',
            'description' => 'Create Opinion Poll in Story User',
        ]);


        Setting::create([
            'key' => 'add_friends_in_user_story',
            'value' => '1',
            'description' => 'Add Friends in User Story',
        ]);

        Setting::create([
            'key' => 'add_mention_in_user_story',
            'value' => '1',
            'description' => 'Add Mention in User Story',
        ]);

        Setting::create([
            'key' => 'add_location_in_user_story',
            'value' => '1',
            'description' => 'Add Location in User Story',
        ]);

        Setting::create([
            'key' => 'add_feeling_in_user_story',
            'value' => '1',
            'description' => 'Add Feeling in User Story',
        ]);
        

        Setting::create([
            'key' => 'show_daily_status_user',
            'value' => '1',
            'description' => 'Show Daily Status User',
        ]);


       Setting::create([
        'key' => 'active_ai_chat_user',
        'value' => '1',
        'description' => 'Active AI Chat User',
       ]);

       Setting::create([
        'key' => 'active_ai_chat_for_the_verification_account_user',
        'value' => '1',
        'description' => 'Active AI Chat for the Verification Account User',
       ]);

       Setting::create([
        'key' => 'show_normal_notification_user',
        'value' => '1',
        'description' => 'Show Normal Notification User',
       ]);

       Setting::create([
        'key' => 'active_send_normal_notification_user',
        'value' => '1',
        'description' => 'Active Send Normal Notification User',
       ]);

       Setting::create([
        'key' => 'active_send_normal_notification_user',
        'value' => '1',
        'description' => 'Active Send Normal Notification User',
       ]);

       Setting::create([
        'key' => 'setting_normal_notification_user',
        'value' => '1',
        'description' => 'Setting Normal Notification User',
       ]);

       Setting::create([
        'key' => 'show_admin_notification_in_user',
        'value' => '1',
        'description' => 'Show Admin Notification in User',
       ]);


    }
}
