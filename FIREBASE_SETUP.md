# Firebase Push Notifications Setup Guide

This guide will help you set up Firebase push notifications for your Sloopify application.

## Prerequisites

1. A Firebase project (create one at [Firebase Console](https://console.firebase.google.com/))
2. Firebase Admin SDK service account key
3. Firebase Cloud Messaging (FCM) configured

## Step 1: Firebase Project Setup

### 1.1 Create Firebase Project
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Create a project" or "Add project"
3. Enter your project name (e.g., "sloopify-app")
4. Configure Google Analytics (optional)
5. Click "Create project"

### 1.2 Enable Cloud Messaging
1. In your Firebase project, go to "Project settings" (gear icon)
2. Click on the "Cloud Messaging" tab
3. Note down your:
   - **Server key** (Legacy server key)
   - **Sender ID**

### 1.3 Generate Service Account Key
1. Go to "Project settings" → "Service accounts"
2. Click "Generate new private key"
3. Download the JSON file
4. Rename it to `service-account-file.json`
5. Place it in `storage/app/firebase/` directory

## Step 2: Environment Configuration

Add these variables to your `.env` file:

```env
# Firebase Configuration
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-file.json
FIREBASE_DATABASE_URL=https://your-project-id-default-rtdb.firebaseio.com/
FIREBASE_NOTIFICATIONS_ENABLED=true

# Firebase Cloud Messaging (FCM)
FCM_SERVER_KEY=your-fcm-server-key
FCM_SENDER_ID=your-fcm-sender-id
FCM_API_KEY=your-fcm-api-key
```

Replace the placeholders with your actual Firebase project values.

## Step 3: Client Setup

### For Android (Kotlin/Java)

1. Add Firebase to your Android project:
   - Download `google-services.json` from Firebase console
   - Place it in `app/` directory

2. Add FCM dependency in `build.gradle`:
   ```gradle
   implementation 'com.google.firebase:firebase-messaging:23.4.0'
   ```

3. Get FCM token:
   ```kotlin
   FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
       if (!task.isSuccessful) {
           Log.w(TAG, "Fetching FCM registration token failed", task.exception)
           return@addOnCompleteListener
       }
       
       // Get new FCM registration token
       val token = task.result
       Log.d(TAG, "FCM Registration Token: $token")
       
       // Send token to your server
       sendTokenToServer(token)
   }
   ```

### For iOS (Swift)

1. Add Firebase to your iOS project:
   - Download `GoogleService-Info.plist` from Firebase console
   - Add it to your Xcode project

2. Add FCM dependency in `Podfile`:
   ```ruby
   pod 'Firebase/Messaging'
   ```

3. Get FCM token:
   ```swift
   Messaging.messaging().token { token, error in
       if let error = error {
           print("Error fetching FCM registration token: \(error)")
       } else if let token = token {
           print("FCM registration token: \(token)")
           // Send token to your server
           sendTokenToServer(token)
       }
   }
   ```

### For Web (JavaScript)

1. Add Firebase SDK to your web app:
   ```html
   <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-app.js"></script>
   <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging.js"></script>
   ```

2. Initialize Firebase and get token:
   ```javascript
   import { initializeApp } from 'firebase/app';
   import { getMessaging, getToken } from 'firebase/messaging';

   const firebaseConfig = {
       // Your Firebase config
   };

   const app = initializeApp(firebaseConfig);
   const messaging = getMessaging(app);

   getToken(messaging, { vapidKey: 'your-vapid-key' }).then((currentToken) => {
       if (currentToken) {
           console.log('Registration token available.');
           // Send token to your server
           sendTokenToServer(currentToken);
       } else {
           console.log('No registration token available.');
       }
   }).catch((err) => {
       console.log('An error occurred while retrieving token. ', err);
   });
   ```

## Step 4: API Integration

### Send Push Token During Login

When users log in, send their FCM token:

```json
POST /api/v1/auth/login
{
    "email": "user@example.com",
    "password": "password",
    "push_token": "FCM_TOKEN_HERE",
    "device_id": "unique_device_id"
}
```

### Update Push Token

```json
POST /api/v1/sessions/update-push-token
{
    "push_token": "NEW_FCM_TOKEN_HERE"
}
```

## Step 5: Testing Notifications

### Test with Postman or cURL

```bash
curl -X POST "https://fcm.googleapis.com/fcm/send" \
  -H "Authorization: key=YOUR_SERVER_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "USER_FCM_TOKEN",
    "notification": {
      "title": "Test Notification",
      "body": "This is a test notification"
    }
  }'
```

### Test Post Notifications

1. Create a post with mentions
2. Check logs for notification sending
3. Verify notifications are received on client devices

## Step 6: Notification Channels (Android)

For Android, create notification channels in your app:

```kotlin
private fun createNotificationChannels() {
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
        val channels = listOf(
            NotificationChannel(
                "general",
                "General Notifications",
                NotificationManager.IMPORTANCE_DEFAULT
            ),
            NotificationChannel(
                "mentions",
                "Mentions",
                NotificationManager.IMPORTANCE_HIGH
            ),
            NotificationChannel(
                "posts",
                "Post Notifications",
                NotificationManager.IMPORTANCE_DEFAULT
            ),
            NotificationChannel(
                "friends",
                "Friend Requests",
                NotificationManager.IMPORTANCE_DEFAULT
            )
        )
        
        val notificationManager = getSystemService(NotificationManager::class.java)
        channels.forEach { notificationManager.createNotificationChannel(it) }
    }
}
```

## Step 7: Troubleshooting

### Common Issues

1. **Firebase not initialized**: Check credentials path and project ID
2. **No push tokens**: Ensure clients are properly requesting and sending tokens
3. **Notifications not received**: Check device settings and notification permissions
4. **Invalid token errors**: Implement token refresh logic

### Debug Logs

Check Laravel logs at `storage/logs/laravel.log` for Firebase-related errors:

```bash
tail -f storage/logs/laravel.log | grep -i firebase
```

### Cleanup Commands

Run cleanup commands periodically:

```bash
# Clean up expired muted notifications
php artisan notifications:cleanup-muted

# Clean up expired sessions
php artisan schedule:run
```

## Security Considerations

1. **Keep service account key secure**: Never commit it to version control
2. **Validate push tokens**: Ensure tokens come from authenticated users
3. **Rate limiting**: Implement rate limiting for notification sending
4. **User preferences**: Respect user notification preferences and mute settings

## Production Deployment

1. Set `FIREBASE_NOTIFICATIONS_ENABLED=true` in production
2. Use proper server key and credentials
3. Monitor notification delivery rates
4. Set up alerts for Firebase errors
5. Configure proper backup and recovery procedures

For more information, refer to the [Firebase Cloud Messaging documentation](https://firebase.google.com/docs/cloud-messaging). 