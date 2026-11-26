<?php
/**
 * Plugin Name:       Ashbi Portfolio Grid
 * Plugin URI:        https://github.com/camster91/portfolio-grid-toolkit
 * Description:       Show your video and design work in a filterable grid that fits any theme. Build it visually, then paste one shortcode.
 * Version:           1.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Cameron Ashley
 * Author URI:        https://github.com/camster91
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ashbi-portfolio-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ASHBPG_VERSION', '1.1.0' );
define( 'ASHBPG_PATH', plugin_dir_path( __FILE__ ) );
define( 'ASHBPG_URL', plugin_dir_url( __FILE__ ) );

require_once ASHBPG_PATH . 'includes/post-type.php';
require_once ASHBPG_PATH . 'includes/admin.php';
require_once ASHBPG_PATH . 'includes/shortcode.php';
require_once ASHBPG_PATH . 'includes/sort.php';

register_activation_hook( __FILE__, 'ashbpg_plugin_activate' );
function ashbpg_plugin_activate() {
    ashbpg_register_portfolio_post_type();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'init', 'ashbpg_register_image_size' );
function ashbpg_register_image_size() {
    add_image_size( 'ashbpg-thumb', 800, 450, true );
}

add_action( 'wp_enqueue_scripts', 'ashbpg_register_frontend_assets', 5 );
function ashbpg_register_frontend_assets() {
    wp_register_style( 'ashbpg-ui', ASHBPG_URL . 'assets/ashbi-ui/front.css', array(), ASHBPG_VERSION );
    wp_register_style( 'ashbpg-style', ASHBPG_URL . 'assets/css/style.css', array( 'ashbpg-ui' ), ASHBPG_VERSION );
    wp_register_script( 'ashbpg-script', ASHBPG_URL . 'assets/js/main.js', array(), ASHBPG_VERSION, true );
}

/**
 * Load the grid assets for classic content or an Elementor shortcode widget.
 * No homepage, theme, contact-page, or SEO behavior is changed by this plugin.
 */
add_action( 'wp_enqueue_scripts', 'ashbpg_enqueue_current_page_assets', 10 );
function ashbpg_enqueue_current_page_assets() {
    if ( ! is_singular() ) {
        return;
    }

    $post_id = get_queried_object_id();
    $post    = $post_id ? get_post( $post_id ) : null;
    $source  = $post ? (string) $post->post_content : '';
    $source .= "\n" . (string) get_post_meta( $post_id, '_elementor_data', true );
    if ( ! ashbpg_content_has_shortcode( $source ) ) {
        return;
    }

    wp_enqueue_style( 'ashbpg-style' );
    wp_enqueue_script( 'ashbpg-script' );
}

/**
 * Whether content uses the portfolio shortcode, under its current or older name.
 *
 * @param string $source Post content and builder data.
 * @return bool
 */
function ashbpg_content_has_shortcode( $source ) {
    foreach ( ashbpg_shortcode_tags() as $tag ) {
        if ( false !== strpos( (string) $source, '[' . $tag ) ) {
            return true;
        }
    }
    return false;
}
