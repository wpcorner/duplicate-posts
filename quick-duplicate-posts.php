<?php

/*
 * Plugin Name: Quick Duplicate Posts
 * Plugin URI: https://wpcorner.co/plugins
 * Description: Easily duplicate posts, pages, products, and custom posts with a single click.
 * Version: 1.0.0
 * Author: WP Corner
 * Author URI: https://wpcorner.co
 * Contributors: wpcorner, lumiblog
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: quick-duplicate-posts
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Duplicate post, page, or product
function duplicate_post_link( $actions, $post ) {
    if ( in_array( $post->post_type, array( 'post', 'page', 'product' ), true ) ) {
        $nonce = wp_create_nonce( 'duplicate_post_' . $post->ID );
        $actions['duplicate'] = '<a href="' . esc_url( admin_url( 'admin.php?action=duplicate_post&post=' . absint( $post->ID ) . '&nonce=' . $nonce ) ) . '">' . esc_html__( 'Duplicate', 'quick-duplicate-posts' ) . '</a>';
    }
    return $actions;
}



// Handle post duplication
function duplicate_post_action() {
    // Check for required parameters and nonce
    if ( ! isset( $_GET['nonce'] ) || ! isset( $_GET['post'] ) || ! isset( $_GET['action'] ) ) {
        wp_die( esc_html__( 'Invalid request', 'quick-duplicate-posts' ) );
    }

    $action = sanitize_key( $_GET['action'] );
    $post_id = absint( $_GET['post'] );
    $nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

    // Verify nonce
    if ( 'duplicate_post' !== $action || ! wp_verify_nonce( $nonce, 'duplicate_post_' . $post_id ) ) {
        wp_die( esc_html__( 'Nonce verification failed', 'quick-duplicate-posts' ) );
    }

    $new_post_id = duplicate_post( $post_id );

    // Redirect to the new duplicated post
    if ( $new_post_id ) {
        wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
        exit;
    }
}

// Duplicate post function
function duplicate_post( $post_id ) {
    $post = get_post( $post_id );

    // Create a new post with the same content and metadata
    $new_post_args = array(
        'post_title'   => $post->post_title,
        'post_content' => $post->post_content,
        'post_status'  => 'draft',
        'post_type'    => $post->post_type,
    );

    $new_post_id = wp_insert_post( $new_post_args );

    // Duplicate post metadata
    $post_meta = get_post_meta( $post_id );
    foreach ( $post_meta as $key => $values ) {
        foreach ( $values as $value ) {
            add_post_meta( $new_post_id, sanitize_key( $key ), sanitize_meta( $key, $value, 'post' ) );
        }
    }

    return $new_post_id;
}

// Add the duplicate link next to the Edit link
add_filter( 'post_row_actions', 'duplicate_post_link', 10, 2 );
add_filter( 'page_row_actions', 'duplicate_post_link', 10, 2 );
add_filter( 'product_row_actions', 'duplicate_post_link', 10, 2 );

// Handle duplication action
add_action( 'admin_action_duplicate_post', 'duplicate_post_action' );
