<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\Auth\RegisterController;
use App\Http\Controllers\Api\V1\User\Post\PostController;
use App\Http\Controllers\Api\V1\User\Friend\FriendController;
use App\Http\Controllers\Api\V1\User\Settings\Sessions\SessionController;
use App\Http\Controllers\Api\V1\User\Story\StoryController;


//=================================== User Auth Routes =============================

Route::group(['prefix' => 'api/v1'], function () {

    Route::group(['prefix' => 'auth'], function () {

      //=================================== User Login =============================

      Route::group(['controller' => AuthController::class], function () {

      Route::post('/login-email' , 'loginEmail');

      Route::post('/login-mobile' , 'loginMobile');

      Route::post('/login-google' , 'googleLogin');

      Route::post('/login-apple' , 'appleLogin');

      Route::post('/login-otp' , 'loginOtp');

      Route::post('/verify-login-otp' , 'verifyLoginOtp');

      Route::post('/verify-token' , 'verifyToken')->middleware('user.auth');

      Route::post('/logout', 'logout')->middleware('user.auth');


     //=================================== User Forget password =============================

      Route::group(['prefix' => 'forgot-password'], function () {

        Route::post('/send-otp' , 'sendForgotPasswordOtp');

        Route::post('/verify-otp' , 'verifyForgotPasswordOtp');

        Route::post('/reset-password' , 'resetForgotPassword');

      });

      //=================================== User Reset password =============================

      Route::group(['prefix' => 'reset-password' , 'middleware' => ['user.auth']], function () {

        Route::post('/send-otp' , 'sendResetPasswordOtp');

        Route::post('/verify-otp' , 'verifyResetPasswordOtp');

        Route::post('/reset-password' , 'resetResetPassword');

      });

      //=================================== User Register =============================

      Route::group(['prefix' => 'register' , 'controller' => RegisterController::class], function () {

        Route::post('/create-account' , 'createAccount');

        Route::post('/send-otp' , 'sendOtp');

        Route::post('/verify-otp' , 'verifyOtp');

        Route::get('/get-interest-category' , 'getInterestCategory');

        Route::post('/get-interests-by-category-name' , 'getInterestsByCategoryName');
        
        Route::post('complete-interests' , 'completeInterests');  

        Route::post('complete-gender' , 'completeGender');

        Route::post('complete-birthday' , 'completeBirthday');

        Route::post('complete-image' , 'completeImage');

        Route::post('complete-reffer' , 'completeReffer');

         });
      });

    
   });  

     //=================================== User Post =============================

     Route::group(['prefix' => 'post' , 'middleware' => ['user.auth']], function () {
        
      Route::group(['controller' => PostController::class], function () {

        Route::post('/create-post' , 'createPost');

        Route::post('/get-feeling' , 'getFeeling');

        Route::post('/get-activity-category' , 'getActivityCategory');

        Route::post('/get-activity-by-category-name' , 'getActivityByCategoryName');

        Route::post('search-feeling' , 'searchFeeling');

        Route::post('search-activity-by-category' , 'searchActivityByCategory');

        Route::post('search-activity' , 'searchActivity');
        
        Route::get('check-server-capabilities' , 'checkServerCapabilities');

        Route::post('get-friends' , 'getFriends');

        Route::post('search-friends' , 'searchFriends');

        Route::post('get-personal-occasion-categories' , 'getPersonalOccasionCategories');

        Route::post('get-personal-occasion-categories-with-occasions' , 'getPersonalOccasionCategoriesWithOccasions');
    
        Route::post('get-personal-occasion-settings-by-category' , 'getPersonalOccasionSettingsByCategory');

        Route::post('get-user-places' , 'getUserPlaces');

        Route::post('create-user-place' , 'createUserPlace');

        Route::post('get-user-place-by-id' , 'getUserPlaceById');

        Route::post('update-user-place' , 'updateUserPlace');

        Route::post('search-user-places' , 'searchUserPlaces');

      });
    });

    //=================================== User Stories =============================

    Route::group(['prefix' => 'stories', 'middleware' => ['user.auth']], function () {
        
        Route::group(['controller' => StoryController::class], function () {

            // Story creation and management
            Route::post('/create-story', 'createStory');

            Route::post('/get-friends' , 'getFriends');

            Route::post('/search-friends' , 'searchFriends');

            Route::post('/get-user-places' , 'getUserPlaces');

            Route::post('/search-user-places' , 'searchUserPlaces');

            Route::post('/get-user-place-by-id' , 'getUserPlaceById');

            Route::post('/create-user-place' , 'createUserPlace');

            Route::post('/update-user-place' , 'updateUserPlace');

            Route::post('/get-feeling' , 'getFeeling');

            Route::post('/search-feeling' , 'searchFeeling');

            Route::get('/get-stories', 'getStories');

            Route::get('/get-story-by-id', 'getStoryById');

            Route::delete('/delete-story', 'deleteStory');
            
            // Story interactions
            Route::get('/get-story-viewers', 'getStoryViewers');

            Route::post('/reply-to-story', 'replyToStory');

            Route::get('/get-story-replies', 'getStoryReplies');
            
            // Story polls
            Route::post('/vote-story-poll', 'voteStoryPoll');

            Route::get('/get-story-poll-results', 'getStoryPollResults');
            
            // Story audio
            Route::get('/audio/available', 'getStoryAudio');
            
            // Story privacy and settings
            Route::post('/mute-story-notifications', 'muteStoryNotifications');

            Route::post('/hide-story', 'hideStory');

            Route::post('/unhide-story', 'unhideStory');

        });
    });

    //=================================== User Friends =============================

    Route::group(['prefix' => 'friends/friend-requests' , 'middleware' => ['user.auth']], function () {
        
      Route::group(['controller' => FriendController::class], function () {

        Route::get('/for-post-privacy' , 'getFriendsForPostPrivacy');
        
        Route::post('/send-request' , 'sendFriendRequest');
        
        Route::get('/pending-requests' , 'getPendingRequests');
        
        Route::post('/accept/{friendshipId}' , 'acceptFriendRequest');
        
        Route::post('/decline/{friendshipId}' , 'declineFriendRequest');
    
      });
    });

    //=================================== User Sessions =============================

    Route::group(['prefix' => 'sessions' , 'middleware' => ['user.auth']], function () {
        
      Route::group(['controller' => SessionController::class], function () {

        Route::get('/' , 'index'); // Get all active sessions
        
        Route::get('/stats' , 'stats'); // Get session statistics
        
        Route::get('/current' , 'current'); // Get current session details
        
        Route::post('/heartbeat' , 'heartbeat'); // Update session activity
        
        Route::delete('/{sessionId}' , 'terminate'); // Terminate specific session
        
        Route::post('/terminate-others' , 'terminateOthers'); // Terminate all other sessions
        
        Route::post('/terminate-all' , 'terminateAll'); // Logout from all devices
        
        Route::post('/cleanup' , 'cleanup'); // Clean up expired sessions (admin)
    
      });
    });

});

