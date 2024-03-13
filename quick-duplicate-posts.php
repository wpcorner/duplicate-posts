<?php
/*
 * Plugin Name: Quick Duplicate Posts
 * Plugin URI: https://wpcorner.co/duplicate-posts
 * Description: Easily duplicate posts, pages, products, and custom posts with a single click.
 * Version: 1.0.0
 * Author: WP Corner
 * Author URI: https://wpcorner.co/
 * Contributors: wpcorner, lumiblog
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: quick-duplicate-posts
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( false === defined( 'ABSPATH' ) ) {
    return;
}

// Duplicate post, page, or product
function qdp_duplicate_post_link($actions, $post) {
    // Check if the post type is supported (post, page, or product)
    if (in_array($post->post_type, array('post', 'page', 'product'), true)) {
        $actions['duplicate'] = '<a href="' . esc_url(admin_url('admin.php?action=duplicate_post&post=' . $post->ID)) . '">' . esc_html__('Duplicate', 'quick-duplicate-posts') . '</a>';
    }

    return $actions;
}

// Handle post duplication
function qdp_duplicate_post_action() {
    if (isset($_GET['action']) && $_GET['action'] === 'duplicate_post' && isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        $new_post_id = qdp_duplicate_post($post_id);

        // Redirect to the new duplicated post
        if ($new_post_id) {
            wp_redirect(esc_url(admin_url('post.php?action=edit&post=' . $new_post_id)));
            exit;
        }
    }
}

// Duplicate post function
function qdp_duplicate_post($post_id) {
    $post = get_post($post_id);

    // Create a new post with the same content and metadata
    $new_post_args = array(
        'post_title' => sanitize_text_field($post->post_title),
        'post_content' => wp_kses_post($post->post_content),
        'post_status' => 'draft',
        'post_type' => $post->post_type,
    );

    $new_post_id = wp_insert_post($new_post_args);

    // Duplicate post metadata
    $post_meta = get_post_meta($post_id);
    foreach ($post_meta as $key => $values) {
        foreach ($values as $value) {
            add_post_meta($new_post_id, sanitize_text_field($key), sanitize_text_field($value));
        }
    }

    return $new_post_id;
}

// Add the duplicate link next to the Edit link
add_filter('post_row_actions', 'qdp_duplicate_post_link', 10, 2);
add_filter('page_row_actions', 'qdp_duplicate_post_link', 10, 2);
add_filter('product_row_actions', 'qdp_duplicate_post_link', 10, 2);

// Handle duplication action
add_action('admin_action_duplicate_post', 'qdp_duplicate_post_action');
?>
