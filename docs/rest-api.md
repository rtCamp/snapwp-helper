# REST API Endpoints

This document provides comprehensive documentation for the REST API endpoints available in the SnapWP Helper plugin.

| Endpoint | Description | Arguments | Response |
| -------- | ----------- | --------- | -------- |
| [`GET /snapwp/v1/env`](#get-snapwpv1env) | Generates environment variables for the SnapWP Helper plugin | `variables` | `content => string` |

## `/snapwp/v1/env`

Generates environment variables for the SnapWP Helper plugin.

### Arguments

- `variables`: _`array`_ - (Required) An array of objects containing variable names and values.

### Response

1. 200 OK

    - **Content**: JSON object containing the generated .env file content.
    
    **Example**:
    
    ```json
    {
        "content": "# Generated .env file content\nNEXT_URL=http://localhost:3000\nHOME_URL=https://headless-demo.local\nGRAPHQL_ENDPOINT=graphql\n"
    }
    ```

2. 400 Bad Request

    - **Content**: JSON object indicating that no variables were provided.
    
    **Example**:
    
    ```json
    {
        "code": "missing_variables",
        "message": "No variables provided.",
        "data": {
            "status": 400
        }
    }
    ```

3. 500 Internal Server Error

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

### Details

**Functionality**:
- Generates a .env file content based on the provided variables.
- Only includes supported variables in the generated .env file.
- Validates required variables (`NEXT_URL` and `HOME_URL`).
- Sets default value for `GRAPHQL_ENDPOINT` if not provided.
- Comments out `NODE_TLS_REJECT_UNAUTHORIZED` if not provided.

**Permissions Required**: Requires the `manage_options` capability (Administrator role).

### Supported Variables

- `NODE_TLS_REJECT_UNAUTHORIZED`: Enable if connecting to a self-signed cert
- `NEXT_URL`: The headless frontend domain URL (Required)
- `HOME_URL`: The WordPress "frontend" domain URL (Required)
- `GRAPHQL_ENDPOINT`: The WordPress GraphQL endpoint (Default: "graphql")

### Example Usage

Here are various examples demonstrating different use cases:

#### 1. Basic Usage - Required Variables Only

```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{
    "variables": [
      {
        "name": "NEXT_URL",
        "value": "http://localhost:3000"
      },
      {
        "name": "HOME_URL",
        "value": "https://headless-demo.local"
      }
    ]
  }' \
  https://your-wordpress-site.com/wp-json/snapwp/v1/env
```

Response:
```json
{
    "content": "NEXT_URL=http://localhost:3000\nHOME_URL=https://headless-demo.local\nGRAPHQL_ENDPOINT=graphql\n"
}
```

#### 2. All Supported Variables

```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{
    "variables": [
      {
        "name": "NEXT_URL",
        "value": "http://localhost:3000"
      },
      {
        "name": "HOME_URL",
        "value": "https://headless-demo.local"
      },
      {
        "name": "GRAPHQL_ENDPOINT",
        "value": "wp-graphql"
      },
      {
        "name": "NODE_TLS_REJECT_UNAUTHORIZED",
        "value": "0"
      }
    ]
  }' \
  https://your-wordpress-site.com/wp-json/snapwp/v1/env
```

Response:
```json
{
    "content": "NEXT_URL=http://localhost:3000\nHOME_URL=https://headless-demo.local\nGRAPHQL_ENDPOINT=wp-graphql\nNODE_TLS_REJECT_UNAUTHORIZED=0\n"
}
```

#### 3. Missing Required Variable

```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{
    "variables": [
      {
        "name": "NEXT_URL",
        "value": "http://localhost:3000"
      }
    ]
  }' \
  https://your-wordpress-site.com/wp-json/snapwp/v1/env
```

Response:
```json
{
    "code": "env_generation_failed",
    "message": "Required variable HOME_URL is missing.",
    "data": {
        "status": 500
    }
}
```

#### 4. Invalid Variable

```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{
    "variables": [
      {
        "name": "NEXT_URL",
        "value": "http://localhost:3000"
      },
      {
        "name": "HOME_URL",
        "value": "https://headless-demo.local"
      },
      {
        "name": "INVALID_VARIABLE",
        "value": "some_value"
      }
    ]
  }' \
  https://your-wordpress-site.com/wp-json/snapwp/v1/env
```

Response:
```json
{
    "content": "NEXT_URL=http://localhost:3000\nHOME_URL=https://headless-demo.local\nGRAPHQL_ENDPOINT=graphql\n"
}
```

Note that the invalid variable is simply ignored in the generated .env content.

#### 5. No Variables Provided

```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{
    "variables": []
  }' \
  https://your-wordpress-site.com/wp-json/snapwp/v1/env
```

Response:
```json
{
    "code": "missing_variables",
    "message": "No variables provided.",
    "data": {
        "status": 400
    }
}
```

### Error Handling

- If required variables (`NEXT_URL` or `HOME_URL`) are missing, the API will return a 500 Internal Server Error with an appropriate error message.
- If no variables are provided, the API will return a 400 Bad Request error.
- If the user doesn't have the required permissions, the API will return a 401 Unauthorized error.

### Best Practices

1. Always include the required variables (`NEXT_URL` and `HOME_URL`) in your request.
2. Use HTTPS URLs for production environments.
3. Only set `NODE_TLS_REJECT_UNAUTHORIZED` to "0" in development environments with self-signed certificates.
4. Handle potential errors in your client-side code, especially for missing required variables.

### Changelog

- v0.0.1: Initial release of the `/snapwp/v1/env` endpoint.

For any issues or feature requests related to this API, please contact the SnapWP Helper plugin support team or open an issue in the plugin's repository.
