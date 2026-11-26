<?php
/**
 * Ashbi Portfolio Grid admin: the Studio page with a live preview.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'ashbpg_register_admin_page', 30 );
function ashbpg_register_admin_page() {
    $GLOBALS['ashbpg_studio_hook'] = add_submenu_page(
        'edit.php?post_type=portfolio',
        __( 'Ashbi Portfolio Grid', 'ashbi-portfolio-grid' ),
        __( 'Grid Studio', 'ashbi-portfolio-grid' ),
        'edit_posts',
        'ashbi-portfolio-grid',
        'ashbpg_render_admin_page'
    );
}

/**
 * Whether the current screen is the Studio page.
 *
 * @param string $hook Admin page hook suffix.
 * @return bool
 */
function ashbpg_is_studio_screen( $hook ) {
    return ! empty( $GLOBALS['ashbpg_studio_hook'] ) && $GLOBALS['ashbpg_studio_hook'] === $hook;
}

add_action( 'admin_enqueue_scripts', 'ashbpg_enqueue_studio' );
function ashbpg_enqueue_studio( $hook ) {
    if ( ! ashbpg_is_studio_screen( $hook ) ) {
        return;
    }

    wp_enqueue_style( 'ashbpg-ui-admin', ASHBPG_URL . 'assets/ashbi-ui/admin.css', array(), ASHBPG_VERSION );
    wp_enqueue_style( 'ashbpg-ui', ASHBPG_URL . 'assets/ashbi-ui/front.css', array(), ASHBPG_VERSION );
    wp_enqueue_style( 'ashbpg-style', ASHBPG_URL . 'assets/css/style.css', array( 'ashbpg-ui' ), ASHBPG_VERSION );
    wp_enqueue_script( 'ashbpg-studio', ASHBPG_URL . 'assets/ashbi-ui/studio.js', array(), ASHBPG_VERSION, true );
    wp_add_inline_script( 'ashbpg-studio', 'window.ashbpgStudio.init(document.getElementById("ashbpg-studio"),' . wp_json_encode( ashbpg_studio_config() ) . ');' );
}

/**
 * Term choices for a Studio select.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $all_label Label for "no filter".
 * @return array<int, array{0:string,1:string}>
 */
function ashbpg_term_options( $taxonomy, $all_label ) {
    $options = array( array( '', $all_label ) );
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return $options;
    }
    $terms = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => 200,
        )
    );
    if ( is_wp_error( $terms ) ) {
        return $options;
    }
    foreach ( $terms as $term ) {
        $options[] = array( $term->slug, $term->name );
    }
    return $options;
}

/**
 * Fields and wording for the shared Ashbi Studio builder.
 *
 * @return array
 */
function ashbpg_studio_config() {
    return array(
        'shortcode' => 'ashbi_portfolio',
        'fields'    => array(
            array(
                'key'     => 'layout',
                'type'    => 'seg',
                'label'   => __( 'Layout', 'ashbi-portfolio-grid' ),
                'default' => 'grid',
                'options' => array(
                    array( 'grid', __( 'Grid', 'ashbi-portfolio-grid' ) ),
                    array( 'bento', __( 'Bento', 'ashbi-portfolio-grid' ) ),
                    array( 'list', __( 'List', 'ashbi-portfolio-grid' ) ),
                ),
            ),
            array(
                'key'     => 'look',
                'type'    => 'preset',
                'label'   => __( 'Look', 'ashbi-portfolio-grid' ),
                'default' => 'minimal',
                'options' => array(
                    array( 'minimal', __( 'Minimal', 'ashbi-portfolio-grid' ), __( 'Hairlines, no shadow', 'ashbi-portfolio-grid' ) ),
                    array( 'editorial', __( 'Editorial', 'ashbi-portfolio-grid' ), __( 'Big type, rules', 'ashbi-portfolio-grid' ) ),
                    array( 'bold', __( 'Bold', 'ashbi-portfolio-grid' ), __( 'Accent cards', 'ashbi-portfolio-grid' ) ),
                    array( 'glass', __( 'Glass', 'ashbi-portfolio-grid' ), __( 'Soft, layered', 'ashbi-portfolio-grid' ) ),
                ),
            ),
            array(
                'key'         => 'accent',
                'type'        => 'swatch',
                'label'       => __( 'Accent', 'ashbi-portfolio-grid' ),
                'customLabel' => __( 'Pick your own color', 'ashbi-portfolio-grid' ),
                'default'     => '#2457ff',
            ),
            array(
                'key'     => 'theme',
                'type'    => 'seg',
                'label'   => __( 'Colors', 'ashbi-portfolio-grid' ),
                'default' => 'light',
                'options' => array(
                    array( 'light', __( 'Match page', 'ashbi-portfolio-grid' ) ),
                    array( 'dark', __( 'Dark', 'ashbi-portfolio-grid' ) ),
                    array( 'auto', __( 'Auto', 'ashbi-portfolio-grid' ) ),
                ),
            ),
            array(
                'key'     => 'columns',
                'type'    => 'seg',
                'label'   => __( 'Columns', 'ashbi-portfolio-grid' ),
                'default' => '3',
                'options' => array( array( '2', '2' ), array( '3', '3' ), array( '4', '4' ) ),
            ),
            array(
                'key'     => 'collection',
                'type'    => 'select',
                'label'   => __( 'Collection', 'ashbi-portfolio-grid' ),
                'default' => '',
                'options' => ashbpg_term_options( 'portfolio_collection', __( 'All collections', 'ashbi-portfolio-grid' ) ),
            ),
            array(
                'key'     => 'artist',
                'type'    => 'select',
                'label'   => __( 'Artist', 'ashbi-portfolio-grid' ),
                'default' => '',
                'options' => ashbpg_term_options( 'portfolio_artist', __( 'All artists', 'ashbi-portfolio-grid' ) ),
            ),
            array(
                'key'     => 'limit',
                'type'    => 'number',
                'label'   => __( 'How many projects (0 = all)', 'ashbi-portfolio-grid' ),
                'default' => '0',
                'min'     => 0,
                'max'     => 100,
            ),
            array(
                'key'     => 'heading',
                'type'    => 'text',
                'label'   => __( 'Heading (optional)', 'ashbi-portfolio-grid' ),
                'default' => '',
            ),
            array(
                'key'     => 'filters',
                'type'    => 'toggle',
                'label'   => __( 'Show tag filter buttons', 'ashbi-portfolio-grid' ),
                'default' => false,
                'on'      => 'show',
                'off'     => 'hide',
            ),
        ),
        'preview'   => array(
            'url'    => admin_url( 'admin-ajax.php' ),
            'action' => 'ashbpg_preview',
            'nonce'  => wp_create_nonce( 'ashbpg_preview' ),
        ),
        'i18n'      => array(
            'copy'         => __( 'Copy shortcode', 'ashbi-portfolio-grid' ),
            'copied'       => __( 'Copied', 'ashbi-portfolio-grid' ),
            'selectToCopy' => __( 'Press Ctrl+C or Cmd+C to copy.', 'ashbi-portfolio-grid' ),
            'failed'       => __( 'The preview could not load. Your settings are still saved in the shortcode.', 'ashbi-portfolio-grid' ),
            'note'         => __( 'Live preview with your projects', 'ashbi-portfolio-grid' ),
            'device'       => __( 'Preview size', 'ashbi-portfolio-grid' ),
            'desktop'      => __( 'Desktop', 'ashbi-portfolio-grid' ),
            'tablet'       => __( 'Tablet', 'ashbi-portfolio-grid' ),
            'phone'        => __( 'Phone', 'ashbi-portfolio-grid' ),
            'background'   => __( 'Page background', 'ashbi-portfolio-grid' ),
            'light'        => __( 'Light page', 'ashbi-portfolio-grid' ),
            'dark'         => __( 'Dark page', 'ashbi-portfolio-grid' ),
            'place'        => __( 'Add to your site', 'ashbi-portfolio-grid' ),
            'placeHelp'    => __( 'Paste it into a Shortcode block, an Elementor Shortcode widget, or any text area that runs shortcodes.', 'ashbi-portfolio-grid' ),
        ),
    );
}

/**
 * Clean Studio settings into shortcode attributes. Only known keys pass.
 *
 * @param mixed $raw Raw request data.
 * @return array<string,string>
 */
function ashbpg_preview_atts( $raw ) {
    $raw  = is_array( $raw ) ? $raw : array();
    $atts = array();
    foreach ( array( 'layout', 'look', 'theme', 'collection', 'artist' ) as $key ) {
        if ( isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ) {
            $atts[ $key ] = sanitize_key( (string) $raw[ $key ] );
        }
    }
    if ( isset( $raw['accent'] ) && is_scalar( $raw['accent'] ) ) {
        $atts['accent'] = (string) sanitize_hex_color( (string) $raw['accent'] );
    }
    if ( isset( $raw['columns'] ) && is_scalar( $raw['columns'] ) ) {
        $atts['columns'] = (string) max( 1, min( 6, (int) $raw['columns'] ) );
    }
    if ( isset( $raw['limit'] ) && is_scalar( $raw['limit'] ) && (int) $raw['limit'] > 0 ) {
        $atts['limit'] = (string) min( 100, (int) $raw['limit'] );
    }
    if ( isset( $raw['heading'] ) && is_scalar( $raw['heading'] ) ) {
        $atts['heading'] = sanitize_text_field( (string) $raw['heading'] );
    }
    $atts['filters'] = ! empty( $raw['filters'] ) && '0' !== (string) $raw['filters'] ? 'show' : 'hide';
    return $atts;
}

add_action( 'wp_ajax_ashbpg_preview', 'ashbpg_ajax_preview' );
function ashbpg_ajax_preview() {
    check_ajax_referer( 'ashbpg_preview', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have permission to preview portfolio grids.', 'ashbi-portfolio-grid' ) ), 403 );
    }
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each known key is sanitized in ashbpg_preview_atts().
    $raw  = isset( $_POST['atts'] ) ? wp_unslash( $_POST['atts'] ) : array();
    $html = ashbpg_work_shortcode( ashbpg_preview_atts( $raw ) );
    wp_send_json_success( array( 'html' => $html ) );
}

function ashbpg_render_admin_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to manage portfolio projects.', 'ashbi-portfolio-grid' ) );
    }

    $counts    = wp_count_posts( 'portfolio' );
    $published = isset( $counts->publish ) ? (int) $counts->publish : 0;
    $drafts    = isset( $counts->draft ) ? (int) $counts->draft : 0;
    ?>
    <div class="wrap ashbpg-studio">
        <div class="ashbpg-studio__header">
            <div class="ashbpg-studio__brand">
                <svg viewBox="0 0 40 40" aria-hidden="true" focusable="false"><rect width="40" height="40" rx="11" fill="#2457ff"></rect><rect x="9" y="9" width="10" height="10" rx="2.5" fill="#fff"></rect><rect x="21" y="9" width="10" height="10" rx="2.5" fill="#fff" opacity=".7"></rect><rect x="9" y="21" width="22" height="10" rx="2.5" fill="#fff" opacity=".85"></rect><circle cx="31" cy="31" r="3" fill="#ffb568"></circle></svg>
                <div>
                    <h1><?php esc_html_e( 'Ashbi Portfolio Grid', 'ashbi-portfolio-grid' ); ?></h1>
                    <p><?php esc_html_e( 'Design your grid, then paste one shortcode anywhere.', 'ashbi-portfolio-grid' ); ?></p>
                </div>
            </div>
            <div class="ashbpg-studio__actions">
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=portfolio' ) ); ?>"><?php esc_html_e( 'All projects', 'ashbi-portfolio-grid' ); ?></a>
                <?php if ( current_user_can( 'edit_others_posts' ) ) : ?>
                    <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=portfolio&page=ashbpg-sort' ) ); ?>"><?php esc_html_e( 'Set order', 'ashbi-portfolio-grid' ); ?></a>
                <?php endif; ?>
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Add project', 'ashbi-portfolio-grid' ); ?></a>
            </div>
        </div>

        <div class="ashbpg-studio__cards">
            <div class="ashbpg-studio__card">
                <span class="ashbpg-studio__stat"><?php echo esc_html( number_format_i18n( $published ) ); ?></span>
                <span class="ashbpg-studio__label"><?php esc_html_e( 'Published projects', 'ashbi-portfolio-grid' ); ?></span>
            </div>
            <div class="ashbpg-studio__card">
                <span class="ashbpg-studio__stat"><?php echo esc_html( number_format_i18n( $drafts ) ); ?></span>
                <span class="ashbpg-studio__label"><?php esc_html_e( 'Drafts', 'ashbi-portfolio-grid' ); ?></span>
            </div>
            <div class="ashbpg-studio__card">
                <ol class="ashbpg-studio__steps">
                    <li><strong><?php esc_html_e( 'Add projects', 'ashbi-portfolio-grid' ); ?></strong><?php esc_html_e( 'Title, featured image, video link and credits.', 'ashbi-portfolio-grid' ); ?></li>
                    <li><strong><?php esc_html_e( 'Design the grid', 'ashbi-portfolio-grid' ); ?></strong><?php esc_html_e( 'Pick a layout, look and accent below.', 'ashbi-portfolio-grid' ); ?></li>
                    <li><strong><?php esc_html_e( 'Paste the shortcode', 'ashbi-portfolio-grid' ); ?></strong><?php esc_html_e( 'Works in any theme, block or Elementor page.', 'ashbi-portfolio-grid' ); ?></li>
                </ol>
            </div>
        </div>

        <div id="ashbpg-studio" class="ashbpg-studio__mount">
            <noscript><p><?php esc_html_e( 'Turn on JavaScript to use the live builder. You can still type the shortcode:', 'ashbi-portfolio-grid' ); ?> <code>[ashbi_portfolio]</code></p></noscript>
        </div>
    </div>
    <?php
}

add_filter( 'plugin_action_links_' . plugin_basename( ASHBPG_PATH . 'ashbi-portfolio-grid.php' ), 'ashbpg_plugin_action_links' );
function ashbpg_plugin_action_links( $links ) {
    $studio_link = '<a href="' . esc_url( admin_url( 'edit.php?post_type=portfolio&page=ashbi-portfolio-grid' ) ) . '">' . esc_html__( 'Grid Studio', 'ashbi-portfolio-grid' ) . '</a>';
    array_unshift( $links, $studio_link );
    return $links;
}
