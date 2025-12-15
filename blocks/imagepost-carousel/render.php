<?php
/**
 * Server-side render for Image Post Carousel block.
 *
 * Shows published posts in category slug "imagepost"
 * as a simple JS carousel with autoplay.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoplay_seconds = isset( $attributes['autoplaySeconds'] )
	? (int) $attributes['autoplaySeconds']
	: 3;

if ( $autoplay_seconds <= 0 ) {
	$autoplay_seconds = 3;
}

$posts_to_show = isset( $attributes['postsToShow'] )
	? (int) $attributes['postsToShow']
	: 10;

if ( $posts_to_show <= 0 ) {
	$posts_to_show = 10;
}

$query = new WP_Query( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $posts_to_show,
	'category_name'  => 'imagepost',
] );

if ( ! $query->have_posts() ) {
	return '<p class="imagepost-carousel__empty">No image posts found.</p>';
}

// Unique instance id for JS scoping
$instance_id = 'imagepost-carousel-' . wp_generate_uuid4();
?>

<div id="<?php echo esc_attr( $instance_id ); ?>"
     class="imagepost-carousel"
     data-autoplay="<?php echo esc_attr( $autoplay_seconds ); ?>">

	<div class="imagepost-carousel__track">
		<?php
		$index = 0;
		while ( $query->have_posts() ) :
			$query->the_post();
			$post_id    = get_the_ID();
			$title      = get_the_title();
			$excerpt    = get_the_excerpt();
			$thumb_url  = get_the_post_thumbnail_url( $post_id, 'large' );
			$is_active  = ( 0 === $index );
			$index++;
			$target_url = add_query_arg(
				[ 'image_id' => $post_id ],
				home_url( '/images-post/' )
			);
			?>
			<article class="imagepost-carousel__slide <?php echo $is_active ? 'is-active' : ''; ?>">
				<a class="imagepost-carousel__link" href="<?php echo esc_url( $target_url ); ?>">
					<div class="imagepost-carousel__media">
						<?php if ( $thumb_url ) : ?>
							<img src="<?php echo esc_url( $thumb_url ); ?>"
							     alt="<?php echo esc_attr( $title ); ?>"
							     class="imagepost-carousel__img" />
						<?php else : ?>
							<div class="imagepost-carousel__placeholder">
								<i class="far fa-image"></i>
							</div>
						<?php endif; ?>
					</div>

					<div class="imagepost-carousel__caption">
						<?php if ( $title ) : ?>
							<h3 class="imagepost-carousel__title">
								<?php echo esc_html( $title ); ?>
							</h3>
						<?php endif; ?>

						<?php if ( $excerpt ) : ?>
							<p class="imagepost-carousel__excerpt">
								<?php echo esc_html( wp_trim_words( $excerpt, 22, '…' ) ); ?>
							</p>
						<?php endif; ?>
					</div>
				</a>
			</article>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	</div>

	<button class="imagepost-carousel__arrow imagepost-carousel__arrow--prev" type="button" aria-label="Previous">
		<span>&lsaquo;</span>
	</button>
	<button class="imagepost-carousel__arrow imagepost-carousel__arrow--next" type="button" aria-label="Next">
		<span>&rsaquo;</span>
	</button>

</div>

<script>
(function() {
	const root = document.getElementById('<?php echo esc_js( $instance_id ); ?>');
	if (!root) return;

	const slides = root.querySelectorAll('.imagepost-carousel__slide');
	if (!slides.length) return;

	let current = 0;
	const delayMs = (parseInt(root.dataset.autoplay, 10) || 3) * 1000;

	const prevBtn = root.querySelector('.imagepost-carousel__arrow--prev');
	const nextBtn = root.querySelector('.imagepost-carousel__arrow--next');

	function showSlide(index) {
		slides[current].classList.remove('is-active');
		current = (index + slides.length) % slides.length;
		slides[current].classList.add('is-active');
	}

	function next() {
		showSlide(current + 1);
	}

	function prev() {
		showSlide(current - 1);
	}

	let timer = null;
	function startAuto() {
		if (timer) clearInterval(timer);
		timer = setInterval(next, delayMs);
	}

	function stopAuto() {
		if (timer) {
			clearInterval(timer);
			timer = null;
		}
	}

	if (nextBtn) {
		nextBtn.addEventListener('click', function() {
			stopAuto();
			next();
			startAuto();
		});
	}
	if (prevBtn) {
		prevBtn.addEventListener('click', function() {
			stopAuto();
			prev();
			startAuto();
		});
	}

	// Pause on hover to avoid UX rage clicking
	root.addEventListener('mouseenter', stopAuto);
	root.addEventListener('mouseleave', startAuto);

	startAuto();
})();
</script>

