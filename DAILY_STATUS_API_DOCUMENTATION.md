# Daily Status API Documentation

## Overview
The Daily Status feature allows users to set and manage their current emotional/mood status that expires after 24 hours. Users can choose from various predefined statuses and track their active status.

## Database Structure

### `daily_statuses` Table
- `id` - Primary key
- `name` - Status name (e.g., "Happy", "Sad", "Angry")
- `web_icon` - Emoji or icon for web interface
- `mobile_icon` - Icon filename for mobile interface
- `status` - Boolean (true = active, false = inactive)
- `created_at`, `updated_at` - Timestamps

### `users` Table (Modified)
- `daily_status_id` - Foreign key to daily_statuses table
- `daily_status_expires_at` - Timestamp when status expires

## API Endpoints

### 1. Get Daily Statuses
**Endpoint:** `POST /api/v1/user/home/get-daily-statuses`

**Description:** Retrieve all available daily statuses with pagination and filtering.

**Request Parameters:**
```json
{
    "page": 1,
    "per_page": 20,
    "sort_by": "name", // "name" or "created_at"
    "sort_order": "asc", // "asc" or "desc"
    "status": true // Optional: filter by active/inactive statuses
}
```

**Response:**
```json
{
    "status_code": 200,
    "success": true,
    "message": "Daily statuses retrieved successfully",
    "data": {
        "daily_statuses": [
            {
                "id": 1,
                "name": "Happy",
                "web_icon": "😊",
                "mobile_icon": "happy_icon.png",
                "status": true,
                "is_user_active_status": true,
                "expires_at": "2025-08-09T20:00:00.000000Z",
                "expires_at_human": "in 23 hours",
                "created_at": "2025-08-08T20:00:00.000000Z",
                "updated_at": "2025-08-08T20:00:00.000000Z"
            }
        ],
        "total_statuses": 20,
        "current_filter": true,
        "sorting": {
            "sort_by": "name",
            "sort_order": "asc"
        },
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 20,
            "total": 20,
            "from": 1,
            "to": 20,
            "has_more_pages": false
        }
    }
}
```

### 2. Search Daily Statuses
**Endpoint:** `POST /api/v1/user/home/search-daily-statuses`

**Description:** Search daily statuses by name.

**Request Parameters:**
```json
{
    "search": "happy",
    "page": 1,
    "per_page": 20,
    "sort_by": "name",
    "sort_order": "asc",
    "status": true
}
```

**Response:** Same structure as get daily statuses with search results.

### 3. Set Daily Status
**Endpoint:** `POST /api/v1/user/home/set-daily-status`

**Description:** Set user's current daily status (expires in 24 hours).

**Request Parameters:**
```json
{
    "daily_status_id": 1
}
```

**Response:**
```json
{
    "status_code": 200,
    "success": true,
    "message": "Daily status set successfully",
    "data": {
        "daily_status": {
            "id": 1,
            "name": "Happy",
            "web_icon": "😊",
            "mobile_icon": "happy_icon.png",
            "status": true
        },
        "expires_at": "2025-08-09T20:00:00.000000Z",
        "expires_at_human": "in 24 hours"
    }
}
```

### 4. Remove Daily Status
**Endpoint:** `POST /api/v1/user/home/remove-daily-status`

**Description:** Remove user's current daily status.

**Request Parameters:** None

**Response:**
```json
{
    "status_code": 200,
    "success": true,
    "message": "Daily status removed successfully"
}
```

### 5. Get Current Daily Status
**Endpoint:** `POST /api/v1/user/home/get-current-daily-status`

**Description:** Get user's current active daily status.

**Request Parameters:** None

**Response:**
```json
{
    "status_code": 200,
    "success": true,
    "message": "Current daily status retrieved successfully",
    "data": {
        "has_active_status": true,
        "daily_status": {
            "id": 1,
            "name": "Happy",
            "web_icon": "😊",
            "mobile_icon": "happy_icon.png",
            "status": true
        },
        "expires_at": "2025-08-09T20:00:00.000000Z",
        "expires_at_human": "in 23 hours"
    }
}
```

## Key Features

### ✅ Expiration System
- Statuses automatically expire after 24 hours
- Expired statuses are automatically removed when accessed
- Human-readable expiration times (e.g., "in 23 hours")

### ✅ Active Status Tracking
- `is_user_active_status` flag shows which status is currently active
- Only one status can be active per user at a time
- Status updates replace the previous active status

### ✅ Search & Filtering
- Search by status name
- Filter by active/inactive statuses
- Pagination support
- Sorting by name or creation date

### ✅ Validation & Security
- Only active statuses can be set
- Proper validation and error handling
- Transaction safety for data integrity
- Authentication required for all endpoints

## Sample Daily Statuses

The system comes with 20 predefined statuses:

1. **Happy** 😊
2. **Sad** 😢
3. **Angry** 😠
4. **Excited** 🤩
5. **Tired** 😴
6. **Energetic** ⚡
7. **Calm** 😌
8. **Stressed** 😰
9. **Confident** 😎
10. **Lonely** 🥺
11. **Grateful** 🙏
12. **Focused** 🎯
13. **Creative** 🎨
14. **Motivated** 💪
15. **Relaxed** 😌
16. **Anxious** 😟
17. **Optimistic** 😄
18. **Pessimistic** 😔
19. **Inspired** ✨
20. **Bored** 😐

## Error Responses

### Validation Error (422)
```json
{
    "status_code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "daily_status_id": ["The daily status id field is required."]
    }
}
```

### Not Found Error (404)
```json
{
    "status_code": 404,
    "success": false,
    "message": "Daily status not found"
}
```

### Server Error (500)
```json
{
    "status_code": 500,
    "success": false,
    "message": "Failed to set daily status",
    "error": "Error details"
}
```

## Usage Examples

### Set a Daily Status
```bash
curl -X POST /api/v1/user/home/set-daily-status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"daily_status_id": 1}'
```

### Get All Statuses
```bash
curl -X POST /api/v1/user/home/get-daily-statuses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"page": 1, "per_page": 10}'
```

### Search Statuses
```bash
curl -X POST /api/v1/user/home/search-daily-statuses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"search": "happy"}'
```

## Implementation Notes

- All endpoints require user authentication
- Statuses expire exactly 24 hours after being set
- The system automatically cleans up expired statuses
- Each user can only have one active status at a time
- Status updates replace the previous status immediately
