<?php
/**
 * Portfolio administration screens.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'pgtk_register_admin_page', 30 );
function pgtk_register_admin_page() {
    add_submenu_page(
        'edit.php?post_type=portfolio',
        __( 'Portfolio Overview', 'portfolio-grid-toolkit' ),
        __( 'Overview', 'portfolio-grid-toolkit' ),
        'edit_posts',
        'portfolio-grid-toolkit',
        'pgtk_render_admin_page'
    );
}

function pgtk_render_admin_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to manage portfolio projects.', 'portfolio-grid-toolkit' ) );
    }

    $counts    = wp_count_posts( 'portfolio' );
    $published = isset( $counts->publish ) ? (int) $counts->publish : 0;
    $drafts    = isset( $counts->draft ) ? (int) $counts->draft : 0;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Portfolio Overview', 'portfolio-grid-toolkit' ); ?></h1>
        <p><?php esc_html_e( 'Add, review, publish, and order portfolio projects for any page.', 'portfolio-grid-toolkit' ); ?></p>

        <div class="notice notice-info inline">
            <p>
                <?php
                printf(
                    esc_html__( '%1$d published project(s) and %2$d draft project(s).', 'portfolio-grid-toolkit' ),
                    $published,
                    $drafts
                );
                ?>
            </p>
        </div>

        <p>
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Add Portfolio Project', 'portfolio-grid-toolkit' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Manage Projects', 'portfolio-grid-toolkit' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=portfolio_collection&post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Manage Collections', 'portfolio-grid-toolkit' ); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=portfolio_artist&post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Manage Artists', 'portfolio-grid-toolkit' ); ?></a>
            <?php if ( current_user_can( 'edit_others_posts' ) ) : ?>
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=portfolio&page=pgtk-sort' ) ); ?>"><?php esc_html_e( 'Set Display Order', 'portfolio-grid-toolkit' ); ?></a>
            <?php endif; ?>
        </p>

        <h2><?php esc_html_e( 'Project checklist', 'portfolio-grid-toolkit' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Add a project title and featured image.', 'portfolio-grid-toolkit' ); ?></li>
            <li><?php esc_html_e( 'Enter a grid subtitle, video URL, and available credits in Project Details.', 'portfolio-grid-toolkit' ); ?></li>
            <li><?php esc_html_e( 'Optionally assign a Collection and Artist.', 'portfolio-grid-toolkit' ); ?></li>
            <li><?php esc_html_e( 'Publish the project, then use Set Display Order to place it in its grid.', 'portfolio-grid-toolkit' ); ?></li>
            <li><?php esc_html_e( 'Verify the project modal and responsive grid on the intended page.', 'portfolio-grid-toolkit' ); ?></li>
        </ol>

        <h2><?php esc_html_e( 'Embed', 'portfolio-grid-toolkit' ); ?></h2>
        <p><code>[pgtk_work]</code></p>
        <p class="description"><?php esc_html_e( 'Examples: [pgtk_work collection="campaigns"] or [pgtk_work artist="creator-slug" heading="Selected work" variant="artist" posts_per_page="6"].', 'portfolio-grid-toolkit' ); ?></p>
    </div>
    <?php
}

add_filter( 'plugin_action_links_' . plugin_basename( PGTK_PATH . 'portfolio-grid-toolkit.php' ), 'pgtk_plugin_action_links' );
function pgtk_plugin_action_links( $links ) {
    $manage_link = '<a href="' . esc_url( admin_url( 'edit.php?post_type=portfolio&page=portfolio-grid-toolkit' ) ) . '">' . esc_html__( 'Manage Portfolio', 'portfolio-grid-toolkit' ) . '</a>';
    array_unshift( $links, $manage_link );
    return $links;
}
