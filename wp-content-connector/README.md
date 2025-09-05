# WP Content Connector

A lightweight WordPress plugin to accept incoming content via a secure API and store it as **draft posts**.

---

## 🚀 Installation

1. Download `wp-content-connector.zip`.
2. In WordPress admin → Plugins → Add New → Upload Plugin → select the ZIP.
3. Activate the plugin.
4. Go to **Settings → Connector** to configure:
   - API Key
   - Active/Inactive toggle

---

## 🔑 Authentication

- Each request must include the correct API key.
- Two options:
  1. As a Bearer token header:  
     `Authorization: Bearer YOUR_API_KEY`
  2. Inside JSON payload as `api_key`.

---

## 📡 Endpoints

### 1. Ping (test connection)
```
GET /wp-json/connector/v1/ping
```

Response:
```json
{
  "status": "success",
  "message": "Ping successful!"
}
```

### 2. Ingest Content
```
POST /wp-json/connector/v1/ingest
Headers: 
  Content-Type: application/json
  Authorization: Bearer YOUR_API_KEY
Body:
{
  "title": "Sample Post Title",
  "description": "This is a sample description",
  "tags": ["news", "update"],
  "category": "Announcements",
  "media_url": "https://example.com/media/sample.mp4"
}
```

Response (success):
```json
{
  "status": "success",
  "message": "Post created successfully.",
  "post_id": 123
}
```

Response (invalid key):
```json
{
  "status": "error",
  "message": "Invalid API key."
}
```

Response (inactive plugin):
```json
{
  "status": "inactive",
  "message": "Plugin is currently inactive."
}
```

---

## 🗂 Post Storage

- All posts are saved as **Drafts**.
- Tags and categories auto-created if missing.
- Media URL stored in custom field: `_connector_media_url`.

---

## 🧹 Uninstall

When the plugin is deleted, it cleans up its settings:
- Removes saved API Key
- Removes Active/Inactive toggle option

No posts or meta data are deleted.
