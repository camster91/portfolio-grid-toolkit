<?php
/**
 * Contract tests for the Grid Studio preview input.
 */

define( 'ABSPATH', __DIR__ );
define( 'ASHBPG_PATH', dirname( __DIR__ ) . '/' );

function add_action() {}
function add_filter() {}
function plugin_basename( $file ) { return basename( $file ); }
function __( $text ) { return $text; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_hex_color( $color ) { return preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $color ) ? $color : null; }

require dirname( __DIR__ ) . '/includes/admin.php';

$atts = ashbpg_preview_atts(
    array(
        'layout'     => 'Bento<script>',
        'accent'     => 'javascript:alert(1)',
        'columns'    => '40',
        'limit'      => '-5',
        'heading'    => '<b>Work</b>',
        'filters'    => '1',
        'post_type'  => 'page',
        'collection' => array( 'x' ),
    )
);

$expected = array(
    'layout'  => 'bentoscript',
    'accent'  => '',
    'columns' => '6',
    'heading' => 'Work',
    'filters' => 'show',
);

if ( $expected !== $atts ) {
    fwrite( STDERR, "Studio preview input was not cleaned as expected:\n" . var_export( $atts, true ) . "\n" );
    exit( 1 );
}

if ( 'hide' !== ashbpg_preview_atts( array( 'filters' => '0' ) )['filters'] || 'hide' !== ashbpg_preview_atts( 'nope' )['filters'] ) {
    fwrite( STDERR, "Filters must default to hidden.\n" );
    exit( 1 );
}

$source = file_get_contents( dirname( __DIR__ ) . '/includes/admin.php' );
if ( false === strpos( $source, "check_ajax_referer( 'ashbpg_preview', 'nonce' )" ) || false === strpos( $source, "current_user_can( 'edit_posts' )" ) ) {
    fwrite( STDERR, "The preview endpoint must check its nonce and capability.\n" );
    exit( 1 );
}

echo "Grid Studio preview contract passed.\n";
