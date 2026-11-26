<?php
/**
 * Contract tests for the reusable portfolio renderer.
 */

define( 'ABSPATH', __DIR__ );

$ashbpg_test_shortcodes = array();
$ashbpg_test_query_args = array();
$ashbpg_test_preview    = false;
$ashbpg_test_meta       = array();
$ashbpg_test_can_edit_page = true;

function add_shortcode( $tag, $callback ) {
    global $ashbpg_test_shortcodes;
    $ashbpg_test_shortcodes[ $tag ] = $callback;
}

function add_action( $hook, $callback ) {}

function sanitize_key( $value ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_hex_color( $color ) {
    return preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $color ) ? $color : null;
}

function sanitize_html_class( $class ) {
    return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
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
    global $ashbpg_test_preview;
    return $ashbpg_test_preview;
}

function current_user_can() {
    global $ashbpg_test_can_edit_page;
    return $ashbpg_test_can_edit_page;
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
    global $ashbpg_test_meta;
    return isset( $ashbpg_test_meta[ $post_id ][ $key ] ) ? $ashbpg_test_meta[ $post_id ][ $key ] : '';
}

function wp_kses_post( $text ) {
    return $text;
}

function wp_strip_all_tags( $text ) {
    return strip_tags( $text );
}

class WP_Query {
    public function __construct( $args ) {
        global $ashbpg_test_query_args;
        $ashbpg_test_query_args = $args;
    }

    public function have_posts() {
        return false;
    }
}

require dirname( __DIR__ ) . '/includes/shortcode.php';

ashbpg_register_shortcodes();
if ( empty( $ashbpg_test_shortcodes['ashbi_portfolio'] ) || empty( $ashbpg_test_shortcodes['pgtk_work'] ) ) {
    fwrite( STDERR, "The shortcode and its 1.0 alias must both be registered.\n" );
    exit( 1 );
}

$ashbpg_test_preview = true;
$markup = ashbpg_work_shortcode(
    array(
        'collection'     => 'Campaigns',
        'artist'         => 'Creator',
        'heading'        => 'Selected <script>work',
        'variant'        => 'artist',
        'posts_per_page' => '6',
    )
);

if ( array( 'publish', 'draft', 'pending', 'future', 'private' ) !== $ashbpg_test_query_args['post_status'] ) {
    fwrite( STDERR, "Authenticated previews must include editable draft portfolio records.\n" );
    exit( 1 );
}

$tax_query = $ashbpg_test_query_args['tax_query'];
if ( 'AND' !== $tax_query['relation'] || 'campaigns' !== $tax_query[0]['terms'] || 'creator' !== $tax_query[1]['terms'] ) {
    fwrite( STDERR, "Artist portfolio query was not scoped to the requested collection and artist.\n" );
    exit( 1 );
}

if ( false === strpos( $markup, 'ashbpg-container--artist' ) || false === strpos( $markup, '>Selected work<' ) || false !== strpos( $markup, '<script>' ) ) {
    fwrite( STDERR, "Artist portfolio markup did not preserve the safe visual contract.\n" );
    exit( 1 );
}

if ( array() !== ashbpg_get_caption_lines( 3729 ) ) {
    fwrite( STDERR, "Temporary grid labels must not leak into the modal credits.\n" );
    exit( 1 );
}

if ( '' !== ashbpg_get_artist_grid_subtitle( 3729 ) ) {
    fwrite( STDERR, "Missing credits must not create a placeholder subtitle.\n" );
    exit( 1 );
}

$ashbpg_test_meta[3729] = array(
    'agency'   => '<strong>Confirmed Agency</strong>',
    'client'   => 'Example client',
    'director' => 'Director Name',
);

if ( 'Confirmed Agency' !== ashbpg_get_artist_grid_subtitle( 3729 ) ) {
    fwrite( STDERR, "Artist grid subtitles must prefer the sanitized confirmed agency value.\n" );
    exit( 1 );
}

if ( array( 'Client: Example client', 'Agency: Confirmed Agency', 'Director: Director Name' ) !== ashbpg_get_caption_lines( 3729 ) ) {
    fwrite( STDERR, "Full structured credits must remain available to the project modal.\n" );
    exit( 1 );
}

$shortcode_source = file_get_contents( dirname( __DIR__ ) . '/includes/shortcode.php' );
$style_source     = file_get_contents( dirname( __DIR__ ) . '/assets/css/style.css' );
if ( false === strpos( $shortcode_source, 'class="ashbpg-agency ashbpg-muted"' ) || false !== strpos( $shortcode_source, 'foreach ( $item[\'caption_lines\'] as $credit_line )' ) ) {
    fwrite( STDERR, "Artist cards must render exactly one agency subtitle instead of every modal credit line.\n" );
    exit( 1 );
}

if ( false !== strpos( $style_source, '.ashbpg-container--artist .ashbpg-info {' . "\n" . '    position: absolute;' ) ) {
    fwrite( STDERR, "Artist project information must not be visually hidden.\n" );
    exit( 1 );
}

$plugin_source = file_get_contents( dirname( __DIR__ ) . '/ashbi-portfolio-grid.php' );
if ( false === strpos( $plugin_source, "wp_enqueue_style( 'ashbpg-style' )" ) || false !== strpos( $plugin_source, 'global-fixes' ) ) {
    fwrite( STDERR, "Only the portfolio assets should load for pages containing the grid shortcode.\n" );
    exit( 1 );
}

$ashbpg_test_preview = false;
ashbpg_work_shortcode( array( 'artist' => 'creator' ) );
if ( array( 'publish' ) !== $ashbpg_test_query_args['post_status'] ) {
    fwrite( STDERR, "Public portfolio renders must not include draft records.\n" );
    exit( 1 );
}

// Shared Ashbi look: look, theme, accent, columns and layout.
$styled = ashbpg_work_shortcode(
    array(
        'layout'  => 'bento',
        'look'  => 'glass',
        'theme'   => 'dark',
        'accent'  => '#e4572e',
        'columns' => '4',
        'class'   => 'my-grid <bad>',
    )
);
foreach ( array( 'ashbpg-ui', 'data-ashbpg-look="glass"', 'data-ashbpg-theme="dark"', '--ashbpg-accent:#e4572e;--ashbpg-cols:4', 'ashbpg-layout-bento', 'my-grid' ) as $needle ) {
    if ( false === strpos( $styled, $needle ) ) {
        fwrite( STDERR, "Styled grid is missing {$needle}.\n" );
        exit( 1 );
    }
}
if ( false !== strpos( $styled, '<bad>' ) ) {
    fwrite( STDERR, "Custom classes must be sanitized.\n" );
    exit( 1 );
}

$fallback = ashbpg_style_settings( array( 'layout' => 'carousel', 'look' => 'neon', 'theme' => 'sepia', 'accent' => 'red;}', 'columns' => '99' ) );
if ( array( 'grid', 'minimal', 'light', '#2457ff', 6 ) !== array( $fallback['layout'], $fallback['look'], $fallback['theme'], $fallback['accent'], $fallback['columns'] ) ) {
    fwrite( STDERR, "Unknown style values must fall back to safe defaults.\n" );
    exit( 1 );
}

ashbpg_work_shortcode( array( 'limit' => '0' ) );
if ( -1 !== $ashbpg_test_query_args['posts_per_page'] ) {
    fwrite( STDERR, "limit=0 must mean all projects.\n" );
    exit( 1 );
}
ashbpg_work_shortcode( array( 'limit' => '4' ) );
if ( 4 !== $ashbpg_test_query_args['posts_per_page'] ) {
    fwrite( STDERR, "limit must set how many projects show.\n" );
    exit( 1 );
}

echo "Portfolio shortcode contract passed.\n";
