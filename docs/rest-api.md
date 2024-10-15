# REST API Endpoints

This document provides documentation for the REST API endpoints available in the SnapWP Helper plugin.

| Endpoint | Description | Parameters | Response |
| -------- | ----------- | ---------- | -------- |
| [`GET /wp-json/snapwp/v1/env`](#get-wp-jsonsnapwpv1env) | Generates environment variables for the SnapWP Helper plugin | `variables` | `content => string` |

## `GET /wp-json/snapwp/v1/env`

Generates environment variables for the SnapWP Helper plugin. This endpoint creates a .env file content based on the provided variables, only including supported variables and validating required ones.

### Example Usage

```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -u "your_username:your_application_password" \
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
      }
    ]
  }' \
  https://your-wordpress-site.com/wp-json/snapwp/v1/env
```

### Parameters

- `variables`: _`array`_ - (Required) An array of objects containing variable names and values. Each object should have `name` and `value` properties.

  - `NEXT_URL` (Required): The headless frontend domain URL.
  - `HOME_URL` (Required): The WordPress "frontend" domain URL.
  - `GRAPHQL_ENDPOINT`: The WordPress GraphQL endpoint. (Default: `graphql`)
  - `NODE_TLS_REJECT_UNAUTHORIZED`: Enable if connecting to a self-signed cert. (Default: commented out)

Note: This endpoint requires authentication with administrator privileges.

### Response

1. 200 OK

    - **Content**: JSON object containing the generated .env file content.
    
    **Example**:
    
    ```json
    {
        "content": "\n# The headless frontend domain URL\nNEXT_URL=http://localhost:3000\\n\n# The WordPress \"frontend\" domain URL\nHOME_URL=https://headless-demo.local\\n\n# The WordPress GraphQL endpoint\nGRAPHQL_ENDPOINT=wp-graphql\\n"
    }
    ```

    Note: The content includes newline characters (`\n`) and escaped backslashes (`\\n`) at the end of each line.

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
