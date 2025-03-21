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

  - `INTROSPECTION_TOKEN` (Required): Token used for authenticating GraphQL introspection queries 
  - `NEXT_PUBLIC_CORS_PROXY_PREFIX`: The CORS proxy prefix to use when bypassing CORS restrictions from WordPress server, Possible values: string|false Default: /proxy. This means for script module next app will make request `NEXT_PUBLIC_FRONTEND_URL/proxy/{module-path}`. (Default: `/proxy`)
  - `NEXT_PUBLIC_FRONTEND_URL` (Required): The headless frontend domain URL. (Default: `http://localhost:3000`)
  - `NEXT_PUBLIC_GRAPHQL_ENDPOINT` (Required): The WordPress GraphQL endpoint. (Default: `graphql`)
  - `NEXT_PUBLIC_REST_URL_PREFIX`: The WordPress REST URL Prefix. (Default: `wp-json`)
  - `NEXT_PUBLIC_WP_HOME_URL` (Required): The WordPress "frontend" domain URL.
  - `NEXT_PUBLIC_WP_SITE_URL` : The WordPress "backend" domain URL.
  - `NEXT_PUBLIC_WP_UPLOADS_DIRECTORY`: The WordPress Uploads directory path. (Default: `wp-content/uploads`)
  - `NODE_TLS_REJECT_UNAUTHORIZED` (Required): Only enable if connecting to a self-signed cert. (Default: `0`)

Note: This endpoint requires authentication with administrator privileges.

### Response

1. 200 OK

    - **Content**: JSON object containing the generated .env file content.
    
    **Example**:
    
    ```json
    {
        "content":"\n# Token used for authenticating GraphQL introspection queries\nINTROSPECTION_TOKEN=Th15IS4te5tT0KEN\n\n# The CORS proxy prefix to use when bypassing CORS restrictions from WordPress server, Possible values: string|false Default: \/proxy, This means for script module next app will make request NEXT_PUBLIC_FRONTEND_URL\/proxy\/{module-path}\n# NEXT_PUBLIC_CORS_PROXY_PREFIX=\/proxy\n\n# The headless frontend domain URL. Make sure the value matches the URL used by your frontend app.\nNEXT_PUBLIC_FRONTEND_URL=http:\/\/localhost:3000\n\n# The WordPress GraphQL endpoint\nNEXT_PUBLIC_GRAPHQL_ENDPOINT=graphql\n\n# The WordPress REST URL Prefix\n# NEXT_PUBLIC_REST_URL_PREFIX=\/wp-json\n\n# The WordPress \"frontend\" domain URL e.g. https:\/\/my-headless-site.local\nNEXT_PUBLIC_WP_HOME_URL=http:\/\/snapwp.local\n\n# The WordPress \"backend\" Site Address. Uncomment if different than `NEXT_PUBLIC_WP_HOME_URL` e.g. https:\/\/my-headless-site.local\/wp\/\n# NEXT_PUBLIC_WP_SITE_URL=\n\n# The WordPress Uploads directory path\n# NEXT_PUBLIC_WP_UPLOADS_DIRECTORY=\/wp-content\/uploads\n\n# Only enable if connecting to a self-signed cert\nNODE_TLS_REJECT_UNAUTHORIZED=0"
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
