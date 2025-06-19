# Story System Documentation

## Overview

The Story System is a comprehensive Instagram/Facebook-like story feature that allows users to create rich, interactive stories with various elements including text, media, polls, location, mentions, and more. Each story expires after 24 hours automatically.

## Database Tables

### Core Tables
- `stories` - Main story table with content and privacy settings
- `story_media` - Story media files (images/videos) with positioning
- `story_views` - Track who viewed each story
- `story_replies` - User replies to stories
- `story_poll_votes` - Votes on story polls
- `story_notification_settings` - Notification preferences
- `story_hide_settings` - Story hiding preferences
- `story_audio` - Available audio files for stories

## Key Features

### 1. Rich Content Support
- **Text with styling**: color, font type, bold, italic, underline, alignment
- **Media files**: Multiple images/videos with positioning (x, y coordinates)
- **Background colors**: Array of gradient colors
- **GIF URLs**: External GIF support
- **Video muting**: Option to mute video sound

### 2. Interactive Elements (All with x, y positioning)
- **Location**: User places with 4 customizable features
- **Mentions**: Friend mentions with positioning
- **Clock**: Time display with 4 different styles
- **Feelings**: Emotion indicators with 4 features
- **Temperature**: Weather display with 4 features
- **Audio**: Background audio with 4 different shapes
- **Polls**: 2-5 options with positioning

### 3. Privacy Controls
- **Public**: Visible to everyone
- **Friends**: Visible to friends only
- **Specific Friends**: Visible to selected friends only
- **Friend Except**: Visible to all friends except selected ones
- Note: No "Only Me" option (as requested)

### 4. Social Features
- **Story Views**: Track and display viewers
- **Story Replies**: Text, media, or emoji responses
- **Poll Voting**: Interactive poll participation
- **Notification Control**: Mute specific users or types
- **Story Hiding**: Hide stories permanently, for 30 days, or specific stories

## API Endpoints

### Story Management

#### Create Story
```http
POST /api/v1/stories/create
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "content": "Story text content",
  "text_properties": {
    "color": "#FF0000",
    "font_type": "Arial",
    "bold": true,
    "italic": false,
    "underline": false,
    "alignment": "center"
  },
  "background_color": ["#FF0000", "#00FF00", "#0000FF"],
  "privacy": "friends",
  "specific_friends": [1, 2, 3],
  "friend_except": [4, 5],
  "media": [
    {
      "file": "image.jpg",
      "x_position": 50.5,
      "y_position": 30.2,
      "display_order": 1
    }
  ],
  "location_element": {
    "id": 1,
    "x": 10.5,
    "y": 20.3,
    "features": ["style1", "style2"]
  },
  "mentions_elements": [
    {
      "friend_id": 123,
      "x": 40.5,
      "y": 60.2
    }
  ],
  "clock_element": {
    "x": 80.1,
    "y": 10.5,
    "features": ["digital", "modern"]
  },
  "feeling_element": {
    "feeling_id": 5,
    "x": 70.5,
    "y": 80.3,
    "features": ["animated", "glow"]
  },
  "temperature_element": {
    "x": 90.1,
    "y": 90.5,
    "value": 25.5,
    "features": ["celsius", "animated"]
  },
  "audio_element": {
    "audio_id": 10,
    "x": 15.5,
    "y": 85.3,
    "features": ["wave", "pulsing"]
  },
  "poll_element": {
    "x": 50.0,
    "y": 70.0,
    "question": "Which do you prefer?",
    "options": ["Option A", "Option B", "Option C"],
    "features": ["modern", "rounded"]
  },
  "gif_url": "https://example.com/gif.gif",
  "is_video_muted": false
}
```

#### Get Stories
```http
GET /api/v1/stories?page=1&per_page=20
Authorization: Bearer {token}
```

#### View Story
```http
GET /api/v1/stories/{storyId}
Authorization: Bearer {token}
```

#### Delete Story
```http
DELETE /api/v1/stories/{storyId}
Authorization: Bearer {token}
```

### Story Interactions

#### Get Story Viewers
```http
GET /api/v1/stories/{storyId}/viewers
Authorization: Bearer {token}
```

#### Reply to Story
```http
POST /api/v1/stories/{storyId}/reply
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "reply_type": "text",
  "reply_text": "Great story!",
  "reply_media": "file.jpg",
  "emoji": "😍"
}
```

#### Get Story Replies
```http
GET /api/v1/stories/{storyId}/replies
Authorization: Bearer {token}
```

### Story Polls

#### Vote on Poll
```http
POST /api/v1/stories/{storyId}/poll/vote
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "selected_options": [0, 2]
}
```

#### Get Poll Results
```http
GET /api/v1/stories/{storyId}/poll/results
Authorization: Bearer {token}
```

### Story Settings

#### Mute Story Notifications
```http
POST /api/v1/stories/notifications/mute
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "muted_user_id": 123,
  "mute_replies": true,
  "mute_poll_votes": false,
  "mute_all": false
}
```

#### Hide Story
```http
POST /api/v1/stories/hide
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "story_owner_id": 123,
  "specific_story_id": 456,
  "hide_type": "30_days"
}
```

#### Unhide Story
```http
POST /api/v1/stories/unhide
Authorization: Bearer {token}
```

### Audio Management

#### Get Available Audio
```http
GET /api/v1/stories/audio/available
Authorization: Bearer {token}
```

## Response Examples

### Story Object
```json
{
  "id": 1,
  "user": {
    "id": 123,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "image": "https://example.com/avatar.jpg"
  },
  "content": "My amazing story!",
  "text_properties": {
    "color": "#FF0000",
    "font_type": "Arial",
    "bold": true,
    "alignment": "center"
  },
  "background_color": ["#FF0000", "#00FF00"],
  "privacy": "friends",
  "gif_url": null,
  "is_video_muted": false,
  "location_element": {
    "id": 1,
    "x": 10.5,
    "y": 20.3,
    "features": ["style1", "style2"]
  },
  "mentions_elements": [
    {
      "friend_id": 456,
      "x": 40.5,
      "y": 60.2
    }
  ],
  "media": [
    {
      "id": 1,
      "type": "image",
      "url": "https://example.com/story_media.jpg",
      "x_position": 50.5,
      "y_position": 30.2,
      "display_order": 1,
      "metadata": {
        "width": 1920,
        "height": 1080
      }
    }
  ],
  "views_count": 25,
  "replies_count": 5,
  "has_viewed": false,
  "has_voted": false,
  "poll_results": {
    "options": [
      {
        "option": "Option A",
        "votes": 10,
        "percentage": 66.7
      },
      {
        "option": "Option B",
        "votes": 5,
        "percentage": 33.3
      }
    ],
    "total_votes": 15
  },
  "expires_at": "2025-01-16T12:00:00Z",
  "is_expired": false,
  "created_at": "2025-01-15T12:00:00Z"
}
```

## Usage Examples

### Creating a Text Story with Background
```javascript
const formData = new FormData();
formData.append('content', 'Hello World!');
formData.append('text_properties[color]', '#FFFFFF');
formData.append('text_properties[font_type]', 'Arial');
formData.append('text_properties[bold]', 'true');
formData.append('text_properties[alignment]', 'center');
formData.append('background_color[0]', '#FF6B6B');
formData.append('background_color[1]', '#4ECDC4');
formData.append('privacy', 'friends');

fetch('/api/v1/stories/create', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
});
```

### Creating a Media Story with Poll
```javascript
const formData = new FormData();
formData.append('media[0][file]', imageFile);
formData.append('media[0][x_position]', '50');
formData.append('media[0][y_position]', '30');
formData.append('poll_element[x]', '50');
formData.append('poll_element[y]', '70');
formData.append('poll_element[question]', 'What do you think?');
formData.append('poll_element[options][0]', 'Amazing!');
formData.append('poll_element[options][1]', 'Good');
formData.append('poll_element[options][2]', 'Okay');
formData.append('privacy', 'public');

fetch('/api/v1/stories/create', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
});
```

### Voting on a Story Poll
```javascript
fetch('/api/v1/stories/123/poll/vote', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    selected_options: [0] // Vote for first option
  })
});
```

### Hiding Stories from a User
```javascript
fetch('/api/v1/stories/hide', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    story_owner_id: 123,
    hide_type: '30_days'
  })
});
```

## Privacy Logic

The story system implements the same privacy logic as posts:

1. **Public**: Visible to all users
2. **Friends**: Only visible to accepted friends
3. **Specific Friends**: Only visible to users in the specific_friends array
4. **Friend Except**: Visible to all friends except those in friend_except array

Additionally, stories are filtered by hide settings:
- Users won't see stories from permanently hidden users
- Users won't see stories from users hidden for 30 days (until expiry)
- Users won't see specific hidden stories

## Automatic Expiry

- Stories automatically expire 24 hours after creation
- Expired stories are not shown in the feed
- The `expires_at` field tracks expiry time
- The `is_expired` computed attribute indicates if a story has expired

## Positioning System

All interactive elements use a coordinate system:
- **X**: 0-100 (percentage from left edge)
- **Y**: 0-100 (percentage from top edge)
- Allows drag-and-drop placement on the story canvas
- Each element can be positioned independently

## Features Array

Most elements support a "features" array with up to 4 different customization options:
- **Location**: Different display styles, animations, etc.
- **Clock**: Digital/analog, themes, animations
- **Feelings**: Animation styles, effects, sizes
- **Temperature**: Unit display, styling, animations
- **Audio**: Waveform shapes, visualizations

This system provides a complete Instagram/Facebook-like story experience with rich customization options and social interaction features. 