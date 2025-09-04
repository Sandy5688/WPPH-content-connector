<?php
/*
Plugin Name: WP Content Connector
Description: A lightweight plugin to receive content via secure API and store as draft posts.
Version: 1.3.1
Author: —
*/

namespace WPCC\Connector;

if (!defined('ABSPATH')) exit;

// 🔹 Add default plugin option on activation
\register_activation_hook(__FILE__, function () {
    if (\get_option('wpcc_active_status') === false) {
        \add_option('wpcc_active_status', '1'); // 1 = Active, 0 = Inactive
    }
});

// 🔹 Add settings page
\add_action('admin_menu', function () {
    \add_options_page(
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
                    \settings_fields('wpcc_options_group');
                    \do_settings_sections('wpcc-connector');
                    \submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
    );
});

// 🔹 Register plugin settings
\add_action('admin_init', function () {
    \register_setting('wpcc_options_group', 'wpcc_api_key');
    \register_setting('wpcc_options_group', 'wpcc_active_status');

    \add_settings_section('wpcc_main_section', 'Main Settings', null, 'wpcc-connector');

    \add_settings_field('wpcc_api_key', 'API Key', function () {
        $val = esc_attr(\get_option('wpcc_api_key', ''));
        echo "<input type='text' name='wpcc_api_key' value='$val' style='width:300px;' />";
    }, 'wpcc-connector', 'wpcc_main_section');

    \add_settings_field('wpcc_active_status', 'Plugin Active?', function () {
        $val = \get_option('wpcc_active_status', '1');
        $checked = ($val == '1') ? 'checked' : '';
        echo "<input type='checkbox' name='wpcc_active_status' value='1' $checked />";
    }, 'wpcc-connector', 'wpcc_main_section');
});

// 🔹 Register custom REST API endpoints
\add_action('rest_api_init', function () {
    \register_rest_route('connector/v1', '/ingest', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\wpcc_ingest_content',
        'permission_callback' => '__return_true'
    ));

    \register_rest_route('connector/v1', '/ping', array(
        'methods' => 'GET',
        'callback' => function () {
            return array('status' => 'ok','message' => 'Ping successful!');
        },
        'permission_callback' => '__return_true'
    ));
});

// 🔹 Ingest content callback
function wpcc_ingest_content($request) {
    $status = \get_option('wpcc_active_status', '1');
    if ($status != '1') {
        return new \WP_REST_Response(array('status' => 'inactive','message' => 'Plugin is currently inactive.'),403);
    }

    $saved_api_key = \get_option('wpcc_api_key', '');
    $incoming_api_key = '';

    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $incoming_api_key = trim($matches[1]);
    } elseif (!empty($request['api_key'])) {
        $incoming_api_key = $request['api_key'];
    }

    if ($incoming_api_key !== $saved_api_key) {
        return new \WP_REST_Response(array('status' => 'error','message' => 'Invalid API key.'),401);
    }

    $title = sanitize_text_field($request['title']);
    $description = sanitize_textarea_field($request['description']);
    $tags = $request['tags'] ?? [];
    $category = sanitize_text_field($request['category']);
    $media_url = esc_url_raw($request['media_url']);

    $post_id = \wp_insert_post(array(
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'draft',
        'post_author'  => 1,
    ));

    if (\is_wp_error($post_id)) {
        return new \WP_REST_Response(array('status' => 'error','message' => 'Failed to insert post'),500);
    }

    if (!empty($tags) && is_array($tags)) {
        \wp_set_post_tags($post_id, $tags);
    }

    if (!empty($category)) {
        $cat_obj = \get_category_by_slug(sanitize_title($category));
        if (!$cat_obj) {
            $new_cat = \wp_insert_term($category, 'category');
            if (!\is_wp_error($new_cat)) {
                $cat_id = $new_cat['term_id'];
            } else {
                $cat_id = 0;
            }
        } else {
            $cat_id = $cat_obj->term_id;
        }
        if ($cat_id) {
            \wp_set_post_categories($post_id, array($cat_id));
        }
    }

    if (!empty($media_url)) {
        \update_post_meta($post_id, '_connector_media_url', $media_url);
    }

    return new \WP_REST_Response(array('status' => 'success','post_id' => $post_id),201);
}
?>