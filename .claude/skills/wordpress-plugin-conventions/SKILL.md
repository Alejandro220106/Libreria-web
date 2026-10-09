---
name: wordpress-plugin-conventions
description: Conventions for the custom WooCommerce plugin and the child theme of Libreria-web (field handling, sanitization, permissions, REST exposure). Use when writing or reviewing anything under tienda/.
---

# WordPress plugin and child theme conventions

## Location and header
- Plugin: `tienda/plugins/<plugin-name>/<plugin-name>.php`. Child theme: `tienda/themes/<theme-name>/`. Only team-written code is versioned.
- Plugin header: `Plugin Name`, `Description`, `Version`, `Requires PHP: 8.2`, `Requires Plugins: woocommerce`. No dependency on any other plugin.
- Every PHP file starts with `defined( 'ABSPATH' ) || exit;`. Start the features on `plugins_loaded` only when WooCommerce is active.
- One class per concern (field definition, admin screen, public output, REST) with a simple autoloader. Comments and identifiers in English.

## The custom field (one value: short text, number or one option from a list)
- Define the field once (prefixed meta key such as `_lib_`, label, type, validation rule, REST property name). The admin screen, the public page and the REST API all read that definition, so a second field later is one more definition.
- Admin: render in the product data panel (`woocommerce_product_options_general_product_data`) and save on `woocommerce_process_product_meta`. Verify the nonce and `current_user_can( 'edit_product', $post_id )`. Sanitize, then validate; an invalid value is not saved.
- Public page: escape the output (`esc_html()`) and print nothing when the field is empty.
- REST: `register_rest_field( 'product', '<property>', ... )` with `get_callback`, `update_callback` and a schema. The field is its own property (not inside `meta_data`) in `GET /wp-json/wc/v3/products` and in `/products/{id}`. An invalid value in `PUT` returns a `WP_Error` with status 400 and saves nothing.
- The validation function is shared by the admin screen and the REST update.

## Child theme
- `style.css` header with `Theme Name` and `Template: <parent-folder>`. The parent theme must be actively maintained. At least one visible change (styles or a template).
- Classic parent: enqueue the parent and child styles in `functions.php`. Block parent: use `theme.json` and templates.

## Never
- Commit WordPress core, WooCommerce or third-party plugins.
- Hard-code URLs, keys or credentials, create a user named `admin`, or leave debugging output.

## Verify
- Admin: edit the field, try an invalid value, try a user without product permissions.
- Public product page shows the value.
- Postman: request 2 returns the field, request 4 updates it, an invalid value returns 400.
