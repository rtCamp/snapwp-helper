# REST API Endpoints

This document provides documentation for the REST API endpoints available in the SnapWP Helper plugin.

| Endpoint | Description | Parameters | Response |
| -------- | ----------- | ---------- | -------- |
| [`GET /wp-json/snapwp/v1/env`](#get-wp-jsonsnapwpv1env) | Generates environment variables for the SnapWP Helper plugin | `variables` | `content => string` |

## `GET /wp-json/snapwp/v1/env`

Generates environment variables for the SnapWP Helper plugin. This endpoint creates a .env file content based on the provided variables, only including supported variables and validating required ones.

### Parameters

- `variables`: _`array`_ - (Required) An array of objects containing variable names and values. Each object should have `name` and `value` properties.

  - `NEXT_URL` (Required): The headless frontend domain URL.
  - `HOME_URL` (Required): The WordPress "frontend" domain URL.
  - `GRAPHQL_ENDPOINT`: The WordPress GraphQL endpoint. (Default: `graphql`)
  - `NODE_TLS_REJECT_UNAUTHORIZED`: Enable if connecting to a self-signed cert. (Default: commented out)

Note: This endpoint requires authentication with administrator privileges.

### Authentication

This endpoint requires administrator privileges. To authenticate:

1. In your WordPress admin panel, go to Users -> Your Profile.
2. Scroll down to the "Application Passwords" section.
3. Enter a name for your application password (e.g., "API Testing") and click "Add New Application Password".
4. Copy the generated password.

You'll use this password along with your username for Basic Auth when making requests.

### Testing Instructions

To verify the functionality of this endpoint, you can use any API testing tool (e.g., cURL, Postman, or a custom script). Follow these steps:

1. **Set up the request**:
   * Create a new GET request.
   * Set the URL to: `https://your-wordpress-site.com/wp-json/snapwp/v1/env`

2. **Set up Authentication**:
   * Use Basic Auth with your WordPress username and the application password you generated.

3. **Set up Request Body**:
   * In the "Body" tab, select "raw" and choose "JSON" from the dropdown.
   * In the request body, enter the following JSON:
     ```json
     {
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
     }
     ```

4. **Send the Request and Verify Responses**:
   * Send the request and verify that you receive a 200 OK response.
   * Check that the response body matches the example in the documentation.

5. **Test Error Cases**:
   * Remove all variables from the request body and send the request. Verify that you receive a 400 Bad Request response matching the documentation.
   * Remove one of the required variables (e.g., NEXT_URL) and send the request. Verify that you receive a 500 Internal Server Error response matching the documentation.

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
