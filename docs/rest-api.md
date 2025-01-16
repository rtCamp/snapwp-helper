# REST API Endpoints

This document provides documentation for the REST API endpoints available in the SnapWP Helper plugin.

| Endpoint | Description | Parameters | Response |
| -------- | ----------- | ---------- | -------- |
| [`GET /wp-json/snapwp/v1/env`](#get-wp-jsonsnapwpv1env) | Generates environment variables for the SnapWP Helper plugin | None | `content => string` |

## `GET /wp-json/snapwp/v1/env`

Generates environment variables for the SnapWP Helper plugin. This endpoint creates a .env file content based on the provided variables, only including supported variables and validating required ones.

### Example Usage

```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -u "your_username:your_application_password" \
  https://your-wordpress-site.com/wp-json/snapwp/v1/env
```

### Parameters

This endpoint does not require any parameters to be passed in the request body. The .env file content is generated based on WordPress settings. Unchanged variables will be commented out.

  - `NODE_TLS_REJECT_UNAUTHORIZED`: Enable if connecting to a self-signed cert. (Default: `0`)
  - `NEXT_PUBLIC_URL` (Required): The headless frontend domain URL. (Default: `http://localhost:3000`)
  - `NEXT_PUBLIC_WORDPRESS_URL` (Required): The WordPress "frontend" domain URL.
  - `NEXT_PUBLIC_GRAPHQL_ENDPOINT`: The WordPress GraphQL endpoint. (Default: `graphql`)
  - `NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH`: The WordPress Uploads directory path. (Default: `wp-content/uploads`)
  - `NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX`: The WordPress REST URL Prefix. (Default: `wp-json`)

Note: This endpoint requires authentication with administrator privileges.

### Response

1. 200 OK

    - **Content**: JSON object containing the generated .env file content.
    
    **Example**:
    
    ```json
    {
        "content": "\n# The headless frontend domain URL\nNEXT_URL=http://localhost:3000\\n\n# The WordPress \"frontend\" domain URL\nHOME_URL=https://headless-demo.local\\n\n# The WordPress GraphQL endpoint\nGRAPHQL_ENDPOINT=graphql\\n"
    }
    ```

    Note: The content includes newline characters (`\n`) and escaped backslashes (`\\n`) at the end of each line.

2. 500 Internal Server Error

    - **Content**: JSON object containing an error message if the .env generation fails.
    
    **Example**:
    
    ```json
    {
        "code": "env_generation_failed",
        "message": "Error message describing the issue",
        "data": {
            "status": 500
        }
    }
    ```
