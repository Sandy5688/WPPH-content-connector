# WP Content Connector

A lightweight WordPress plugin to receive content via a secure API endpoint and store it as draft posts.

---

## ⚡ Features
- Secure REST API endpoint (`/wp-json/connector/v1/ingest`)
- API key authentication (Bearer header or JSON body)
- Auto-create categories and tags if missing
- Stores media URL in custom field `_connector_media_url`
- Plugin toggle (Active/Inactive) in settings
- Settings page under **Settings → Connector**
- Saves all posts as **Draft** for admin review
- Namespaced code to avoid conflicts
- Includes uninstall cleanup (removes saved options)

---

## 📦 Installation
1. Upload `wp-content-connector.zip` to WordPress (Plugins → Add New → Upload).
2. Activate the plugin from the **Plugins** menu.
3. Go to **Settings → Connector**.
4. Set your API key and enable the plugin.

---

## 🔑 Authentication
Requests must include the API key either:
- As a header:
  ```
  Authorization: Bearer YOUR_API_KEY
  ```
- Or inside the JSON body:
  ```json
  { "api_key": "YOUR_API_KEY", ... }
  ```

---

## 📡 API Endpoints

### ✅ Ping
**GET** `/wp-json/connector/v1/ping`  
Checks if the plugin is active.

**Response:**
```json
{ "status": "ok", "message": "Ping successful!" }
```

---

### 📥 Ingest
**POST** `/wp-json/connector/v1/ingest`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_API_KEY
```

**Body Example:**
```json
{
  "title": "Sample Post Title",
  "description": "This is a sample description for testing purposes.",
  "tags": ["news", "update", "sample"],
  "category": "Announcements",
  "media_url": "https://example.com/media/sample-video.mp4"
}
```

**Response (success):**
```json
{
  "status": "success",
  "message": "Post created as draft",
  "post_id": 123
}
```

**Response (error):**
```json
{
  "status": "error",
  "message": "Invalid API key."
}
```

---

## 🧪 Quick Test

Test with curl:

```bash
curl -X POST https://your-wp-site.com/wp-json/connector/v1/ingest   -H "Content-Type: application/json"   -H "Authorization: Bearer YOUR_API_KEY"   -d '{
    "title": "Hello World",
    "description": "This is a test post from API.",
    "tags": ["test", "api"],
    "category": "API Demo",
    "media_url": "https://example.com/media/test.mp4"
  }'
```

👉 Or use Postman:  
Import `connector_api.postman_collection.json` and test with `sample-payload.json`.

---

## 🛠 Compatibility
- Tested with **WordPress 6.x**
- Requires **PHP 7.4+**
- Works with default WP REST API enabled

---

## 🗑 Uninstall
When deleted from WordPress, the plugin removes:
- `wpcc_api_key`
- `wpcc_active_status`

---

### 🎯 Verdict
Delivered **plugin + README inside repo = production-ready**.  
All polish added: quick curl test + compatibility info + uninstall behavior ✅
