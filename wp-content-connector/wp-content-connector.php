<?php
/*
Plugin Name: WP Content Connector
Description: A lightweight plugin to receive content via secure API and store as draft posts.
Version: 1.4
Author: (leave blank or your team name)
*/

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * 🔹 On activation, set default plugin options
 */
register_activation_hook(__FILE__, function () {
    if (get_option('wpcc_active_status') === false) {
        add_option('wpcc_active_status', '1'); // 1 = Active, 0 = Inactive
    }
});

/**
 * 🔹 Add settings page under Settings → Connector
 */
add_action('admin_menu', function () {
    add_options_page(
        'WP Content Connector',
        'Connector',
        'manage_options',
        'wpcc-connector',
        function () {
            ?>
            <div class="wrap">
                <h1>WP Content Connector Settings</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wpcc_options_group');
                    do_settings_sections('wpcc-connector');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
    );
});

/**
 * 🔹 Register plugin settings (API Key + Active toggle)
 */
add_action('admin_init', function () {
    register_setting('wpcc_options_group', 'wpcc_api_key');
    register_setting('wpcc_options_group', 'wpcc_active_status');

    add_settings_section('wpcc_main_section', 'Main Settings', null, 'wpcc-connector');

    // API Key input field
    add_settings_field('wpcc_api_key', 'API Key', function () {
        $val = esc_attr(get_option('wpcc_api_key', ''));
        echo "<input type='text' name='wpcc_api_key' value='$val' style='width:300px;' />";
    }, 'wpcc-connector', 'wpcc_main_section');

    // Active toggle checkbox
    add_settings_field('wpcc_active_status', 'Plugin Active?', function () {
        $val = get_option('wpcc_active_status', '1');
        $checked = ($val == '1') ? 'checked' : '';
        echo "<input type='checkbox' name='wpcc_active_status' value='1' $checked />";
    }, 'wpcc-connector', 'wpcc_main_section');
});

/**
 * 🔹 Register custom REST API endpoints
 */
add_action('rest_api_init', function () {
    // Content ingest endpoint
    register_rest_route('connector/v1', '/ingest', array(
        'methods' => 'POST',
        'callback' => 'wpcc_ingest_content',
        'permission_callback' => '__return_true'
    ));

    // Simple ping endpoint (for testing)
    register_rest_route('connector/v1', '/ping', array(
        'methods' => 'GET',
        'callback' => function () {
            return array(
                'status' => 'success',
                'message' => 'Ping successful!'
            );
        },
        'permission_callback' => '__return_true'
    ));
});

/**
 * 🔹 Ingest content callback
 */
function wpcc_ingest_content($request) {
    // Check if plugin is active
    $status = get_option('wpcc_active_status', '1');
    if ($status != '1') {
        return array(
            'status' => 'inactive',
            'message' => 'Plugin is currently inactive.'
        );
    }

    // Check API key (supports both header + JSON body)
    $saved_api_key   = get_option('wpcc_api_key', '');
    $incoming_api_key = $request->get_header('authorization');

    if (strpos($incoming_api_key, 'Bearer ') === 0) {
        $incoming_api_key = trim(str_replace('Bearer ', '', $incoming_api_key));
    } else {
        $incoming_api_key = $request['api_key'] ?? '';
    }

    if ($incoming_api_key !== $saved_api_key) {
        return array(
            'status' => 'error',
            'message' => 'Invalid API key.'
        );
    }

    // Sanitize input
    $title       = sanitize_text_field($request['title']);
    $description = sanitize_textarea_field($request['description']);
    $tags        = $request['tags'] ?? [];
    $category    = sanitize_text_field($request['category']);
    $media_url   = esc_url_raw($request['media_url']);

    // Insert post as draft
    $post_id = wp_insert_post(array(
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'draft',
        'post_author'  => 1,
    ));

    if (is_wp_error($post_id)) {
        return array(
            'status' => 'error',
            'message' => 'Failed to insert post.'
        );
    }

    // Set tags (auto-create if missing)
    if (!empty($tags) && is_array($tags)) {
        wp_set_post_tags($post_id, $tags);
    }

    // Set category (auto-create if missing)
    if (!empty($category)) {
        $cat_obj = get_category_by_slug(sanitize_title($category));
        if (!$cat_obj) {
            $new_cat = wp_insert_term($category, 'category');
            if (!is_wp_error($new_cat)) {
                $cat_id = $new_cat['term_id'];
            } else {
                $cat_id = 0;
            }
        } else {
            $cat_id = $cat_obj->term_id;
        }
        if ($cat_id) {
            wp_set_post_categories($post_id, array($cat_id));
        }
    }

    // Save media URL as custom field (_connector_media_url)
    if (!empty($media_url)) {
        update_post_meta($post_id, '_connector_media_url', $media_url);
    }

    // Success response
    return array(
        'status'  => 'success',
        'message' => 'Post created successfully.',
        'post_id' => $post_id
    );
}
?>
