# Actions & Filters

## TOC

- [Action Hooks](#action-hooks)
  - [Activation / Deactivation](#activation--deactivation)
    - [`snapwp_helper/activate`](#snapwp_helperactivate)
  - [Admin Notices](#admin-notices)
    - [`admin_notices`](#admin_notices)
    - [`network_admin_notices`](#network_admin_notices)
  - [GraphQL](#graphql)
    - [`snapwp_helper/graphql/init/register_types`](#snapwp_helpergraphqlinitregister_types)
    - [`snapwp_helper/graphql/init/after_register_types`](#snapwp_helpergraphqlinitafter_register_types)
    - [`graphql_register_types_late`](#graphql_register_types_late)
    - [`graphql_register_initial_types`](#graphql_register_initial_types)
    - [`graphql_register_types`](#graphql_register_types)
    - [`graphql_init`](#graphql_init)
  - [Lifecycle](#lifecycle)
    - [`snapwp_helper/init`](#snapwp_helperinit)
    - [`rest_api_init`](#rest_api_init)
    - [`init`](#init)
    - [`admin_menu`](#admin_menu)
    - [`admin_enqueue_scripts`](#admin_enqueue_scripts)
- [Filter Hooks](#filter-hooks)
  - [GraphQL](#graphql)
    - [`snapwp_helper/graphql/init/registered_{type}_classes`](#snapwp_helpergraphqlinitregistered_type_classes)
    - [`wpgraphql_content_blocks_resolver_content`](#wpgraphql_content_blocks_resolver_content)
    - [`snapwp_helper/graphql/resolve_template_uri`](#snapwp_helpergraphqlresolvetemplateuri)
 - [Lifecycle](#lifecycle)
    - [`snapwp_helper/init/module_classes`](#snapwp_helperinitmodule_classes)
    - [`snapwp_helper/dependencies/registered_dependencies`](#snapwp_helperdependenciesregistered_dependencies)
    - [`query_vars`](#query_vars)
    - [`request`](#request)
    - [`pre_handle_404`](#pre_handle_404)
  - [Plugin Updater](#plugin-updater)
    - [`snapwp_helper/plugin_updater/plugins`](#snapwp_helperplugin_updaterplugins)

## Action Hooks

### Activation / Deactivation

#### `snapwp_helper/activate`

Runs when the plugin is activated.

```php
do_action( 'snapwp_helper/activate' );
```

`admin_notices`

Fires to display admin notices.

```php
do_action( 'admin_notices' );
```

`network_admin_notices`

Fires to display network admin notices.

```php
do_action( 'network_admin_notices' );
```


### GraphQL

#### `snapwp_helper/graphql/init/register_types`

Fires before any GraphQL types are registered.

```php
do_action( 'snapwp_helper/graphql/init/register_types' );
```

#### `snapwp_helper/graphql/init/after_register_types`

Fires after all GraphQL types are registered.

```php
do_action( 'snapwp_helper/graphql/init/after_register_types' );
```

`graphql_register_types_late`

Fire an action as the type registry is initialized. This executes during the graphql_register_types action to allow for earlier hooking.

```php
do_action( 'graphql_register_types_late', WPGraphQL\Registry\TypeRegistry $type_registry );
```

##### Parameters

- `$type_registry` (WPGraphQL\Registry\TypeRegistry): The registry of Types used in the GraphQL Schema

`graphql_register_initial_types`

Fire an action as the type registry is initialized. This executes before the graphql_register_types action to allow for earlier hooking

```php
do_action( 'graphql_register_initial_types', WPGraphQL\Registry\TypeRegistry $type_registry );
```

##### Parameters

- `$type_registry` (WPGraphQL\Registry\TypeRegistry): The registry of Types used in the GraphQL Schema

`graphql_register_types`

Fire an action as the type registry is initialized. This executes before the graphql_register_types action to allow for earlier hooking

```php
do_action( 'graphql_register_types', TypeRegistry $type_registry );
```

##### Parameters

- `$type_registry` (WPGraphQL\Registry\TypeRegistry): The registry of Types used in the GraphQL Schema

`graphql_init`

Fires after themes have been setup, allowing for both plugins and themes to register things before graphql_init.

```php
do_action( 'graphql_init', WPGraphQL $instance );
```

##### Parameters

- `$instance` (WPGraphQL): The instance of the WPGraphQL class


### Lifecycle

#### `snapwp_helper/init`

Runs when the plugin is initialized.

```php
do_action( 'snapwp_helper/init', \SnapWP\Helper\Main $instance );
```

##### Parameters

- `$instance` _(\SnapWP\Helper\Main)_: The main plugin class instance.

`rest_api_init`

Fires when preparing to serve a REST API request.

```php
do_action( 'rest_api_init' );
```

`init`

Fires after WordPress has finished loading but before any headers are sent.

```php
do_action( 'init' );
```

`admin_menu`

Fires before the administration menu loads in the admin.

```php
do_action( 'admin_menu' );
```

`admin_enqueue_scripts`

Fires when enqueuing scripts for all admin pages.

```php
do_action( 'admin_enqueue_scripts' );
```


## Filter Hooks

### GraphQL

#### `snapwp_helper/graphql/init/registered_{type}_classes`

Filters the list of registered classes for a specific GraphQL Type. Classes must implement the `SnapWP\Helper\Interfaces\GraphQLType` interface.

`{type}` can be one of the following:
- `enum` : Enum Type
- `input` : Input Type
- `interface` : Interface Type
- `object` : Object Type
- `field` : Fields on an Existing Type
- `connection` : Relay-compliant Connection Type
- `mutation` : Mutation Type


```php
apply_filters( 'snapwp_helper/graphql/init/registered_enum_classes', array $registered_classes );
apply_filters( 'snapwp_helper/graphql/init/registered_input_classes', array $registered_classes );
apply_filters( 'snapwp_helper/graphql/init/registered_interface_classes', array $registered_classes );
apply_filters( 'snapwp_helper/graphql/init/registered_object_classes', array $registered_classes );
apply_filters( 'snapwp_helper/graphql/init/registered_field_classes', array $registered_classes );
apply_filters( 'snapwp_helper/graphql/init/registered_connection_classes', array $registered_classes );
apply_filters( 'snapwp_helper/graphql/init/registered_mutation_classes', array $registered_classes );
```

##### Parameters

- `$registered_classes` _(class-string<\SnapWP\Helper\Interfaces\GraphQLType>[])_: An array of fully-qualified GraphQL Type class-names.

#### `wpgraphql_content_blocks_resolver_content`

Filters the content from a node before it's turned into blocks. It can be used to adjust or limit the content that's displayed in the application.

```php
apply_filters( 'wpgraphql_content_blocks_resolver_content', $content, $node, $args );
```

##### Parameters

- `$content` (string): The content being filtered.
- `$node` (mixed): The current GraphQL node.
- `$args` (array): The arguments provided for content resolution.

`snapwp_helper/graphql/resolve_template_uri`

This filter allows to use a custom template for a specific URI instead of the default WordPress template. It’s useful if your template doesn’t follow WordPress’s usual URL structure.

```php
apply_filters( 'snapwp_helper/graphql/resolve_template_uri', null, $uri, $context, $wp, $extra_query_vars );
```

##### Parameters

- `$uri` (string): The template URI being resolved.
- `$context` (mixed): Context information.
- `$wp` (object): The WP object instance.
- `$extra_query_vars` (array): Additional query variables.

### Lifecycle

#### `snapwp_helper/init/module_classes`

Filters the list of module classes to be loaded.

```php
apply_filters( 'snapwp_helper/init/module_classes', array $module_classes );
```

##### Parameters

- `$module_classes` _(class-string<\SnapWP\Helper\Interfaces\Module>[])_: An array of fully-qualified Module class-names to load.

#### `snapwp_helper/dependencies/registered_dependencies`

Filters the array of external dependencies (e.g. WordPress plugins ) required by the plugin.

```php
apply_filters( 'snapwp_helper/dependencies/registered_dependencies', array $dependencies );
```

##### Parameters

- `$dependencies` _(array)_: An array of dependencies. Each element in the array is an associative array with the following keys
   - `slug` _(string)_: A unique slug to identify the dependency. E.g. Plugin slug.
	 - `name` _(string)_: The pretty name of the dependency used in the admin notice.
	 - `check_callback` _(`callable(): true|\WP_Error`)_: A callable that returns true if the dependency is met, or a `WP_Error` object (with an explicit error message) if the dependency is not met.


`query_vars`

Filters the query variables allowed before processing.

```php
apply_filters( 'query_vars', $wp->public_query_vars );
```

`request`

Filters the list of query variables for the current request.

```php
apply_filters( 'request', $wp->query_vars );
```

`pre_handle_404`

Filters whether to short-circuit default header status handling.

```php
apply_filters( 'pre_handle_404', false, $wp_query );
```

##### Parameters
- `$wp_query` (WP_Query): The current query object.

### Plugin Updater

#### `snapwp_helper/plugin_updater/plugins`

Filters the list of plugins to be updated using the Plugin Updater.

```php
apply_filters( 'snapwp_helper/plugin_updater/plugins', array $plugins );
```

##### Parameters

- `$plugins` _(array{slug,update_uri}[])_: An array of plugins to be checked for updates. Each element in the array is an associative array with the following keys:

   - `slug` _(string)_: The qualified plugin slug with it's folder. E.g. 'wp-graphql-my-plugin/wp-graphql-my-plugin.php'.
   - `update_uri` _(string)_: The URI used to check for plugin updates.
