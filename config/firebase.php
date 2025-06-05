<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Firebase project settings.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    'credentials' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase/service-account-file.json')),

    'database_url' => env('FIREBASE_DATABASE_URL'),

    'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM) Settings
    |--------------------------------------------------------------------------
    */

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'sender_id' => env('FCM_SENDER_ID'),
        'api_key' => env('FCM_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'enabled' => env('FIREBASE_NOTIFICATIONS_ENABLED', true),
        
        'channels' => [
            'general' => [
                'name' => 'General Notifications',
                'description' => 'General app notifications',
                'importance' => 'high',
            ],
            'posts' => [
                'name' => 'Post Notifications',
                'description' => 'Notifications about posts from friends',
                'importance' => 'normal',
            ],
            'mentions' => [
                'name' => 'Mentions',
                'description' => 'When someone mentions you',
                'importance' => 'high',
            ],
            'friends' => [
                'name' => 'Friend Requests',
                'description' => 'Friend request notifications',
                'importance' => 'normal',
            ],
        ],

        'default_icon' => '/images/notification-icon.png',
        'default_badge' => '/images/notification-badge.png',
        
        'sound' => [
            'default' => 'default',
            'mention' => 'mention.mp3',
            'post' => 'notification.mp3',
        ],
    ],

]; 