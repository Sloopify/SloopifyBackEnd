# 🔒 Friend Selection System for Post Privacy

This document describes the implementation of the friend selection system that allows users to choose specific friends for `specific_friends` and `friend_except` privacy options when creating posts.

## 📋 Table of Contents

- [System Overview](#system-overview)
- [Database Structure](#database-structure)
- [API Endpoints](#api-endpoints)
- [Frontend Implementation](#frontend-implementation)
- [Usage Examples](#usage-examples)
- [Installation & Setup](#installation--setup)

## 🎯 System Overview

The friend selection system provides two main privacy options:

1. **Specific Friends** (`specific_friends`): Only selected friends can see the post
2. **Friends Except** (`friend_except`): All friends can see the post except the selected ones

### Features

- ✅ Search friends by name
- ✅ Pagination support
- ✅ Real-time friend selection
- ✅ Profile image display
- ✅ Online status indicators
- ✅ Friend request management
- ✅ Bidirectional friendship support

## 🗄️ Database Structure

### Friendships Table

```sql
CREATE TABLE friendships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    friend_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'blocked', 'declined') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_friendship (user_id, friend_id),
    KEY idx_user_status (user_id, status),
    KEY idx_friend_status (friend_id, status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Posts Table (Updated)

```sql
-- Added friend_except field and updated privacy enum
ALTER TABLE posts 
ADD COLUMN friend_except JSON NULL AFTER specific_friends,
MODIFY COLUMN privacy ENUM('public', 'friends', 'specific_friends', 'friend_except', 'only_me');
```

## 🚀 API Endpoints

### 1. Get Friends for Post Privacy

**Endpoint:** `GET /api/v1/friends/for-post-privacy`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Parameters:**
- `search` (optional): Search friends by name
- `page` (optional): Page number for pagination
- `per_page` (optional): Number of friends per page (max 100)

**Response:**
```json
{
    "status_code": 200,
    "success": true,
    "message": "Friends retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "first_name": "John",
            "last_name": "Doe",
            "profile_image": "https://example.com/storage/profile1.jpg",
            "is_online": false
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 10,
        "last_page": 1,
        "from": 1,
        "to": 10,
        "has_more_pages": false
    }
}
```

### 2. Send Friend Request

**Endpoint:** `POST /api/v1/friends/send-request`

**Body:**
```json
{
    "friend_id": 2
}
```

### 3. Get Pending Friend Requests

**Endpoint:** `GET /api/v1/friends/pending-requests`

### 4. Accept Friend Request

**Endpoint:** `POST /api/v1/friends/accept/{friendshipId}`

### 5. Decline Friend Request

**Endpoint:** `POST /api/v1/friends/decline/{friendshipId}`

## 🎨 Frontend Implementation

### Friend Selection Table

```html
<table class="friends-table">
    <thead>
        <tr>
            <th>Select</th>
            <th>Friend</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="checkbox-cell">
                <input type="checkbox" class="friend-checkbox" data-friend-id="1">
            </td>
            <td>
                <div class="friend-info">
                    <img src="profile.jpg" alt="Profile" class="profile-img">
                    <div class="friend-name">John Doe</div>
                </div>
            </td>
            <td>
                <span class="online-status"></span>
                Online
            </td>
        </tr>
    </tbody>
</table>
```

### JavaScript Implementation

```javascript
// Get selected friend IDs
function getSelectedFriends() {
    return Array.from(document.querySelectorAll('.friend-checkbox:checked'))
        .map(cb => parseInt(cb.dataset.friendId));
}

// Apply selection to post privacy
function applyFriendSelection(privacyType) {
    const selectedIds = getSelectedFriends();
    
    if (privacyType === 'specific_friends') {
        // Set specific_friends array
        postData.specific_friends = selectedIds;
    } else if (privacyType === 'friend_except') {
        // Set friend_except array
        postData.friend_except = selectedIds;
    }
}
```

## 📝 Usage Examples

### Creating a Post with Specific Friends

```javascript
// POST /api/v1/post/create-post
{
    "type": "regular",
    "content": "This is a private post for specific friends",
    "privacy": "specific_friends",
    "specific_friends": [1, 3, 5]
}
```

### Creating a Post with Friends Except

```javascript
// POST /api/v1/post/create-post
{
    "type": "regular",
    "content": "This post is for all friends except some",
    "privacy": "friend_except",
    "friend_except": [2, 4]
}
```

### Fetching Friends with Search

```javascript
fetch('/api/v1/friends/for-post-privacy?search=john&page=1&per_page=20', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    // Populate friend selection table
    populateFriendsTable(data.data);
});
```

## ⚙️ Installation & Setup

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Seed Sample Data

```bash
php artisan db:seed --class=FriendshipSeeder
```

### 3. Test the API

Access the demo page: `http://your-domain.com/friend-selection-demo.html`

### 4. Frontend Integration

Include the friend selection component in your post creation form:

```html
<!-- Privacy Selection -->
<select id="privacyType" onchange="handlePrivacyChange()">
    <option value="public">Public</option>
    <option value="friends">Friends</option>
    <option value="specific_friends">Specific Friends</option>
    <option value="friend_except">Friends Except</option>
    <option value="only_me">Only Me</option>
</select>

<!-- Friend Selection Modal/Component -->
<div id="friendSelection" style="display: none;">
    <!-- Include the friend selection table here -->
</div>
```

## 🔧 Model Relationships

### User Model

```php
// Get friends where user is initiator
public function sentFriendRequests()
{
    return $this->hasMany(Friendship::class, 'user_id');
}

// Get friends where user is recipient
public function receivedFriendRequests()
{
    return $this->hasMany(Friendship::class, 'friend_id');
}

// Check if users are friends
public function isFriendsWith($userId)
{
    return $this->sentFriendRequests()
        ->where('friend_id', $userId)
        ->where('status', 'accepted')
        ->exists() ||
        $this->receivedFriendRequests()
        ->where('user_id', $userId)
        ->where('status', 'accepted')
        ->exists();
}
```

### Post Model

```php
// Scope for post visibility
public function scopeVisibleTo($query, $userId)
{
    return $query->where(function ($q) use ($userId) {
        $q->where('privacy', 'public')
          ->orWhere(function ($subQ) use ($userId) {
              // Friends privacy logic
          })
          ->orWhere(function ($subQ) use ($userId) {
              // Specific friends logic
              $subQ->where('privacy', 'specific_friends')
                   ->whereJsonContains('specific_friends', $userId);
          })
          ->orWhere(function ($subQ) use ($userId) {
              // Friend except logic
              $subQ->where('privacy', 'friend_except')
                   ->whereHas('user', function ($userQ) use ($userId) {
                       // User must be friend
                   })
                   ->whereJsonDoesntContain('friend_except', $userId);
          })
          ->orWhere('user_id', $userId);
    });
}
```

## 🎯 Key Features

1. **Bidirectional Friendships**: Supports both directions of friendship
2. **Search Functionality**: Real-time search through friends list
3. **Pagination**: Efficient handling of large friend lists
4. **Visual Feedback**: Clear indication of selected friends
5. **Responsive Design**: Works on desktop and mobile devices
6. **API Consistency**: Follows the existing API patterns

## 🔒 Security Considerations

- ✅ Authentication required for all endpoints
- ✅ Users can only access their own friends
- ✅ Friend IDs are validated against actual friendships
- ✅ SQL injection protection through Eloquent ORM
- ✅ Input validation and sanitization

## 📱 Demo

Visit `http://your-domain.com/friend-selection-demo.html` to see the interactive demo of the friend selection system.

The demo includes:
- Interactive friend selection tables
- Search functionality
- Real-time counters
- Visual feedback
- API endpoint information 