<?php
/**
 * Shortcode to display a portfolio grid from managed project records.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode names. `ashbi_portfolio` is the current name; `pgtk_work` keeps
 * pages built with Portfolio Grid Toolkit 1.0 working.
 *
 * @return string[]
 */
function ashbpg_shortcode_tags() {
    return array( 'ashbi_portfolio', 'pgtk_work' );
}

add_action( 'init', 'ashbpg_register_shortcodes' );
function ashbpg_register_shortcodes() {
    foreach ( ashbpg_shortcode_tags() as $tag ) {
        add_shortcode( $tag, 'ashbpg_work_shortcode' );
    }
}

/**
 * Shared look settings, cleaned for output.
 *
 * @param array $atts Raw shortcode attributes.
 * @return array{layout:string,columns:int,look:string,theme:string,accent:string,class:string}
 */
function ashbpg_style_settings( $atts ) {
    $layout = sanitize_key( (string) ( $atts['layout'] ?? 'grid' ) );
    $look = sanitize_key( (string) ( $atts['look'] ?? 'minimal' ) );
    $theme  = sanitize_key( (string) ( $atts['theme'] ?? 'light' ) );
    $accent = sanitize_hex_color( (string) ( $atts['accent'] ?? '' ) );
    $class  = implode( ' ', array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', (string) ( $atts['class'] ?? '' ) ) ) ) );

    return array(
        'layout'  => in_array( $layout, array( 'grid', 'bento', 'list' ), true ) ? $layout : 'grid',
        'columns' => max( 1, min( 6, (int) ( $atts['columns'] ?? 3 ) ) ),
        'look'  => in_array( $look, array( 'minimal', 'editorial', 'bold', 'glass' ), true ) ? $look : 'minimal',
        'theme'   => in_array( $theme, array( 'light', 'dark', 'auto' ), true ) ? $theme : 'light',
        'accent'  => $accent ? $accent : '#2457ff',
        'class'   => $class,
    );
}

/**
 * Render the portfolio grid and its per-instance video dialog.
 *
 * Supported attributes:
 * - posts_per_page: number of published portfolio posts to show.
 * - category: optional post_tag slug to scope the query.
 * - collection: optional Collection slug.
 * - artist: optional Artist slug.
 * - heading: optional visible page heading.
 * - variant: work|artist. Artist displays the agency credit when supplied.
 * - filters: show|hide the post_tag filter controls.
 */
function ashbpg_work_shortcode( $atts ) {
    static $instance = 0;
    $instance++;

    $instance_id      = 'ashbpg-work-' . $instance;
    $modal_id         = $instance_id . '-modal';
    $modal_title_id   = $modal_id . '-title';
    $modal_caption_id = $modal_id . '-caption';

    // Prevent loading scripts and breaking layout if we are inside the
    // Elementor editor. The front-end preview remains available on the page
    // preview route.
    if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        return '<div class="ashbpg-ui"><div class="ashbpg-placeholder"><strong>' . esc_html__( 'Ashbi Portfolio Grid', 'ashbi-portfolio-grid' ) . '</strong>' . esc_html__( 'View the page preview to see the grid.', 'ashbi-portfolio-grid' ) . '</div></div>';
    }

    $atts = shortcode_atts(
        array(
            'posts_per_page' => -1,
            'limit'          => '',
            'layout'         => 'grid',
            'columns'        => 3,
            'look'         => 'minimal',
            'theme'          => 'light',
            'accent'         => '',
            'class'          => '',
            'category'       => '',
            'collection'     => '',
            'artist'         => '',
            'heading'        => '',
            'variant'        => 'work',
            'filters'        => 'hide',
        ),
        $atts,
        'ashbi_portfolio'
    );
    $style = ashbpg_style_settings( $atts );
    if ( (int) $atts['limit'] > 0 ) {
        $atts['posts_per_page'] = (int) $atts['limit'];
    }

    $category       = sanitize_title( $atts['category'] );
    $collection     = sanitize_title( $atts['collection'] );
    $artist         = sanitize_title( $atts['artist'] );
    $heading        = sanitize_text_field( $atts['heading'] );
    $variant        = 'artist' === sanitize_title( $atts['variant'] ) ? 'artist' : 'work';
    $posts_per_page = (int) $atts['posts_per_page'];
    if ( -1 !== $posts_per_page ) {
        $posts_per_page = max( 1, min( 100, $posts_per_page ) );
    }
    $show_filters = in_array(
        strtolower( sanitize_text_field( $atts['filters'] ) ),
        array( 'show', 'true', 'yes' ),
        true
    );

    $post_status = array( 'publish' );
    if ( is_preview() && current_user_can( 'edit_posts' ) ) {
        $post_status = array( 'publish', 'draft', 'pending', 'future', 'private' );
    }

    $args = array(
        'post_type'      => 'portfolio',
        'posts_per_page' => $posts_per_page,
        'orderby'        => 'menu_order',
        'order'          => 'DESC',
        'post_status'    => $post_status,
        'no_found_rows'  => true,
    );

    $tax_query = array();
    if ( $category ) {
        $tax_query[] = array(
            'taxonomy' => 'post_tag',
            'field'    => 'slug',
            'terms'    => $category,
        );
    }
    if ( $collection ) {
        $tax_query[] = array(
            'taxonomy' => 'portfolio_collection',
            'field'    => 'slug',
            'terms'    => $collection,
        );
    }
    if ( $artist ) {
        $tax_query[] = array(
            'taxonomy' => 'portfolio_artist',
            'field'    => 'slug',
            'terms'    => $artist,
        );
    }
    if ( $tax_query ) {
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $args );
    $items = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $post_id       = get_the_ID();
            $item_status   = get_post_status( $post_id );
            if ( 'publish' !== $item_status && ! current_user_can( 'edit_post', $post_id ) ) {
                continue;
            }
            $title         = get_the_title();
            $agency        = get_post_meta( $post_id, 'subtitle', true );
            $main_video    = esc_url_raw( trim( (string) get_post_meta( $post_id, 'video_link', true ) ) );
            $thumbnail     = get_the_post_thumbnail_url( $post_id, 'ashbpg-thumb' );

            // Thumbnail: fall back to the legacy ACF `image` field when no
            // featured image is set.
            if ( ! $thumbnail ) {
                $image_id = get_post_meta( $post_id, 'image', true );
                if ( $image_id ) {
                    $thumbnail = wp_get_attachment_image_url( (int) $image_id, 'ashbpg-thumb' );
                }
            }

            $categories     = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'slugs' ) );
            $category_slugs = array();
            if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                $category_slugs = array_values( array_filter( array_map( 'sanitize_html_class', $categories ) ) );
            }

            $items[] = array(
                'id'             => $post_id,
                'title'          => $title ? $title : __( 'Untitled project', 'ashbi-portfolio-grid' ),
                'agency'         => is_scalar( $agency ) ? (string) $agency : '',
                'main_video'     => $main_video,
                'thumbnail'      => $thumbnail ? esc_url( $thumbnail ) : '',
                'caption_lines'  => ashbpg_get_caption_lines( $post_id ),
                'grid_subtitle'  => 'artist' === $variant ? ashbpg_get_artist_grid_subtitle( $post_id ) : '',
                'category_slugs' => $category_slugs,
            );
        }
        wp_reset_postdata();
    }

    $filter_terms = array();
    if ( $show_filters && ! empty( $items ) ) {
        $item_ids     = wp_list_pluck( $items, 'id' );
        $filter_terms = get_terms(
            array(
                'taxonomy'   => 'post_tag',
                'hide_empty' => true,
                'object_ids' => array_map( 'intval', $item_ids ),
            )
        );
        if ( is_wp_error( $filter_terms ) ) {
            $filter_terms = array();
        }
    }

    ob_start();
    ?>
    <section
        class="ashbpg-ui ashbpg-container ashbpg-container--<?php echo esc_attr( $variant ); ?> ashbpg-container--<?php echo esc_attr( $style['layout'] ); ?><?php echo $show_filters ? '' : ' ashbpg-container--filters-hidden'; ?><?php echo $style['class'] ? ' ' . esc_attr( $style['class'] ) : ''; ?>"
        data-ashbpg-instance="<?php echo esc_attr( $instance_id ); ?>"
        data-ashbpg-look="<?php echo esc_attr( $style['look'] ); ?>"
        data-ashbpg-theme="<?php echo esc_attr( $style['theme'] ); ?>"
        style="<?php echo esc_attr( '--ashbpg-accent:' . $style['accent'] . ';--ashbpg-cols:' . $style['columns'] ); ?>"
        aria-label="<?php esc_attr_e( 'Portfolio projects', 'ashbi-portfolio-grid' ); ?>"
    >
        <?php if ( $heading ) : ?>
            <h2 class="ashbpg-page-title"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>
        <?php if ( $show_filters && ! empty( $filter_terms ) ) : ?>
            <nav class="ashbpg-filters ashbpg-pills" aria-label="<?php esc_attr_e( 'Filter portfolio projects', 'ashbi-portfolio-grid' ); ?>">
                <button class="ashbpg-filter-btn ashbpg-pill active" type="button" data-filter="all" aria-pressed="true">
                    <?php esc_html_e( 'All', 'ashbi-portfolio-grid' ); ?>
                </button>
                <?php foreach ( $filter_terms as $term ) : ?>
                    <button class="ashbpg-filter-btn ashbpg-pill" type="button" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false">
                        <?php echo esc_html( $term->name ); ?>
                    </button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="ashbpg-grid ashbpg-layout-<?php echo esc_attr( $style['layout'] ); ?>" aria-label="<?php esc_attr_e( 'Portfolio projects', 'ashbi-portfolio-grid' ); ?>">
            <?php if ( ! empty( $items ) ) : ?>
                <?php foreach ( $items as $item ) :
                    $item_classes = implode( ' ', $item['category_slugs'] );
                    $caption       = implode( "\n", $item['caption_lines'] );
                    $aria_label    = sprintf( __( 'View %s project video', 'ashbi-portfolio-grid' ), $item['title'] );
                    ?>
                    <button
                        class="ashbpg-item <?php echo esc_attr( $item_classes ); ?>"
                        type="button"
                        data-video="<?php echo esc_url( $item['main_video'] ); ?>"
                        data-caption="<?php echo esc_attr( $caption ); ?>"
                        data-title="<?php echo esc_attr( $item['title'] ); ?>"
                        data-modal="<?php echo esc_attr( $modal_id ); ?>"
                        aria-label="<?php echo esc_attr( $aria_label ); ?>"
                        <?php disabled( ! $item['main_video'] ); ?>
                    >
                        <span class="ashbpg-item-inner">
                            <span class="ashbpg-media">
                                <?php if ( $item['thumbnail'] ) : ?>
                                    <img src="<?php echo esc_url( $item['thumbnail'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" class="ashbpg-thumb" loading="lazy" decoding="async">
                                <?php endif; ?>
                            </span>
                            <span class="ashbpg-info">
                                <span class="ashbpg-title"><?php echo esc_html( $item['title'] ); ?></span>
                                <?php if ( 'artist' === $variant && $item['grid_subtitle'] ) : ?>
                                    <span class="ashbpg-agency ashbpg-muted"><?php echo esc_html( $item['grid_subtitle'] ); ?></span>
                                <?php elseif ( $item['agency'] ) : ?>
                                    <span class="ashbpg-agency ashbpg-muted"><?php echo esc_html( $item['agency'] ); ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </button>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="ashbpg-empty"><?php esc_html_e( 'No work items found.', 'ashbi-portfolio-grid' ); ?></p>
            <?php endif; ?>
        </div>

        <dialog class="ashbpg-modal" id="<?php echo esc_attr( $modal_id ); ?>" aria-labelledby="<?php echo esc_attr( $modal_title_id ); ?>" aria-describedby="<?php echo esc_attr( $modal_caption_id ); ?>">
            <div class="ashbpg-modal-overlay" data-ashbpg-dismiss="true"></div>
            <div class="ashbpg-modal-content">
                <button class="ashbpg-modal-close" type="button" aria-label="<?php esc_attr_e( 'Close project video', 'ashbi-portfolio-grid' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <h2 class="ashbpg-modal-title" id="<?php echo esc_attr( $modal_title_id ); ?>"><?php esc_html_e( 'Portfolio project', 'ashbi-portfolio-grid' ); ?></h2>
                <div class="ashbpg-video-container"></div>
                <div class="ashbpg-modal-caption" id="<?php echo esc_attr( $modal_caption_id ); ?>"></div>
            </div>
        </dialog>
    </section>
    <?php

    return ob_get_clean();
}

/**
 * Build plain-text caption lines from structured credits or the legacy field.
 * The browser turns these lines into DOM nodes with textContent, so credits
 * cannot become executable markup through a post meta value.
 */
function ashbpg_get_caption_lines( $post_id ) {
    $credit_fields = array(
        'client'        => 'Client',
        'agency'        => 'Agency',
        'director'      => 'Director',
        'editor'        => 'Editor',
        'production_credit' => 'Production',
    );

    $lines = array();
    foreach ( $credit_fields as $meta_name => $label ) {
        $value = get_post_meta( $post_id, $meta_name, true );
        if ( ! is_scalar( $value ) ) {
            continue;
        }
        $value = trim( wp_strip_all_tags( (string) $value ) );
        if ( $value ) {
            $lines[] = $label . ': ' . $value;
        }
    }

    if ( ! empty( $lines ) ) {
        return $lines;
    }

    // Legacy fallback: parse portfolio_text lines such as "Client: Example".
    $caption_raw       = wp_kses_post( (string) get_post_meta( $post_id, 'portfolio_text', true ) );
    $caption_processed = str_replace( '\\n', "\n", $caption_raw );
    $caption_lines     = preg_split( '/<br\s*\/?>|\r\n|\n|\r/', $caption_processed );

    foreach ( $caption_lines as $line ) {
        $line_text = html_entity_decode( trim( wp_strip_all_tags( $line ) ), ENT_QUOTES, 'UTF-8' );
        if ( preg_match( '/^([^:]+):\s*(.+)$/u', $line_text, $matches ) ) {
            $lines[] = trim( $matches[1] ) . ': ' . trim( $matches[2], " \t\n\r\0\x0B\xA0" );
        } elseif ( $line_text ) {
            $lines[] = trim( $line_text );
        }
    }

    return $lines;
}

/**
 * Return a single confirmed subtitle for an artist project. Never invent a
 * placeholder credit when no agency or subtitle has been supplied.
 */
function ashbpg_get_artist_grid_subtitle( $post_id ) {
    $agency = get_post_meta( $post_id, 'agency', true );
    if ( ! is_scalar( $agency ) || ! trim( (string) $agency ) ) {
        $agency = get_post_meta( $post_id, 'subtitle', true );
    }

    if ( is_scalar( $agency ) ) {
        $agency = trim( wp_strip_all_tags( (string) $agency ) );
    } else {
        $agency = '';
    }

    return $agency;
}
