# GraphQL Queries

This document outlines the GraphQL queries available in the SnapWP Helper plugin.

## Table of Contents

- [Querying `RenderedTemplate` data with `templateByUri`](#querying-renderedtemplate-data-with-templatebyuri)

## Querying `RenderedTemplate` data with `templateByUri`

The `RootQuery.templateByUri` field is used to fetch the rendered template data (`RenderedTemplate`) for a given URI. This query allows the use WordPress's Block Template rendering engine as the full source of truth for a headless frontend.

### Query Structure

```graphql
query GetTemplateByUri( $uri: String! ) {
  templateByUri( uri: $uri ) {
    bodyClasses # The CSS classes for the <body> tag
    connectedNode { # The main content node associated with the URI
      ...CurrentNodeFrag
    }
    content # The content for the template. This is the serialized block markup and HTML.
    editorBlocks { # The editor blocks for the template
      ...BlockFrag
    }
    enqueuedScripts(first: 1000) { # The enqueued scripts for the template
      nodes {
        ...EnqueuedAssetFrag
      }
    }
    enqueuedStylesheets(first: 1000) { # The enqueued stylesheets for the template
      nodes {
        ...EnqueuedAssetFrag
      }
    }
    id
    isComment
    isContentNode
    isFrontPage
    isPostsPage
    isTermNode
    renderedHtml # The fully rendered HTML content for the given URI
    uri
  }
}
```

>[!WARNING]
> `templateByUri.enqueuedScripts` currently does not return the correct data. This is a known issue in WPGraphQL core and will be fixed in a future release.
