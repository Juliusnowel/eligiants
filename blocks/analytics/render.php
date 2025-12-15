<?php
/**
 * Analytics block render template
 *
 * Shows profile-level metrics for the current user (views, likes, posts, images),
 * lightweight charts, top posts, and recent activity pulled from the user's posts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
if ( ! $current_user || ! $current_user->exists() ) {
	return '<div class="wp-block-ileg-analytics ileg-analytics"><p class="ileg-analytics__empty">Please log in to see your analytics.</p></div>';
}

$user_id      = $current_user->ID;
$image_cat    = get_category_by_slug( 'imagepost' );
$image_cat_id = $image_cat ? (int) $image_cat->term_id : 0;
$now          = current_time( 'timestamp' );

// Gather all of the user's posts.
$user_posts = get_posts(
	[
		'author'         => $user_id,
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'numberposts'    => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'suppress_filters' => false,
	]
);

$exclude_image_cat = $image_cat_id ? [ $image_cat_id ] : [];

$total_posts  = 0;
$total_images = 0;
$total_views  = 0;
$total_likes  = 0;

$buckets = [
	'Days'   => [
		'start' => strtotime( '-7 days', $now ),
		'views' => 0,
	],
	'Weeks'  => [
		'start' => strtotime( '-30 days', $now ),
		'views' => 0,
	],
	'Months' => [
		'start' => strtotime( '-180 days', $now ),
		'views' => 0,
	],
];

$recent_likes = 0;

foreach ( $user_posts as $post_id ) {
	$views = (int) get_post_meta( $post_id, 'views', true );
	$likes = (int) get_post_meta( $post_id, 'likes', true );

	$total_views += $views;
	$total_likes += $likes;

	$is_image_post = $image_cat_id && has_category( $image_cat_id, $post_id );
	if ( $is_image_post ) {
		$total_images ++;
	} else {
		$total_posts ++;
	}

	$post_timestamp = get_post_time( 'U', true, $post_id );
	foreach ( $buckets as $key => $bucket ) {
		if ( $post_timestamp >= $bucket['start'] ) {
			$buckets[ $key ]['views'] += $views;
		}
	}

	if ( $post_timestamp >= $buckets['Days']['start'] ) {
		$recent_likes += $likes;
	}
}

$recent_views = $buckets['Days']['views'];
$recent_comments = 0;

if ( $user_posts ) {
	$recent_comments = get_comments(
		[
			'post__in'    => $user_posts,
			'status'      => 'approve',
			'date_query'  => [
				[
					'after' => '1 week ago',
				],
			],
			'count'       => true,
		]
	);
}

// Top posts by views.
$top_posts = get_posts(
	[
		'author'         => $user_id,
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'numberposts'    => 4,
		'meta_key'       => 'views',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'suppress_filters' => false,
		'category__not_in' => $exclude_image_cat,
	]
);

// Top liked posts for the bar chart.
$top_liked_posts = get_posts(
	[
		'author'         => $user_id,
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'numberposts'    => 4,
		'meta_key'       => 'likes',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'suppress_filters' => false,
		'category__not_in' => $exclude_image_cat,
	]
);

$line_labels = array_keys( $buckets );
$line_values = array_values(
	array_map(
		static function ( $bucket ) {
			return (int) $bucket['views'];
		},
		$buckets
	)
);

$line_max    = max( $line_values );
$line_max    = $line_max > 0 ? $line_max : 1;
$line_axis_step = 10;
$line_axis_top  = (int) ceil( max( $line_max, $line_axis_step ) / $line_axis_step ) * $line_axis_step;
$line_ticks     = range( $line_axis_top, 0, -$line_axis_step );
$line_width  = 320;
$line_height = 180;
$line_pad    = 24;

$line_coords = [];
for ( $i = 0, $count = count( $line_values ); $i < $count; $i++ ) {
	$x = $line_pad + ( $i * ( ( $line_width - ( 2 * $line_pad ) ) / max( 1, $count - 1 ) ) );
	$y = $line_height - $line_pad - ( ( $line_values[ $i ] / max( 1, $line_axis_top ) ) * ( $line_height - ( 2 * $line_pad ) ) );
	$line_coords[] = [ 'x' => $x, 'y' => $y ];
}

$path_segments = [];
foreach ( $line_coords as $index => $coords ) {
	$prefix         = $index === 0 ? 'M' : 'L';
	$path_segments[] = sprintf( '%s %.2f %.2f', $prefix, $coords['x'], $coords['y'] );
}

// Bar chart sizing.
$bar_values = [];
foreach ( $top_liked_posts as $post ) {
	$bar_values[] = (int) get_post_meta( $post->ID, 'likes', true );
}

// Prevent warnings if no posts yet.
$bar_max = $bar_values ? max( $bar_values ) : 0;
$bar_max = $bar_max > 0 ? $bar_max : 1;
$bar_axis_step = 5;
$bar_axis_top  = (int) ceil( max( $bar_max, $bar_axis_step ) / $bar_axis_step ) * $bar_axis_step;
$bar_ticks     = range( $bar_axis_top, 0, -$bar_axis_step );

ob_start();
?>
<div class="wp-block-ileg-analytics ileg-analytics">
	<section class="ileg-analytics__kpis">
		<div class="ileg-analytics__kpi">
			<div class="ileg-analytics__kpi-value"><?php echo esc_html( number_format_i18n( $total_views ) ); ?></div>
			<div class="ileg-analytics__kpi-label">Total Views</div>
		</div>
		<div class="ileg-analytics__kpi">
			<div class="ileg-analytics__kpi-value"><?php echo esc_html( number_format_i18n( $total_likes ) ); ?></div>
			<div class="ileg-analytics__kpi-label">Total Likes</div>
		</div>
		<div class="ileg-analytics__kpi">
			<div class="ileg-analytics__kpi-value"><?php echo esc_html( number_format_i18n( $total_posts ) ); ?></div>
			<div class="ileg-analytics__kpi-label">Total Posts</div>
		</div>
		<div class="ileg-analytics__kpi">
			<div class="ileg-analytics__kpi-value"><?php echo esc_html( number_format_i18n( $total_images ) ); ?></div>
			<div class="ileg-analytics__kpi-label">Total Images</div>
		</div>
	</section>

	<section class="ileg-analytics__charts">
		<div class="ileg-analytics__card ileg-analytics__card--chart">
			<div class="ileg-analytics__card-head">
				<h4>Views Over Time</h4>
			</div>
			<div class="ileg-analytics__chart-wrap">
				<div class="ileg-analytics__axis">
					<?php foreach ( $line_ticks as $tick ) : ?>
						<span><?php echo esc_html( $tick ); ?></span>
					<?php endforeach; ?>
				</div>
				<div class="ileg-analytics__line-chart">
					<svg viewBox="0 0 <?php echo esc_attr( $line_width ); ?> <?php echo esc_attr( $line_height ); ?>" role="img" aria-label="Views over time">
						<g class="grid">
							<?php foreach ( $line_ticks as $tick ) : ?>
								<?php
								$y = $line_height - $line_pad - ( ( $tick / max( 1, $line_axis_top ) ) * ( $line_height - ( 2 * $line_pad ) ) );
								?>
								<line x1="<?php echo $line_pad; ?>" x2="<?php echo $line_width - $line_pad; ?>" y1="<?php echo esc_attr( $y ); ?>" y2="<?php echo esc_attr( $y ); ?>" />
							<?php endforeach; ?>
							<?php foreach ( $line_coords as $coord ) : ?>
								<line x1="<?php echo esc_attr( round( $coord['x'], 2 ) ); ?>" x2="<?php echo esc_attr( round( $coord['x'], 2 ) ); ?>" y1="<?php echo $line_pad; ?>" y2="<?php echo $line_height - $line_pad; ?>" />
							<?php endforeach; ?>
						</g>
						<path d="<?php echo esc_attr( implode( ' ', $path_segments ) ); ?>" />
						<?php foreach ( $line_coords as $index => $coord ) : ?>
							<?php
							$line_label = $line_labels[ $index ] ?? '';
							$line_value = $line_values[ $index ] ?? 0;
							$tip_text   = sprintf( '%s - %s views', $line_label, number_format_i18n( $line_value ) );
							?>
							<circle
								cx="<?php echo esc_attr( round( $coord['x'], 2 ) ); ?>"
								cy="<?php echo esc_attr( round( $coord['y'], 2 ) ); ?>"
								r="5"
								data-ileg-tip="<?php echo esc_attr( $tip_text ); ?>"
								role="presentation"
							/>
						<?php endforeach; ?>
					</svg>
					<div class="ileg-analytics__line-labels">
						<?php foreach ( $line_labels as $label ) : ?>
							<span><?php echo esc_html( $label ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="ileg-analytics__card ileg-analytics__card--chart">
			<div class="ileg-analytics__card-head">
				<h4>Likes per Post</h4>
			</div>
			<div class="ileg-analytics__bar-chart">
				<?php if ( $top_liked_posts ) : ?>
					<?php foreach ( $top_liked_posts as $index => $liked_post ) : ?>
						<?php
						$likes  = (int) get_post_meta( $liked_post->ID, 'likes', true );
						$height = 20 + ( ( $likes / $bar_max ) * 140 );
						$title  = get_the_title( $liked_post ) ?: '(no title)';
						$short  = wp_trim_words( $title, 5, '...' );
						$tip    = sprintf( '%s - %s likes', $title, number_format_i18n( $likes ) );
						?>
						<div class="ileg-analytics__bar" data-ileg-tip="<?php echo esc_attr( $tip ); ?>" tabindex="0">
							<div class="ileg-analytics__bar-fill" style="height: <?php echo esc_attr( round( $height, 2 ) ); ?>px;"></div>
							<span class="ileg-analytics__bar-name"><?php echo esc_html( $short ); ?></span>
							<!-- <span class="ileg-analytics__bar-value"><?php echo esc_html( number_format_i18n( $likes ) ); ?></span> -->
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="ileg-analytics__empty">Likes data will appear once your posts start getting likes.</p>
				<?php endif; ?>
			</div>
		</div>

	</section>

	<section class="ileg-analytics__grid">
		<div class="ileg-analytics__card">
			<div class="ileg-analytics__card-head">
				<h4>Top Posts</h4>
			</div>
			<?php if ( $top_posts ) : ?>
				<div class="ileg-analytics__table">
					<div class="ileg-analytics__row ileg-analytics__row--head">
						<span>Post</span>
						<span>Views</span>
						<span>Likes</span>
						<span>Comments</span>
					</div>
					<?php foreach ( $top_posts as $post ) : ?>
						<?php
						$post_views    = (int) get_post_meta( $post->ID, 'views', true );
						$post_likes    = (int) get_post_meta( $post->ID, 'likes', true );
						$post_comments = (int) get_comments_number( $post->ID );
						$title         = get_the_title( $post );
						?>
						<div class="ileg-analytics__row">
							<a class="ileg-analytics__row-title" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
								<?php echo esc_html( $title ?: '(no title)' ); ?>
							</a>
							<span><?php echo esc_html( number_format_i18n( $post_views ) ); ?></span>
							<span><?php echo esc_html( number_format_i18n( $post_likes ) ); ?></span>
							<span><?php echo esc_html( number_format_i18n( $post_comments ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="ileg-analytics__empty">Publish a post to start tracking performance.</p>
			<?php endif; ?>
		</div>

		<div class="ileg-analytics__card ileg-analytics__card--activity">
			<div class="ileg-analytics__card-head">
				<h4>Recent Activity</h4>
			</div>
			<ul class="ileg-analytics__activity">
				<li><strong><?php echo esc_html( number_format_i18n( $recent_views ) ); ?></strong> views on posts created in the last 7 days</li>
				<li><strong><?php echo esc_html( number_format_i18n( $recent_likes ) ); ?></strong> likes on posts created in the last 7 days</li>
				<li><strong><?php echo esc_html( number_format_i18n( $recent_comments ) ); ?></strong> comments in the last 7 days</li>
			</ul>
		</div>
	</section>
	<div class="ileg-analytics__tooltip" aria-hidden="true"></div>
</div>
<?php
// Tooltip helper scoped to this block instance.
?>
<script>
(() => {
	const root = document.currentScript.previousElementSibling;
	if (!root || !root.classList.contains('wp-block-ileg-analytics')) return;
	const tip = root.querySelector('.ileg-analytics__tooltip');
	if (!tip) return;

	const show = (el) => {
		const text = el.getAttribute('data-ileg-tip');
		if (!text) return;
		tip.textContent = text;
		const rootRect = root.getBoundingClientRect();
		const rect = el.getBoundingClientRect();
		const x = rect.left - rootRect.left + (rect.width / 2);
		const y = rect.top - rootRect.top - 12;
		tip.style.left = `${x}px`;
		tip.style.top = `${y}px`;
		tip.classList.add('is-visible');
	};

	const hide = () => {
		tip.classList.remove('is-visible');
	};

	root.querySelectorAll('[data-ileg-tip]').forEach((el) => {
		el.addEventListener('mouseenter', () => show(el));
		el.addEventListener('focus', () => show(el));
		el.addEventListener('mouseleave', hide);
		el.addEventListener('blur', hide);
	});
})();
</script>
