# GraphQL Queries

This document outlines the GraphQL queries available in the SnapWP Helper plugin.

## Table of Contents

- [templateByUri](#templatebyuri)
  - [Query Structure](#query-structure)
  - [Arguments](#arguments)
  - [Return Fields](#return-fields)
  - [Usage Examples](#usage-examples)

## templateByUri

The `templateByUri` query allows you to fetch template data for a given URI. This is particularly useful for headless WordPress setups where you need to determine the appropriate template and content for a given URL.

### Query Structure

```graphql
query TemplateByUri($uri: String!) {
  templateByUri(uri: $uri) {
    bodyClasses
    connectedNode {
      __typename
      ... on DatabaseIdentifier {
        databaseId
      }
      ... on NodeWithTitle {
        title
      }
      ... on TermNode {
        name
      }
      ... on User {
        name
      }
    }
    renderedHtml
  }
}
```

### Arguments

- `uri` (String!, required): The URI for which to fetch the template data.

### Return Fields

- `bodyClasses` (String): A space-separated list of CSS classes for the `<body>` tag.
- `connectedNode` (Node): The main content node associated with the URI.
  - `__typename` (String): The type of the connected node (e.g., "Post", "Page", "Category", etc.).
  - `databaseId` (ID): The database ID of the connected node.
  - `title` (String): The title of the connected node (for content types with titles).
  - `name` (String): The name of the connected node (for taxonomies and users).
- `renderedHtml` (String): The fully rendered HTML content for the given URI.

### Usage Examples

1. Fetching template data for a blog post:

```graphql
query {
  templateByUri(uri: "/2023/05/15/sample-blog-post") {
    bodyClasses
    connectedNode {
      __typename
      ... on Post {
        databaseId
        title
      }
    }
    renderedHtml
  }
}
```

2. Fetching template data for a category archive:

```graphql
query {
  templateByUri(uri: "/category/news") {
    bodyClasses
    connectedNode {
      __typename
      ... on Category {
        databaseId
        name
      }
    }
    renderedHtml
  }
}
```

3. Fetching template data for an author archive:

```graphql
query {
  templateByUri(uri: "/author/johndoe") {
    bodyClasses
    connectedNode {
      __typename
      ... on User {
        databaseId
        name
      }
    }
    renderedHtml
  }
}
```

These examples demonstrate how to use the `templateByUri` query for different types of WordPress content. The query is flexible and can handle various content types, including posts, pages, custom post types, category archives, tag archives, author archives, and more.