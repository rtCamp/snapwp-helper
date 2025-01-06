# Actions & Filters

## Table of Contents

- [Action Hooks](#action-hooks)
  - [Activation / Deactivation](#activation--deactivation)
    - [`snapwp_helper/activate`](#snapwp_helperactivate)
  - [GraphQL Type Registration](#graphql-type-registration)
    - [`snapwp_helper/graphql/init/register_types`](#snapwp_helpergraphqlinitregister_types)
    - [`snapwp_helper/graphql/init/after_register_types`](#snapwp_helpergraphqlinitafter_register_types)
  - [Lifecycle](#lifecycle)
    - [`snapwp_helper/init`](#snapwp_helperinit)
- [Filter Hooks](#filter-hooks)
  - [GraphQL](#graphql)
    - [`snapwp_helper/graphql/init/registered_{type}_classes`](#snapwp_helpergraphqlinitregistered_type_classes)
    - [`snapwp_helper/graphql/resolve_template_uri`](#snapwp_helpergraphqlresolvetemplateuri)
  - [Lifecycle](#lifecycle)
    - [`snapwp_helper/init/module_classes`](#snapwp_helperinitmodule_classes)
    - [`snapwp_helper/dependencies/registered_dependencies`](#snapwp_helperdependenciesregistered_dependencies)
   - [Plugin Updater](#plugin-updater)
     - [`snapwp_helper/plugin_updater/plugins`](#snapwp_helperplugin_updaterplugins)
   - [Variable Registry](#variable-registry)
     - [`snapwp_helper/env_generator/variables`](#snapwp_helperenv_generatorvariables)

## Action Hooks

### Activation / Deactivation

#### `snapwp_helper/activate`

Runs when the plugin is activated.

```php
do_action( 'snapwp_helper/activate' );
```

### GraphQL Type Registration

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

### Lifecycle

#### `snapwp_helper/init`

Runs when the plugin is initialized.

```php
do_action( 'snapwp_helper/init', \SnapWP\Helper\Main $instance );
```

##### Parameters

- `$instance` _(\SnapWP\Helper\Main)_: The main plugin class instance.

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

#### `snapwp_helper/graphql/resolve_template_uri`

When this filter return anything other than null, it will be used as a resolved node and the execution will be skipped. This is to be used in extensions to resolve their own templates which might not use WordPress permalink structure.

```php
apply_filters( 'snapwp_helper/graphql/resolve_template_uri', mixed|null $node, string $uri, \WPGraphQL\AppContext $context, \WP $wp, array|string $extra_query_vars );
```

##### Parameters

- `$node` (mixed|null): The node, defaults to nothing.
- `$uri` (string): The uri being searched.
- `$content` (\WPGraphQL\AppContext): The app context.
- `$wp` (\WP object): The WP object instance.
- `$extra_query_vars` (array<string,mixed>|string): Any extra query vars to consider.

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

### Variable Registry

#### `snapwp_helper/env_generator/variables`

Filters the list of environment variables used in the VariableRegistry class.

```php
apply_filters( 'snapwp_helper/env_generator/variables', array $variables );
```

##### Parameters

- `$variables` _(array<string,array{description:string,default:string,required:bool}>)_: An array of environment variables with their details. Each element has the following keys:

   - `description` _(string)_: A description of the variable.
   - `default` _(string)_: The default value for the variable.
   - `required` _(bool)_: Whether the variable is required.
