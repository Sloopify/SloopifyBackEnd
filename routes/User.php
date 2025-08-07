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

        Route::post('pin-post' , 'pinPost');

        Route::post('update-post' , 'updatePost');

        Route::post('mute-post-notifications' , 'mutePostNotifications');

        Route::post('get-post-notification-settings' , 'getPostNotificationSettings');

        Route::post('save-post' , 'savePost');

        Route::post('get-saved-posts' , 'getSavedPosts');

        Route::post('check-post-saved-status' , 'checkPostSavedStatus');

        Route::post('toggle-post-comments' , 'togglePostComments');

        Route::post('get-post-comments-status' , 'getPostCommentsStatus');

        Route::post('delete-post' , 'deletePost');

        Route::post('post-interest' , 'postInterest');

        Route::post('toggle-post-notifications' , 'togglePostNotifications');

        Route::post('get-post-notifications-status' , 'getPostNotificationsStatus');

        Route::post('hide-specific-friend-post' , 'hideSpecificFriendPost');

        Route::post('get-hidden-posts' , 'getHiddenPosts');

        Route::post('hide-friend-posts' , 'hideFriendPosts');

        Route::post('unhide-friend-posts' , 'unhideFriendPosts');

        Route::post('get-hidden-friend-posts' , 'getHiddenFriendPosts');

        Route::post('restrict-friend-notifications' , 'restrictFriendNotifications');

        Route::post('get-friend-notification-restrictions' , 'getFriendNotificationRestrictions');

        Route::post('suggested-post-interest' , 'suggestedPostInterest');

        Route::post('hide-suggested-user-post' , 'hideSuggestedPost');

        Route::post('hide-suggested-user-posts' , 'hideSuggestedUserPosts');

        Route::post('unhide-suggested-user-post' , 'unhideSuggestedUserPosts');

        Route::post('get-hidden-suggested-posts' , 'getHiddenSuggestedPosts');

        Route::post('create-comment' , 'createComment');

        Route::post('reply-to-comment' , 'replyToComment');

        Route::post('get-post-comments' , 'getPostComments');

        Route::post('delete-comment' , 'deleteComment');

      });
    });

    //=================================== User Stories =============================

    Route::group(['prefix' => 'stories', 'middleware' => ['user.auth']], function () {
        
        Route::group(['controller' => StoryController::class], function () {

            // My Story

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

            Route::post('/get-story-audio', 'getStoryAudio');

            Route::post('/search-story-audio', 'searchStoryAudio');

            Route::post('/get-story-viewers', 'getStoryViewers');

            Route::post('/search-story-viewers', 'searchStoryViewers');

            Route::delete('/delete-story', 'deleteStory');

            Route::post('/change-story-muted-notification', 'changeStoryMutedNotification');

            Route::post('/get-story-poll-results', 'getStoryPollResults');

            Route::post('/search-story-poll-results', 'searchStoryPollResults');

            Route::post('/get-story-by-id', 'getStoryById');

            Route::post('/get-my-stories', 'getMyStories');
            
            Route::post('/get-stories', 'getStories');

            Route::post('/get-stories-by-user-id', 'getStoriesByUserId');

            Route::post('/view-story', 'viewStory');

            Route::post('/view-my-story-by-id', 'viewMyStoryById');

            Route::post('/mute-story-notifications', 'muteStoryNotifications');

            Route::post('/vote-story-poll', 'voteStoryPoll');

            Route::post('/reply-to-story', 'replyToStory');
            
            Route::post('/hide-story', 'hideStory');

            Route::post('/unhide-story', 'unhideStory');

        });
    });

    //=================================== User Friends =============================

    Route::group(['prefix' => 'friends' , 'middleware' => ['user.auth']], function () {
        
      Route::group(['controller' => FriendController::class], function () {

        Route::post('/get-friends' , 'getFriends');

        Route::post('/search-friends' , 'searchFriends');

        Route::post('/delete-friend-ship' , 'deleteFriendShip');

        Route::post('/block-friend' , 'blockFriend');

        Route::post('/get-sent-requests' , 'getSentRequests');

        Route::post('/search-sent-requests' , 'searchSentRequests');

        Route::post('/cancel-friend-request' , 'cancelFriendRequest');

        Route::post('/get-received-requests' , 'getReceivedRequests');

        Route::post('/search-received-requests' , 'searchReceivedRequests');

        Route::post('/accept-friend-request' , 'acceptFriendRequest');

        Route::post('/decline-friend-request' , 'declineFriendRequest');

        Route::post('/send-request' , 'sendFriendRequest');
        
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

