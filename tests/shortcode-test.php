<?php
/**
 * Contract tests for the reusable portfolio renderer.
 */

define( 'ABSPATH', __DIR__ );

$pgtk_test_shortcodes = array();
$pgtk_test_query_args = array();
$pgtk_test_preview    = false;
$pgtk_test_meta       = array();
$pgtk_test_can_edit_page = true;

function add_shortcode( $tag, $callback ) {
    global $pgtk_test_shortcodes;
    $pgtk_test_shortcodes[ $tag ] = $callback;
}

function shortcode_atts( $pairs, $atts ) {
    return array_merge( $pairs, array_intersect_key( $atts, $pairs ) );
}

function sanitize_title( $value ) {
    $value = strtolower( trim( (string) $value ) );
    return preg_replace( '/[^a-z0-9-]+/', '-', $value );
}

function sanitize_text_field( $value ) {
    return trim( strip_tags( (string) $value ) );
}

function is_preview() {
    global $pgtk_test_preview;
    return $pgtk_test_preview;
}

function current_user_can() {
    global $pgtk_test_can_edit_page;
    return $pgtk_test_can_edit_page;
}

function __( $text ) {
    return $text;
}

function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr_e( $text ) {
    echo esc_attr( $text );
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html_e( $text ) {
    echo esc_html( $text );
}

function esc_url( $url ) {
    return filter_var( (string) $url, FILTER_SANITIZE_URL );
}

function get_post_meta( $post_id, $key ) {
    global $pgtk_test_meta;
    return isset( $pgtk_test_meta[ $post_id ][ $key ] ) ? $pgtk_test_meta[ $post_id ][ $key ] : '';
}

function wp_kses_post( $text ) {
    return $text;
}

function wp_strip_all_tags( $text ) {
    return strip_tags( $text );
}

class WP_Query {
    public function __construct( $args ) {
        global $pgtk_test_query_args;
        $pgtk_test_query_args = $args;
    }

    public function have_posts() {
        return false;
    }
}

require dirname( __DIR__ ) . '/includes/shortcode.php';

if ( empty( $pgtk_test_shortcodes['pgtk_work'] ) ) {
    fwrite( STDERR, "Portfolio shortcode was not registered.\n" );
    exit( 1 );
}

$pgtk_test_preview = true;
$markup = pgtk_work_shortcode(
    array(
        'collection'     => 'Campaigns',
        'artist'         => 'Creator',
        'heading'        => 'Selected <script>work',
        'variant'        => 'artist',
        'posts_per_page' => '6',
    )
);

if ( array( 'publish', 'draft', 'pending', 'future', 'private' ) !== $pgtk_test_query_args['post_status'] ) {
    fwrite( STDERR, "Authenticated previews must include editable draft portfolio records.\n" );
    exit( 1 );
}

$tax_query = $pgtk_test_query_args['tax_query'];
if ( 'AND' !== $tax_query['relation'] || 'campaigns' !== $tax_query[0]['terms'] || 'creator' !== $tax_query[1]['terms'] ) {
    fwrite( STDERR, "Artist portfolio query was not scoped to the requested collection and artist.\n" );
    exit( 1 );
}

if ( false === strpos( $markup, 'pgtk-container--artist' ) || false === strpos( $markup, '>Selected work<' ) || false !== strpos( $markup, '<script>' ) ) {
    fwrite( STDERR, "Artist portfolio markup did not preserve the safe visual contract.\n" );
    exit( 1 );
}

if ( array() !== pgtk_get_caption_lines( 3729 ) ) {
    fwrite( STDERR, "Temporary grid labels must not leak into the modal credits.\n" );
    exit( 1 );
}

if ( '' !== pgtk_get_artist_grid_subtitle( 3729 ) ) {
    fwrite( STDERR, "Missing credits must not create a placeholder subtitle.\n" );
    exit( 1 );
}

$pgtk_test_meta[3729] = array(
    'agency'   => '<strong>Confirmed Agency</strong>',
    'client'   => 'Example client',
    'director' => 'Director Name',
);

if ( 'Confirmed Agency' !== pgtk_get_artist_grid_subtitle( 3729 ) ) {
    fwrite( STDERR, "Artist grid subtitles must prefer the sanitized confirmed agency value.\n" );
    exit( 1 );
}

if ( array( 'Client: Example client', 'Agency: Confirmed Agency', 'Director: Director Name' ) !== pgtk_get_caption_lines( 3729 ) ) {
    fwrite( STDERR, "Full structured credits must remain available to the project modal.\n" );
    exit( 1 );
}

$shortcode_source = file_get_contents( dirname( __DIR__ ) . '/includes/shortcode.php' );
$style_source     = file_get_contents( dirname( __DIR__ ) . '/assets/css/style.css' );
if ( false === strpos( $shortcode_source, 'class="pgtk-agency"' ) || false !== strpos( $shortcode_source, 'foreach ( $item[\'caption_lines\'] as $credit_line )' ) ) {
    fwrite( STDERR, "Artist cards must render exactly one agency subtitle instead of every modal credit line.\n" );
    exit( 1 );
}

if ( false !== strpos( $style_source, '.pgtk-container--artist .pgtk-info {' . "\n" . '    position: absolute;' ) ) {
    fwrite( STDERR, "Artist project information must not be visually hidden.\n" );
    exit( 1 );
}

$plugin_source = file_get_contents( dirname( __DIR__ ) . '/portfolio-grid-toolkit.php' );
if ( false === strpos( $plugin_source, "wp_enqueue_style( 'pgtk-style' )" ) || false !== strpos( $plugin_source, 'global-fixes' ) ) {
    fwrite( STDERR, "Only the portfolio assets should load for pages containing the grid shortcode.\n" );
    exit( 1 );
}

$pgtk_test_preview = false;
pgtk_work_shortcode( array( 'artist' => 'creator' ) );
if ( array( 'publish' ) !== $pgtk_test_query_args['post_status'] ) {
    fwrite( STDERR, "Public portfolio renders must not include draft records.\n" );
    exit( 1 );
}

echo "Portfolio shortcode contract passed.\n";
