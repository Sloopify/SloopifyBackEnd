<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    
    <!-- Open Graph Meta Tags for Social Media -->
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image_url }}">
    <meta property="og:url" content="{{ $share_url }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Sloopify">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image_url }}">
    
    <!-- Additional Meta Tags -->
    <meta name="description" content="{{ $description }}">
    <meta name="keywords" content="story, social media, sloopify">
    <meta name="author" content="{{ $user_name }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }
        
        .story-image {
            width: 100%;
            height: 250px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        
        .story-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
        }
        
        .user-info {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            color: white;
            z-index: 2;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            border: 2px solid white;
        }
        
        .user-details h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .user-details p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .story-content {
            padding: 30px 25px;
        }
        
        .story-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .story-description {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .app-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .app-button {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .open-app-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .open-app-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .download-app-btn {
            background: #f8f9fa;
            color: #1a1a1a;
            border: 2px solid #e9ecef;
        }
        
        .download-app-btn:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .app-icon {
            width: 20px;
            height: 20px;
        }
        
        .footer {
            text-align: center;
            padding: 20px 25px;
            border-top: 1px solid #f0f0f0;
            background: #fafafa;
        }
        
        .footer p {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .app-logo {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }
        
        .expired-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 59, 48, 0.9);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 2;
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .story-content {
                padding: 25px 20px;
            }
            
            .story-title {
                font-size: 20px;
            }
            
            .app-buttons {
                flex-direction: column;
            }
        }
        
        /* Loading animation */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($story->is_expired)
            <div class="expired-badge">EXPIRED</div>
        @endif
        
        <div class="story-image" style="background-image: url('{{ $image_url }}')">
            <div class="user-info">
                <img src="{{ $user_image }}" alt="{{ $user_name }}" class="user-avatar" onerror="this.src='{{ asset('images/default-avatar.png') }}'">
                <div class="user-details">
                    <h3>{{ $user_name }}</h3>
                    <p>{{ $created_at }}</p>
                </div>
            </div>
        </div>
        
        <div class="story-content">
            <h1 class="story-title">{{ $title }}</h1>
            <p class="story-description">{{ $description }}</p>
            
            <div class="app-buttons">
                <a href="{{ $deep_link }}" class="app-button open-app-btn" id="openAppBtn">
                    <svg class="app-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                    Open in App
                </a>
                <a href="{{ $play_store_url }}" class="app-button download-app-btn">
                    <svg class="app-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Download App
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>Experience more stories like this</p>
            <div class="app-logo">Sloopify</div>
        </div>
    </div>
    
    <script>
        // Handle deep link fallback
        document.getElementById('openAppBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const deepLink = this.href;
            const fallbackUrl = '{{ $play_store_url }}';
            
            // Try to open the app
            window.location.href = deepLink;
            
            // Fallback to app store after a delay if app doesn't open
            setTimeout(function() {
                if (document.hidden || document.webkitHidden) {
                    return; // App opened successfully
                }
                window.location.href = fallbackUrl;
            }, 2000);
        });
        
        // Add loading state for images
        document.addEventListener('DOMContentLoaded', function() {
            const storyImage = document.querySelector('.story-image');
            const userAvatar = document.querySelector('.user-avatar');
            
            // Handle image loading errors
            if (storyImage) {
                storyImage.addEventListener('error', function() {
                    this.style.backgroundImage = 'url("{{ asset('images/default-story.jpg') }}")';
                });
            }
            
            if (userAvatar) {
                userAvatar.addEventListener('error', function() {
                    this.src = '{{ asset('images/default-avatar.png') }}';
                });
            }
        });
        
        // Detect if user is on mobile and show appropriate button
        if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            // User is on mobile - show "Open in App" more prominently
            document.querySelector('.open-app-btn').style.flex = '2';
        }
    </script>
</body>
</html>
