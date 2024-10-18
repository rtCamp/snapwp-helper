# Deployment API

This document provides documentation for the Deployment API endpoints available in the RT Decoupler plugin.

## Table of Contents

- [Trigger Deployment](#trigger-deployment)
- [Get Deployment Status](#get-deployment-status)
- [Update Deployment Status](#update-deployment-status)

## Trigger Deployment

Triggers the deployment process for the headless frontend.

### Endpoint

```
POST /wp-json/rt-decoupler-api/v1/deploy/trigger
```

### Parameters

| Name | Type | Description |
|------|------|-------------|
| `nonce` | string | A WordPress nonce for security verification |

### Example Usage

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_WORDPRESS_NONCE" \
  -d '{"nonce": "YOUR_DEPLOYMENT_NONCE"}' \
  https://your-wordpress-site.com/wp-json/rt-decoupler-api/v1/deploy/trigger
```

### Response

#### Success (200 OK)

```json
{
  "message": "Deployment triggered successfully",
  "response": {
    // Deployment server response
  },
  "env": "NEXT_URL=https://example.com\nHOME_URL=https://your-wordpress-site.com\n..."
}
```

#### Error (400 Bad Request)

```json
{
  "code": "missing_params",
  "message": "Required parameters are missing or invalid",
  "data": {
    "status": 400
  }
}
```

#### Error (403 Forbidden)

```json
{
  "code": "invalid_nonce",
  "message": "Nonce verification failed",
  "data": {
    "status": 403
  }
}
```

## Get Deployment Status

Retrieves the current status of the deployment process.

### Endpoint

```
GET /wp-json/rt-decoupler-api/v1/deploy/status
```

### Example Usage

```bash
curl -X GET \
  -H "X-WP-Nonce: YOUR_WORDPRESS_NONCE" \
  https://your-wordpress-site.com/wp-json/rt-decoupler-api/v1/deploy/status
```

### Response

#### Success (200 OK)

```json
{
  "status": "complete",
  "message": "Deployment Completed",
  "frontend_url": "https://your-frontend-url.com"
}
```

#### Error (404 Not Found)

```json
{
  "code": "no_status",
  "message": "No deployment status found",
  "data": {
    "status": 404
  }
}
```

## Update Deployment Status

Updates the status of the ongoing deployment process. This endpoint is typically called by the deployment server.

### Endpoint

```
POST /wp-json/rt-decoupler-api/v1/deploy/status
```

### Parameters

| Name | Type | Description |
|------|------|-------------|
| `status` | string | The current status of the deployment |
| `errorCode` | string | (Optional) Error code if the deployment failed |
| `errorMessage` | string | (Optional) Error message if the deployment failed |
| `new_url` | string | (Optional) The URL of the newly deployed frontend |

### Headers

| Name | Description |
|------|-------------|
| `X-Nonce` | The nonce provided during deployment triggering |

### Example Usage

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-Nonce: YOUR_DEPLOYMENT_NONCE" \
  -d '{
    "status": "complete",
    "new_url": "https://your-new-frontend-url.com"
  }' \
  https://your-wordpress-site.com/wp-json/rt-decoupler-api/v1/deploy/status
```

### Response

#### Success (200 OK)

```json
{
  "success": true,
  "message": "Deployment status updated"
}
```

#### Error (400 Bad Request)

```json
{
  "code": "invalid_status",
  "message": "Invalid deployment status",
  "data": {
    "status": 400
  }
}
```

#### Error (403 Forbidden)

```json
{
  "code": "invalid_nonce",
  "message": "Invalid nonce",
  "data": {
    "status": 403
  }
}
```

## Notes

- All endpoints require authentication with administrator privileges.
- The deployment process involves communication between the WordPress site and a deployment server. The exact implementation of the deployment server is not covered in this documentation.
- The `status` parameter in the Update Deployment Status endpoint can be one of the following: `queued`, `setting-up`, `configuring-files`, `deploying`, `complete`, `error`, or `ignored`.
- Error handling and security measures should be implemented on both the WordPress side and the deployment server side to ensure safe and reliable deployments.
