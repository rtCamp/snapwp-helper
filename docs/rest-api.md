# REST API Documentation

This document outlines the REST API endpoints available in the SnapWP Helper plugin.

## Endpoints

### Generate Environment Variables

Generate environment variables for the SnapWP Helper plugin.

- **URL:** `/snapwp/v1/env`
- **Method:** GET
- **Authentication:** Required (Administrator role)

#### Request Parameters

The request should include a JSON body with the following structure:

```json
{
  "variables": [
    {
      "name": "VARIABLE_NAME",
      "value": "VARIABLE_VALUE"
    },
    // ... additional variables
  ]
}
```

#### Supported Variables

- `NODE_TLS_REJECT_UNAUTHORIZED`: Enable if connecting to a self-signed cert
- `NEXT_URL`: The headless frontend domain URL
- `HOME_URL`: The WordPress "frontend" domain URL
- `GRAPHQL_ENDPOINT`: The WordPress GraphQL endpoint

#### Response

##### Success Response

- **Code:** 200 OK
- **Content:**

```json
{
  "content": "# Generated .env file content"
}
```

##### Error Response

- **Code:** 500 Internal Server Error
- **Content:**

```json
{
  "code": "env_generation_failed",
  "message": "Error message describing the issue"
}
```

#### Notes

- The endpoint will only include supported variables in the generated .env file.
- Required variables (`NEXT_URL` and `HOME_URL`) must have a value.
- If `GRAPHQL_ENDPOINT` is not provided, it will default to "graphql".
- The `NODE_TLS_REJECT_UNAUTHORIZED` variable will be commented out if not provided.

#### Example Usage

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

This will return the generated .env file content based on the provided variables.
