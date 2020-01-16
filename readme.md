# mio Admin OmniSearch

Adds a global admin search living in the admin bar for easier post discovery.
All post types that are `'exclude_from_search' => false` and `'show_in_rest' => true` will show up in the post results and within the filter dropdown.

## Customization

Use this filter to unset unwanted post types

```php
add_filter('mio_omnisearch_posttypes', function($post_types) {
    unset($post_types['post']);
    return $post_types;
});
```

Use this filter to configure who can see the OmniSearch

```php
add_filter('mio_omnisearch_capability', function($cap) {
    return 'manage_options';
});
```

Change WP_Query args for the search

```php
add_filter('mio_omnisearch_query', function($args) {
    ...
    return $args;
});
```
