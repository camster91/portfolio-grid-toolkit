<?php
/**
 * Portfolio content model and editor fields.
 *
 * Existing post type registrations are preserved; a fallback is supplied when
 * the site does not already provide one.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'ashbpg_register_portfolio_post_type', 20 );
function ashbpg_register_portfolio_post_type() {
    if ( ! post_type_exists( 'portfolio' ) ) {
        $labels = array(
            'name'                  => __( 'Portfolios', 'ashbi-portfolio-grid' ),
            'singular_name'         => __( 'Portfolio Project', 'ashbi-portfolio-grid' ),
            'menu_name'             => __( 'Portfolios', 'ashbi-portfolio-grid' ),
            'name_admin_bar'        => __( 'Portfolio Project', 'ashbi-portfolio-grid' ),
            'add_new'               => __( 'Add Project', 'ashbi-portfolio-grid' ),
            'add_new_item'          => __( 'Add Portfolio Project', 'ashbi-portfolio-grid' ),
            'new_item'              => __( 'New Portfolio Project', 'ashbi-portfolio-grid' ),
            'edit_item'             => __( 'Edit Portfolio Project', 'ashbi-portfolio-grid' ),
            'view_item'             => __( 'View Portfolio Project', 'ashbi-portfolio-grid' ),
            'all_items'             => __( 'All Portfolio Projects', 'ashbi-portfolio-grid' ),
            'search_items'          => __( 'Search Portfolio Projects', 'ashbi-portfolio-grid' ),
            'not_found'             => __( 'No portfolio projects found.', 'ashbi-portfolio-grid' ),
            'not_found_in_trash'    => __( 'No portfolio projects found in Trash.', 'ashbi-portfolio-grid' ),
            'featured_image'        => __( 'Project Thumbnail', 'ashbi-portfolio-grid' ),
            'set_featured_image'    => __( 'Set project thumbnail', 'ashbi-portfolio-grid' ),
            'remove_featured_image' => __( 'Remove project thumbnail', 'ashbi-portfolio-grid' ),
        );

        register_post_type(
            'portfolio',
            array(
                'labels'             => $labels,
                'public'             => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'show_in_admin_bar'  => true,
                'show_in_rest'       => true,
                'publicly_queryable' => false,
                'has_archive'        => false,
                'rewrite'            => false,
                'menu_icon'          => 'dashicons-format-video',
                'menu_position'      => 20,
                'supports'           => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
                'taxonomies'         => array( 'post_tag' ),
                'capability_type'    => 'post',
                'map_meta_cap'       => true,
                'delete_with_user'   => false,
                'exclude_from_search' => true,
            )
        );
    }

    register_taxonomy_for_object_type( 'post_tag', 'portfolio' );
    ashbpg_register_portfolio_taxonomies();
    ashbpg_register_portfolio_meta();
}

/**
 * Collections group projects by discipline or theme; Artists link projects to
 * an optional creator. Both taxonomies are editor-managed and have no defaults.
 */
function ashbpg_register_portfolio_taxonomies() {
    if ( ! taxonomy_exists( 'portfolio_collection' ) ) {
        register_taxonomy(
            'portfolio_collection',
            array( 'portfolio' ),
            array(
                'labels'            => array(
                    'name'          => __( 'Collections', 'ashbi-portfolio-grid' ),
                    'singular_name' => __( 'Collection', 'ashbi-portfolio-grid' ),
                    'menu_name'     => __( 'Collections', 'ashbi-portfolio-grid' ),
                    'all_items'     => __( 'All Collections', 'ashbi-portfolio-grid' ),
                    'edit_item'     => __( 'Edit Collection', 'ashbi-portfolio-grid' ),
                    'add_new_item'  => __( 'Add Collection', 'ashbi-portfolio-grid' ),
                    'search_items'  => __( 'Search Collections', 'ashbi-portfolio-grid' ),
                ),
                'public'            => false,
                'publicly_queryable' => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'hierarchical'      => true,
                'rewrite'           => false,
            )
        );
    } else {
        register_taxonomy_for_object_type( 'portfolio_collection', 'portfolio' );
    }

    if ( ! taxonomy_exists( 'portfolio_artist' ) ) {
        register_taxonomy(
            'portfolio_artist',
            array( 'portfolio' ),
            array(
                'labels'            => array(
                    'name'          => __( 'Artists', 'ashbi-portfolio-grid' ),
                    'singular_name' => __( 'Artist', 'ashbi-portfolio-grid' ),
                    'menu_name'     => __( 'Artists', 'ashbi-portfolio-grid' ),
                    'all_items'     => __( 'All Artists', 'ashbi-portfolio-grid' ),
                    'edit_item'     => __( 'Edit Artist', 'ashbi-portfolio-grid' ),
                    'add_new_item'  => __( 'Add Artist', 'ashbi-portfolio-grid' ),
                    'search_items'  => __( 'Search Artists', 'ashbi-portfolio-grid' ),
                ),
                'public'            => false,
                'publicly_queryable' => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'hierarchical'      => false,
                'rewrite'           => false,
            )
        );
    } else {
        register_taxonomy_for_object_type( 'portfolio_artist', 'portfolio' );
    }
}

function ashbpg_register_portfolio_meta() {
    $fields = array(
        'subtitle'       => 'string',
        'video_link'     => 'string',
        'client'         => 'string',
        'agency'         => 'string',
        'director'       => 'string',
        'editor'         => 'string',
        'production_credit'  => 'string',
    );

    foreach ( $fields as $key => $type ) {
        register_post_meta(
            'portfolio',
            $key,
            array(
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'video_link' === $key ? 'esc_url_raw' : 'sanitize_text_field',
                'auth_callback'     => 'ashbpg_can_edit_portfolio_meta',
            )
        );
    }
}

function ashbpg_can_edit_portfolio_meta( $allowed, $meta_key, $post_id ) {
    return $post_id
        ? current_user_can( 'edit_post', (int) $post_id )
        : current_user_can( 'edit_posts' );
}

add_action( 'add_meta_boxes_portfolio', 'ashbpg_add_portfolio_meta_box' );
function ashbpg_add_portfolio_meta_box() {
    add_meta_box(
        'ashbpg-project-details',
        __( 'Project Details', 'ashbi-portfolio-grid' ),
        'ashbpg_render_portfolio_meta_box',
        'portfolio',
        'normal',
        'high'
    );
}

function ashbpg_render_portfolio_meta_box( $post ) {
    wp_nonce_field( 'ashbpg_save_project_details', 'ashbpg_project_details_nonce' );

    $fields = array(
        'subtitle'      => array( __( 'Grid subtitle / agency', 'ashbi-portfolio-grid' ), 'text', __( 'Shown directly beneath the project title.', 'ashbi-portfolio-grid' ) ),
        'video_link'    => array( __( 'Main video URL', 'ashbi-portfolio-grid' ), 'url', __( 'Vimeo, YouTube, or a direct MP4 URL.', 'ashbi-portfolio-grid' ) ),
        'client'        => array( __( 'Client credit', 'ashbi-portfolio-grid' ), 'text', '' ),
        'agency'        => array( __( 'Agency credit', 'ashbi-portfolio-grid' ), 'text', '' ),
        'director'      => array( __( 'Director credit', 'ashbi-portfolio-grid' ), 'text', '' ),
        'editor'        => array( __( 'Editor credit', 'ashbi-portfolio-grid' ), 'text', '' ),
        'production_credit' => array( __( 'Additional production credit', 'ashbi-portfolio-grid' ), 'text', '' ),
    );

    echo '<table class="form-table" role="presentation"><tbody>';
    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        echo '<tr>';
        echo '<th scope="row"><label for="ashbpg-' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th>';
        echo '<td><input class="regular-text" id="ashbpg-' . esc_attr( $key ) . '" name="ashbpg_project[' . esc_attr( $key ) . ']" type="' . esc_attr( $field[1] ) . '" value="' . esc_attr( $value ) . '">';
        if ( $field[2] ) {
            echo '<p class="description">' . esc_html( $field[2] ) . '</p>';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="description">' . esc_html__( 'Use the Featured Image panel for the project thumbnail. Assign its Collection and Artist in the editor sidebar, then publish it when it is ready to appear on the associated portfolio page.', 'ashbi-portfolio-grid' ) . '</p>';
}

add_action( 'save_post_portfolio', 'ashbpg_save_portfolio_meta', 10, 2 );
function ashbpg_save_portfolio_meta( $post_id, $post ) {
    if ( ! isset( $_POST['ashbpg_project_details_nonce'] ) ) {
        return;
    }

    $nonce = sanitize_text_field( wp_unslash( $_POST['ashbpg_project_details_nonce'] ) );
    if ( ! wp_verify_nonce( $nonce, 'ashbpg_save_project_details' ) ) {
        return;
    }

    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! $post || 'portfolio' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $submitted = isset( $_POST['ashbpg_project'] ) && is_array( $_POST['ashbpg_project'] )
        ? wp_unslash( $_POST['ashbpg_project'] )
        : array();

    $fields = array( 'subtitle', 'video_link', 'client', 'agency', 'director', 'editor', 'production_credit' );
    foreach ( $fields as $key ) {
        $raw_value = isset( $submitted[ $key ] ) && is_scalar( $submitted[ $key ] ) ? (string) $submitted[ $key ] : '';
        $value     = 'video_link' === $key ? esc_url_raw( $raw_value ) : sanitize_text_field( $raw_value );

        if ( '' === $value ) {
            delete_post_meta( $post_id, $key );
        } else {
            update_post_meta( $post_id, $key, $value );
        }
    }
}

add_filter( 'manage_portfolio_posts_columns', 'ashbpg_portfolio_columns' );
function ashbpg_portfolio_columns( $columns ) {
    $updated = array();
    foreach ( $columns as $key => $label ) {
        $updated[ $key ] = $label;
        if ( 'title' === $key ) {
            $updated['ashbpg_thumbnail'] = __( 'Thumbnail', 'ashbi-portfolio-grid' );
            $updated['ashbpg_video']     = __( 'Video', 'ashbi-portfolio-grid' );
        }
    }
    return $updated;
}

add_action( 'manage_portfolio_posts_custom_column', 'ashbpg_render_portfolio_column', 10, 2 );
function ashbpg_render_portfolio_column( $column, $post_id ) {
    if ( 'ashbpg_thumbnail' === $column ) {
        echo get_the_post_thumbnail( $post_id, array( 80, 45 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    if ( 'ashbpg_video' === $column ) {
        $video_url = get_post_meta( $post_id, 'video_link', true );
        echo $video_url
            ? '<span class="dashicons dashicons-yes-alt" aria-label="' . esc_attr__( 'Video configured', 'ashbi-portfolio-grid' ) . '"></span>'
            : '<span class="dashicons dashicons-warning" aria-label="' . esc_attr__( 'Video missing', 'ashbi-portfolio-grid' ) . '"></span>';
    }
}
