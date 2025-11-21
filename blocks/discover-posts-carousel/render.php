<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_title  = isset( $attributes['sectionTitle'] ) ? sanitize_text_field( $attributes['sectionTitle'] ) : 'Explore';
$view_all_label = isset( $attributes['viewAllLabel'] ) ? sanitize_text_field( $attributes['viewAllLabel'] ) : 'View all';
$view_all_url   = isset( $attributes['viewAllUrl'] ) ? esc_url_raw( $attributes['viewAllUrl'] ) : home_url( '/blog/' );
$posts_to_show  = isset( $attributes['postsToShow'] ) ? (int) $attributes['postsToShow'] : 9;
$metrics_cutoff = isset( $attributes['metricsCutoffDays'] ) ? (int) $attributes['metricsCutoffDays'] : 7;
$blog_render    = isset( $attributes['blogRender'] ) ? sanitize_key( $attributes['blogRender'] ) : 'none';

if ( $posts_to_show <= 0 ) {
	$posts_to_show = 9;
}
if ( $metrics_cutoff <= 0 ) {
	$metrics_cutoff = 7;
}
if ( ! in_array( $blog_render, [ 'new', 'old', 'none' ], true ) ) {
	$blog_render = 'none';
}

// Date filtering based on blogRender
$date_query = [];
$relative   = '-' . $metrics_cutoff . ' days';

if ( 'new' === $blog_render ) {
	// posts from last N days
	$date_query[] = [
		'after'     => $relative,
		'inclusive' => true,
		'column'    => 'post_date',
	];
} elseif ( 'old' === $blog_render ) {
	// posts older than N days
	$date_query[] = [
		'before'    => $relative,
		'inclusive' => false,
		'column'    => 'post_date',
	];
}

$query_args = [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $posts_to_show,
	'orderby'        => 'date',
	'order'          => 'DESC',
];

if ( ! empty( $date_query ) ) {
	$query_args['date_query'] = $date_query;
}

// Exclude posts in category slug "imagepost"
$image_cat = get_category_by_slug( 'imagepost' );
if ( $image_cat && ! is_wp_error( $image_cat ) ) {
    $query_args['category__not_in'] = [ (int) $image_cat->term_id ];
}


$query = new WP_Query( $query_args );

if ( ! $query->have_posts() ) {
	return '';
}

$total_posts = (int) $query->post_count;
// For responsive pages we start with "worst case": one dot per card
$total_pages = max( 1, $total_posts );

$instance_id = 'discover-carousel-' . wp_generate_uuid4();


ob_start();
?>

<section id="<?php echo esc_attr( $instance_id ); ?>"
         class="discover-carousel child-block"
         data-pages="<?php echo esc_attr( $total_pages ); ?>">

	<div class="discover-carousel__header">
		<h2 class="discover-carousel__title">
			<?php echo esc_html( $section_title ); ?>
		</h2>

		<?php if ( $view_all_url ) : ?>
			<a class="discover-carousel__viewall" href="<?php echo esc_url( $view_all_url ); ?>">
                <span class="discover-carousel__viewall-label">
                    <?php echo esc_html( $view_all_label ); ?>
                </span>
                <span class="discover-carousel__viewall-icon">&rsaquo;</span>
            </a>

		<?php endif; ?>
	</div>

	<div class="discover-carousel__shell">
		<button type="button"
		        class="discover-carousel__arrow discover-carousel__arrow--prev"
		        aria-label="Previous">
			<span>&lsaquo;</span>
		</button>

		<div class="discover-carousel__viewport">
			<div class="discover-carousel__track">

				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$post_id = get_the_ID();

					$thumb_url = get_the_post_thumbnail_url( $post_id, 'medium_large' );

					$author_id   = get_post_field( 'post_author', $post_id );
					$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';

					// Metrics
					$likes_raw = get_post_meta( $post_id, 'likes', true );
					$views_raw = get_post_meta( $post_id, 'views', true );
					$likes     = $likes_raw !== '' ? (int) $likes_raw : 0;
					$views     = $views_raw !== '' ? (int) $views_raw : 0;

					$now_ts      = current_time( 'timestamp' );
					$posted_ts   = get_the_time( 'U', $post_id );
					$age_days    = floor( ( $now_ts - $posted_ts ) / DAY_IN_SECONDS );
					$time_ago    = human_time_diff( $posted_ts, $now_ts ) . ' ago';
					$show_metrics = ( $age_days <= $metrics_cutoff );

					$categories  = get_the_category( $post_id );
					$cat_label   = $categories ? $categories[0]->name : '';
					?>
					<article class="discover-card">
						<a href="<?php the_permalink(); ?>" class="discover-card__link">
							<div class="discover-card__thumb">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>"
									     alt="<?php echo esc_attr( get_the_title() ); ?>"
									     class="discover-card__img" />
								<?php else : ?>
									<div class="discover-card__thumb-placeholder">
										<i class="far fa-image"></i>
									</div>
								<?php endif; ?>
							</div>

							<div class="discover-card__body">
								<?php if ( $cat_label ) : ?>
									<div class="discover-card__category">
										<?php echo esc_html( $cat_label ); ?>
									</div>
								<?php endif; ?>

								<h3 class="discover-card__title">
									<?php the_title(); ?>
								</h3>

								<p class="discover-card__excerpt">
									<?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?>
								</p>

								<?php if ( $author_name ) : ?>
									<p class="discover-card__meta-author">
										<span class="discover-card__meta-label">Posted By:</span>
										<?php echo esc_html( $author_name ); ?>
									</p>
								<?php endif; ?>

								<div class="discover-card__meta-footer">
									<?php if ( $show_metrics ) : ?>
										<span><?php echo esc_html( $likes ); ?> likes</span>
										<span><?php echo esc_html( $views ); ?> Views</span>
										<span class="discover-card__meta-muted">
											<?php echo esc_html( $time_ago ); ?>
										</span>
									<?php else : ?>
										<span class="discover-card__meta-muted">
											<?php echo esc_html( $time_ago ); ?>
										</span>
									<?php endif; ?>
								</div>
							</div>
						</a>
					</article>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>

			</div>
		</div>

		<button type="button"
		        class="discover-carousel__arrow discover-carousel__arrow--next"
		        aria-label="Next">
			<span>&rsaquo;</span>
		</button>
	</div>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="discover-carousel__dots" role="tablist">
			<?php for ( $i = 0; $i < $total_pages; $i++ ) : ?>
				<button type="button"
				        class="discover-carousel__dot <?php echo 0 === $i ? 'is-active' : ''; ?>"
				        data-page="<?php echo esc_attr( $i ); ?>"
				        aria-label="<?php echo esc_attr( 'Go to slide ' . ( $i + 1 ) ); ?>">
				</button>
			<?php endfor; ?>
		</div>
	<?php endif; ?>

</section>

<script>
(function() {
    const root     = document.getElementById('<?php echo esc_js( $instance_id ); ?>');
    if (!root) return;

    const viewport = root.querySelector('.discover-carousel__viewport');
    const track    = root.querySelector('.discover-carousel__track');
    const cards    = root.querySelectorAll('.discover-card');
    if (!viewport || !track || !cards.length) return;

    const prevBtn  = root.querySelector('.discover-carousel__arrow--prev');
    const nextBtn  = root.querySelector('.discover-carousel__arrow--next');
    const dotsWrap = root.querySelector('.discover-carousel__dots');
    const dots     = dotsWrap ? Array.from(dotsWrap.querySelectorAll('.discover-carousel__dot')) : [];

    let currentPage = 0;
    let totalPages  = 1;
    let perPage     = 1;

    function updateDots() {
        if (!dots.length) return;
        dots.forEach(function(dot, index) {
            dot.classList.toggle('is-active', index === currentPage);
        });
    }

    function goToPage(pageIndex, smooth = true) {
        if (!viewport) return;
        const maxPage = Math.max(0, totalPages - 1);
        currentPage   = Math.max(0, Math.min(maxPage, pageIndex));

        // one "page" is exactly the viewport width
        const offset  = viewport.clientWidth * currentPage;
        viewport.scrollTo({ left: offset, behavior: smooth ? 'smooth' : 'auto' });
        updateDots();
    }

    function recalc() {
        if (!cards.length) return;

        const vw        = viewport.clientWidth;
        const cardRect  = cards[0].getBoundingClientRect();
        const cardWidth = cardRect.width || vw;

        // how many cards fit per page (gap is already baked into the % widths)
        perPage   = Math.max(1, Math.round(vw / cardWidth));
        totalPages = Math.max(1, Math.ceil(cards.length / perPage));

        // normalize dots
        if (dots.length) {
            dots.forEach(function(dot, index) {
                if (index < totalPages) {
                    dot.style.display = 'inline-block';
                    dot.dataset.page  = String(index);
                } else {
                    dot.style.display = 'none';
                }
            });
        }

        // snap to valid page without animation
        goToPage(Math.min(currentPage, totalPages - 1), false);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            goToPage(currentPage + 1);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            goToPage(currentPage - 1);
        });
    }

    if (dots.length) {
        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                const page = parseInt(dot.dataset.page || '0', 10) || 0;
                goToPage(page);
            });
        });
    }

    window.addEventListener('resize', recalc);

    // initial layout
    recalc();
})();
</script>


