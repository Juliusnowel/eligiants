<?php
/**
 * Server-side render for Card Gatherer – Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    return '<p class="card-gatherer__notice">Please log in to view your profile items.</p>';
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

$typeblock = isset( $attributes['typeblock'] ) ? sanitize_key( $attributes['typeblock'] ) : 'posts';
$per_page  = isset( $attributes['postsPerPage'] ) ? (int) $attributes['postsPerPage'] : 9;

if ( ! in_array( $typeblock, [ 'posts', 'drafts', 'images' ], true ) ) {
    $typeblock = 'posts';
}

// Block-specific page query param: cg_posts_page | cg_drafts_page | cg_images_page
$page_param   = 'cg_' . $typeblock . '_page';
$current_page = isset( $_GET[ $page_param ] ) ? max( 1, (int) $_GET[ $page_param ] ) : 1;

// Build query args per type
$query_args = [
    'author'         => $user_id,
    'posts_per_page' => $per_page,
    'paged'          => $current_page,
];

switch ( $typeblock ) {
    case 'images':
        $query_args = array_merge(
            $query_args,
            [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
            ]
        );
        break;

    case 'drafts':
        $query_args = array_merge(
            $query_args,
            [
                'post_type'   => 'post',
                'post_status' => [ 'draft', 'pending' ],
            ]
        );
        break;

    case 'posts':
    default:
        $query_args = array_merge(
            $query_args,
            [
                'post_type'   => 'post',
                'post_status' => 'publish',
            ]
        );
        break;
}

$query       = new WP_Query( $query_args );
$total_pages = (int) $query->max_num_pages;

// Helper: build pagination link keeping other query args
$build_page_link = function ( $page ) use ( $page_param ) {
    $page = max( 1, (int) $page );
    $url  = remove_query_arg( $page_param );
    return esc_url( add_query_arg( $page_param, $page, $url ) );
};

ob_start();
?>

<div class="card-gatherer card-gatherer--type-<?php echo esc_attr( $typeblock ); ?>">
    <div class="card-gatherer__grid">

        <?php if ( 'images' === $typeblock ) : ?>

            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                    <?php
                    $img_id    = get_the_ID();
                    $thumb_url = wp_get_attachment_image_url( $img_id, 'medium' );
                    $full_url  = wp_get_attachment_url( $img_id );
                    $delete_url = add_query_arg(
                        [
                            'action' => 'ileg_delete_image',
                            'img_id' => $img_id,
                            'nonce'  => wp_create_nonce( 'delete_user_image' ),
                        ],
                        admin_url( 'admin-post.php' )
                    );
                    ?>
                    <article class="card-gatherer__item card-gatherer__item--image">
                        <div class="card-gatherer__actions">
                            <a href="<?php echo esc_url( $full_url ); ?>"
                               download
                               class="card-gatherer__btn card-gatherer__btn--ghost">
                                Download
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>"
                               class="card-gatherer__btn card-gatherer__btn--danger"
                               onclick="return confirm('Delete this image?');">
                                Delete
                            </a>
                        </div>

                        <div class="card-gatherer__thumb-wrapper">
                            <?php if ( $thumb_url ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>"
                                     alt=""
                                     class="card-gatherer__thumb-img" />
                            <?php else : ?>
                                <div class="card-gatherer__thumb-placeholder">
                                    <i class="far fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="card-gatherer__empty">You haven't uploaded any images yet.</p>
            <?php endif; ?>

        <?php else : ?>

            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                    <?php
                    $post_id   = get_the_ID();
                    $status    = get_post_status( $post_id );
                    $cats      = get_the_category();
                    $cat_label = $cats ? $cats[0]->name : 'Uncategorized';

                    // Metrics for published posts
                    $likes_raw = get_post_meta( $post_id, 'likes', true );
                    $views_raw = get_post_meta( $post_id, 'views', true );
                    $likes     = $likes_raw !== '' ? (int) $likes_raw : 0;
                    $views     = $views_raw !== '' ? (int) $views_raw : 0;

                    $time_ago = human_time_diff(
                        get_the_time( 'U', $post_id ),
                        current_time( 'timestamp' )
                    ) . ' ago';

                    $last_edited_ago = human_time_diff(
                        get_post_modified_time( 'U', false, $post_id ),
                        current_time( 'timestamp' )
                    ) . ' ago';

                    $edit_url = add_query_arg(
                        [
                            'edit_post' => $post_id,
                        ],
                        home_url( '/community/submit-post/' )
                    );

                    $delete_url = add_query_arg(
                        [
                            'action'  => 'ileg_delete_post',
                            'post_id' => $post_id,
                            'nonce'   => wp_create_nonce( 'delete_user_post' ),
                        ],
                        admin_url( 'admin-post.php' )
                    );
                    ?>

                    <article class="card-gatherer__item card-gatherer__item--post">
                        <div class="card-gatherer__actions">
                            <a href="<?php echo esc_url( $edit_url ); ?>"
                               class="card-gatherer__btn card-gatherer__btn--ghost">
                                Edit
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>"
                               class="card-gatherer__btn card-gatherer__btn--danger"
                               onclick="return confirm('Delete this post?');">
                                Delete
                            </a>
                        </div>

                        <a href="<?php the_permalink(); ?>" class="card-gatherer__thumb-wrapper">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large', [ 'class' => 'card-gatherer__thumb-img' ] ); ?>
                            <?php else : ?>
                                <div class="card-gatherer__thumb-placeholder">
                                    <i class="far fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="card-gatherer__body">
                            <div class="card-gatherer__meta-top">
                                <span class="card-gatherer__category">
                                    <?php echo esc_html( $cat_label ); ?>
                                </span>
                                <span class="card-gatherer__status">
                                    <?php echo esc_html( $status ); ?>
                                </span>
                            </div>

                            <h3 class="card-gatherer__title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h3>

                            <p class="card-gatherer__excerpt">
                                <?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) ); ?>
                            </p>

                            <div class="card-gatherer__meta-bottom">
                                <?php if ( 'posts' === $typeblock ) : ?>
                                    <span class="card-gatherer__metric">
                                        <?php echo esc_html( $likes ); ?> Likes
                                    </span>
                                    <span class="card-gatherer__metric">
                                        <?php echo esc_html( $views ); ?> Views
                                    </span>
                                    <span class="card-gatherer__metric card-gatherer__metric--muted">
                                        <?php echo esc_html( $time_ago ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="card-gatherer__metric">
                                        Last edited <?php echo esc_html( $last_edited_ago ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="card-gatherer__empty">
                    <?php echo 'drafts' === $typeblock
                        ? esc_html__( 'You have no drafts.', 'ileg' )
                        : esc_html__( 'You have no posts yet.', 'ileg' ); ?>
                </p>
            <?php endif; ?>

        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

    </div>

    <?php if ( $total_pages > 1 ) : ?>
        <nav class="card-gatherer__pagination" aria-label="Profile items pagination">
            <?php if ( $current_page > 1 ) : ?>
                <a class="card-gatherer__page-link card-gatherer__page-link--prev"
                   href="<?php echo $build_page_link( $current_page - 1 ); ?>">
                    &laquo; Prev
                </a>
            <?php else : ?>
                <span class="card-gatherer__page-link card-gatherer__page-link--disabled">&laquo; Prev</span>
            <?php endif; ?>

            <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                <?php if ( $i === $current_page ) : ?>
                    <span class="card-gatherer__page-link card-gatherer__page-link--current">
                        <?php echo esc_html( $i ); ?>
                    </span>
                <?php else : ?>
                    <a class="card-gatherer__page-link"
                       href="<?php echo $build_page_link( $i ); ?>">
                        <?php echo esc_html( $i ); ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ( $current_page < $total_pages ) : ?>
                <a class="card-gatherer__page-link card-gatherer__page-link--next"
                   href="<?php echo $build_page_link( $current_page + 1 ); ?>">
                    Next &raquo;
                </a>
            <?php else : ?>
                <span class="card-gatherer__page-link card-gatherer__page-link--disabled">Next &raquo;</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
