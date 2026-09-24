<?php
/**
 * Plugin Name: Portfolio Grid Toolkit
 * Description: Manage portfolio projects and display filterable, accessible video grids.
 * Version: 1.0.0
 * Text Domain: portfolio-grid-toolkit
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PGTK_VERSION', '1.0.0' );
define( 'PGTK_PATH', plugin_dir_path( __FILE__ ) );
define( 'PGTK_URL', plugin_dir_url( __FILE__ ) );

require_once PGTK_PATH . 'includes/post-type.php';
require_once PGTK_PATH . 'includes/admin.php';
require_once PGTK_PATH . 'includes/shortcode.php';
require_once PGTK_PATH . 'includes/sort.php';

register_activation_hook( __FILE__, 'pgtk_plugin_activate' );
function pgtk_plugin_activate() {
    pgtk_register_portfolio_post_type();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'init', 'pgtk_register_image_size' );
function pgtk_register_image_size() {
    add_image_size( 'pgtk-thumb', 800, 450, true );
}

add_action( 'wp_enqueue_scripts', 'pgtk_register_frontend_assets', 5 );
function pgtk_register_frontend_assets() {
    wp_register_style( 'pgtk-style', PGTK_URL . 'assets/css/style.css', array(), PGTK_VERSION );
    wp_register_script( 'pgtk-script', PGTK_URL . 'assets/js/main.js', array(), PGTK_VERSION, true );
}

/**
 * Load the grid assets for classic content or an Elementor shortcode widget.
 * No homepage, theme, contact-page, or SEO behavior is changed by this plugin.
 */
add_action( 'wp_enqueue_scripts', 'pgtk_enqueue_current_page_assets', 10 );
function pgtk_enqueue_current_page_assets() {
    if ( ! is_singular() ) {
        return;
    }

    $post_id = get_queried_object_id();
    $post    = $post_id ? get_post( $post_id ) : null;
    $source  = $post ? (string) $post->post_content : '';
    $source .= "\n" . (string) get_post_meta( $post_id, '_elementor_data', true );
    if ( false === strpos( $source, '[pgtk_work' ) ) {
        return;
    }

    wp_enqueue_style( 'pgtk-style' );
    wp_enqueue_script( 'pgtk-script' );
}
