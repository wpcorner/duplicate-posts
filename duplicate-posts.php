<?php
/*
 *Plugin Name:       Duplicate Posts
 * Plugin URI:        https://wpcorner.co/duplicate-posts
 * Description:       Easily duplicate posts, pages, products, and custom posts with a single click.
 * Version:           1.0.0
 * Author:            WP Corner
 * Author URI:        https://wpcorner.co/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       duplicate-posts
 * Domain Path:       /languages
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'DUPLICATE_POSTS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-duplicate-posts-activator.php
 */
function activate_duplicate_posts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-duplicate-posts-activator.php';
	Duplicate_Posts_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-duplicate-posts-deactivator.php
 */
function deactivate_duplicate_posts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-duplicate-posts-deactivator.php';
	Duplicate_Posts_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_duplicate_posts' );
register_deactivation_hook( __FILE__, 'deactivate_duplicate_posts' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-duplicate-posts.php';


// Duplicate post, page, or product
function duplicate_post_link($actions, $post) {
    // Check if the post type is supported (post, page, or product)
    if (in_array($post->post_type, array('post', 'page', 'product'))) {
        $actions['duplicate'] = '<a href="' . admin_url('admin.php?action=duplicate_post&post=' . $post->ID) . '">Duplicate</a>';
    }

    return $actions;
}

// Handle post duplication
function duplicate_post_action() {
    if (isset($_GET['action']) && $_GET['action'] === 'duplicate_post' && isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        $new_post_id = duplicate_post($post_id);

        // Redirect to the new duplicated post
        if ($new_post_id) {
            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            exit;
        }
    }
}

// Duplicate post function
function duplicate_post($post_id) {
    $post = get_post($post_id);

    // Create a new post with the same content and metadata
    $new_post_args = array(
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_status' => 'draft',
        'post_type' => $post->post_type,
    );

    $new_post_id = wp_insert_post($new_post_args);

    // Duplicate post metadata
    $post_meta = get_post_meta($post_id);
    foreach ($post_meta as $key => $values) {
        foreach ($values as $value) {
            add_post_meta($new_post_id, $key, $value);
        }
    }

    return $new_post_id;
}

// Add the duplicate link next to the Edit link
add_filter('post_row_actions', 'duplicate_post_link', 10, 2);
add_filter('page_row_actions', 'duplicate_post_link', 10, 2);
add_filter('product_row_actions', 'duplicate_post_link', 10, 2);

// Handle duplication action
add_action('admin_action_duplicate_post', 'duplicate_post_action');
?>
