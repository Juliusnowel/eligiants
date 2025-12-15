<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ATTRIBUTES
 */
$section_title  = isset( $attributes['sectionTitle'] ) ? sanitize_text_field( $attributes['sectionTitle'] ) : 'Explore';
$view_all_label = isset( $attributes['viewAllLabel'] ) ? sanitize_text_field( $attributes['viewAllLabel'] ) : 'View all';
$view_all_url   = isset( $attributes['viewAllUrl'] ) ? esc_url_raw( $attributes['viewAllUrl'] ) : home_url( '/blog/' );
$posts_to_show  = isset( $attributes['postsToShow'] ) ? (int) $attributes['postsToShow'] : 9;
$metrics_cutoff = isset( $attributes['metricsCutoffDays'] ) ? (int) $attributes['metricsCutoffDays'] : 7;
$blog_render    = isset( $attributes['blogRender'] ) ? sanitize_key( $attributes['blogRender'] ) : 'none';
$desktop_cols   = isset( $attributes['desktopCols'] ) ? (int) $attributes['desktopCols'] : 4;

if ( $posts_to_show <= 0 ) {
	$posts_to_show = 9;
}
if ( $metrics_cutoff <= 0 ) {
	$metrics_cutoff = 7;
}
if ( ! in_array( $blog_render, [ 'new', 'old', 'none' ], true ) ) {
	$blog_render = 'none';
}
if ( ! in_array( $desktop_cols, [ 3, 4, 5 ], true ) ) {
	$desktop_cols = 4;
}

/**
 * MODE:
 * - new => "Published Live Contents" (last N days, show date)
 * - old => "Explore" (older than N days, hide date)
 * - none => fallback
 */
$mode = $blog_render;

if ( 'none' === $mode ) {
	$title_lower = strtolower( $section_title );
	if ( false !== strpos( $title_lower, 'published live' ) ) {
		$mode = 'new';
	} elseif ( false !== strpos( $title_lower, 'explore' ) ) {
		$mode = 'old';
	}
}
if ( ! in_array( $mode, [ 'new', 'old', 'none' ], true ) ) {
	$mode = 'none';
}

/**
 * DATE QUERY (same business rules as before)
 */
$date_query = [];
$relative   = '-' . $metrics_cutoff . ' days';

if ( 'new' === $mode ) {
	$date_query[] = [
		'after'     => $relative,
		'inclusive' => true,
		'column'    => 'post_date',
	];
} elseif ( 'old' === $mode ) {
	$date_query[] = [
		'before'    => $relative,
		'inclusive' => false,
		'column'    => 'post_date',
	];
}

/**
 * QUERY POSTS
 */
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

/**
 * BUILD ITEMS ARRAY (one per card)
 */
$items           = [];
$now_ts          = current_time( 'timestamp' );
$current_user_id = get_current_user_id();
$liked_posts     = $current_user_id
	? array_map( 'intval', (array) get_user_meta( $current_user_id, '_ileg_liked_posts', true ) )
	: [];

while ( $query->have_posts() ) {
	$query->the_post();

	$post_id   = get_the_ID();
	$thumb_url = get_the_post_thumbnail_url( $post_id, 'medium_large' );

	$author_id   = get_post_field( 'post_author', $post_id );
	$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';

	$likes_raw = get_post_meta( $post_id, 'likes', true );
	$views_raw = get_post_meta( $post_id, 'views', true );
	$likes     = $likes_raw !== '' ? (int) $likes_raw : 0;
	$views     = $views_raw !== '' ? (int) $views_raw : 0;

	$posted_ts = get_the_time( 'U', $post_id );
	$age_days  = floor( ( $now_ts - $posted_ts ) / DAY_IN_SECONDS );
	$time_ago  = human_time_diff( $posted_ts, $now_ts ) . ' ago';

	$items[] = [
		'postId'     => $post_id,
		'imageURL'   => $thumb_url,
		'imageAlt'   => get_the_title(),
		'title'      => get_the_title(),
		'excerpt'    => wp_trim_words( get_the_excerpt(), 24, '.' ),
		'authorName' => $author_name,
		'likes'      => $likes,
		'views'      => $views,
		'liked'      => in_array( $post_id, $liked_posts, true ),
		'timeAgo'    => $time_ago,
		'ageDays'    => $age_days,
		'url'        => get_permalink( $post_id ),
	];
}
wp_reset_postdata();

if ( empty( $items ) ) {
	return '';
}

/**
 * LAYOUT CONFIG — same pattern as reference carousel
 */
$cols = [
	'xs' => 1,
	'sm' => 2,
	'md' => min( 3, $desktop_cols ),
	'lg' => $desktop_cols,
	'xl' => $desktop_cols,
];
$bps = [ 'xs', 'sm', 'md', 'lg', 'xl' ];

$rowColsPieces = [];
foreach ( $bps as $bp ) {
	if ( ! empty( $cols[ $bp ] ) ) {
		$n = max( 1, (int) $cols[ $bp ] );
		$rowColsPieces[] = ( 'xs' === $bp ) ? "row-cols-$n" : "row-cols-$bp-$n";
	}
}
$rowColsClasses = 'row ' . implode( ' ', $rowColsPieces ) . ' g-3 g-md-4';

$autoplay       = false;
$interval       = 5000;
$pauseOnHover   = true;
$wrap           = true;
$showControls   = true;
$showIndicators = true;

$isCarousel = true;

$ariaLabel = $section_title ? $section_title : 'Discover posts carousel';
$id        = 'discover_' . uniqid();
$scope_id  = 'pscope_' . $id;

/**
 * CARD RENDERER – keeps your existing card design,
 * just plugs into the reference carousel layout.
 */
$print_card = function( array $card ) use ( $mode, $metrics_cutoff ) {
	$title      = $card['title'] ?? '';
	$excerpt    = $card['excerpt'] ?? '';
	$authorName = $card['authorName'] ?? '';
	$postId     = isset( $card['postId'] ) ? (int) $card['postId'] : 0;
	$likes      = isset( $card['likes'] ) ? (int) $card['likes'] : 0;
	$views      = isset( $card['views'] ) ? (int) $card['views'] : 0;
	$liked      = ! empty( $card['liked'] );
	$timeAgo    = $card['timeAgo'] ?? '';
	$ageDays    = isset( $card['ageDays'] ) ? (int) $card['ageDays'] : 0;
	$thumb      = $card['imageURL'] ?? '';
	$alt        = $card['imageAlt'] ?? '';
	$url        = $card['url'] ?? '';

	// date visibility logic:
	$show_date = false;
	if ( 'new' === $mode ) {
		$show_date = true;
	} elseif ( 'old' === $mode ) {
		$show_date = false;
	} else {
		$show_date = ( $ageDays <= $metrics_cutoff );
	}
	?>
	<article class="discover-card"
	         data-post-id="<?php echo esc_attr( $postId ); ?>"
	         data-liked="<?php echo $liked ? '1' : '0'; ?>"
	         data-likes="<?php echo esc_attr( $likes ); ?>">
		<div class="discover-card__like-wrap">
			<button type="button"
			        class="discover-card__like-btn"
			        aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>"
			        <?php if ( ! $postId ) echo 'disabled'; ?>>
				<i class="fa<?php echo $liked ? 's' : 'r'; ?> fa-heart" aria-hidden="true"></i>
				<span class="discover-card__like-count"><?php echo esc_html( $likes ); ?></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle like', 'child' ); ?></span>
			</button>
		</div>

		<a href="<?php echo esc_url( $url ); ?>" class="discover-card__link">
			<div class="discover-card__thumb">
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>"
					     alt="<?php echo esc_attr( $alt ); ?>"
					     class="discover-card__img" />
				<?php else : ?>
					<div class="discover-card__thumb-placeholder">
						<i class="far fa-image"></i>
					</div>
				<?php endif; ?>
			</div>

			<div class="discover-card__body">
				<h3 class="discover-card__title">
					<?php echo esc_html( $title ); ?>
				</h3>

				<p class="discover-card__excerpt">
					<?php echo esc_html( $excerpt ); ?>
				</p>

				<?php if ( $authorName ) : ?>
					<p class="discover-card__meta-author">
						<span class="discover-card__meta-label">Posted By:</span>
						<?php echo esc_html( $authorName ); ?>
					</p>
				<?php endif; ?>

				<div class="discover-card__meta-footer">
					<span><?php echo esc_html( $views ); ?> Views</span>
					<?php if ( $show_date && $timeAgo ) : ?>
						<span class="discover-card__meta-muted">
							<?php echo esc_html( $timeAgo ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</a>
	</article>
	<?php
};

/**
 * BUILD SLIDES (desktop) — same pattern as reference
 */
$largest  = 1;
foreach ( $bps as $bp ) {
	if ( ! empty( $cols[ $bp ] ) ) {
		$largest = max( $largest, (int) $cols[ $bp ] );
	}
}
$perSlide = max( 1, $largest );
$slides   = array_chunk( $items, $perSlide );

$data = [
	'data-bs-ride'     => $autoplay ? 'carousel' : false,
	'data-bs-interval' => $autoplay ? $interval : false,
	'data-bs-pause'    => $pauseOnHover ? 'hover' : 'false',
	'data-bs-wrap'     => $wrap ? 'true' : 'false',
	'data-bs-touch'    => 'true',
];
$carouselDataAttr = '';
foreach ( $data as $k => $v ) {
	if ( false !== $v && null !== $v && '' !== $v ) {
		$carouselDataAttr .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
	}
}

// mobile slide IDs for indicators
$slide_ids = [];
foreach ( $items as $idx => $_ ) {
	$slide_ids[] = $id . '-s' . $idx;
}

ob_start();
?>

<section class="carousel-block discover-carousel child-block">
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

	<?php if ( $isCarousel ) : ?>
		<?php $ariaLabel_esc = esc_attr( $ariaLabel ); ?>

		<!-- ===== MOBILE/TABLET: peek slider (from reference) ===== -->
		<div id="<?php echo esc_attr( $scope_id ); ?>"
		     class="peek-slider is-mobile"
		     role="region"
		     aria-roledescription="carousel"
		     aria-label="<?php echo $ariaLabel_esc; ?>">
			<div class="peek-track">
				<?php foreach ( $items as $i => $card ) : ?>
					<div class="peek-slide" id="<?php echo esc_attr( $slide_ids[ $i ] ); ?>">
						<div class="row row-cols-1 g-3">
							<div class="col">
								<?php $print_card( $card ); ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<nav class="peek-indicators" aria-label="Carousel indicators">
				<?php foreach ( $items as $i => $_ ) : ?>
					<a class="pi-dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
					   data-i="<?php echo (int) $i; ?>"
					   href="#<?php echo esc_attr( $slide_ids[ $i ] ); ?>"
					   aria-label="<?php printf( 'Go to slide %d', $i + 1 ); ?>"></a>
				<?php endforeach; ?>
			</nav>
		</div>

		<!-- ===== DESKTOP: Bootstrap carousel (from reference) ===== -->
		<div id="<?php echo esc_attr( $id ); ?>"
		     class="carousel slide generic-carousel is-desktop"
		     role="region"
		     aria-roledescription="carousel"
		     aria-label="<?php echo $ariaLabel_esc; ?>"
		     data-cols-xs="<?php echo (int) ( $cols['xs'] ?? 1 ); ?>"
		     data-cols-sm="<?php echo (int) ( $cols['sm'] ?? 2 ); ?>"
		     data-cols-md="<?php echo (int) ( $cols['md'] ?? 3 ); ?>"
		     data-cols-lg="<?php echo (int) ( $cols['lg'] ?? 4 ); ?>"
		     data-cols-xl="<?php echo (int) ( $cols['xl'] ?? 5 ); ?>"
			<?php echo $carouselDataAttr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $showIndicators && count( $slides ) > 1 ) : ?>
				<div class="carousel-indicators">
					<?php foreach ( $slides as $i => $_ ) : ?>
						<button type="button"
						        data-bs-target="#<?php echo esc_attr( $id ); ?>"
						        data-bs-slide-to="<?php echo esc_attr( $i ); ?>"
							<?php echo 0 === $i ? 'class="active" aria-current="true"' : ''; ?>
						        aria-label="<?php printf( 'Slide %d', $i + 1 ); ?>"></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="carousel-inner">
				<?php foreach ( $slides as $sIndex => $group ) : ?>
					<?php
					$isPartial            = count( $group ) < $perSlide;
					$rowClassThisSlide    = $rowColsClasses . ( $isPartial ? '' : '' );
					$carousel_item_active = ( 0 === $sIndex ) ? ' active' : '';
					?>
					<div class="carousel-item<?php echo esc_attr( $carousel_item_active ); ?>">
						<div class="<?php echo esc_attr( $rowClassThisSlide ); ?>">
							<?php foreach ( $group as $card ) : ?>
								<div class="col">
									<?php $print_card( $card ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $showControls && count( $slides ) > 1 ) : ?>
				<button class="carousel-control-prev" type="button"
				        data-bs-target="#<?php echo esc_attr( $id ); ?>"
				        data-bs-slide="prev" aria-label="Previous">
					<span class="slider-prev" aria-hidden="true">&#10094;</span>
				</button>
				<button class="carousel-control-next" type="button"
				        data-bs-target="#<?php echo esc_attr( $id ); ?>"
				        data-bs-slide="next" aria-label="Next">
					<span class="slider-next" aria-hidden="true">&#10095;</span>
				</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>

<script>
/* Mobile peek-slider behavior (copied from reference) */
(function(){
  const scope  = document.getElementById('<?php echo esc_js( $scope_id ); ?>');
  if(!scope || scope.dataset.bound === '1') return;
  scope.dataset.bound = '1';

  const container = scope;
  const track     = scope.querySelector('.peek-track');
  const slides    = Array.from(scope.querySelectorAll('.peek-slide'));
  const dots      = Array.from(scope.querySelectorAll('.pi-dot'));

  function setActive(idx) {
    dots.forEach(d => d.classList.remove('is-active'));
    if (dots[idx]) dots[idx].classList.add('is-active');
  }

  dots.forEach((dot,i)=>{
    dot.addEventListener('click', function(e){
      e.preventDefault();
      slides[i].scrollIntoView({behavior:'smooth', inline:'center', block:'nearest'});
      setActive(i);
    }, {passive:false});
  });

  function setActiveByCenter() {
    const cRect = container.getBoundingClientRect();
    const cMid  = cRect.left + cRect.width/2;
    let bestI = 0, bestDist = Infinity;
    slides.forEach((sl, i) => {
      const r = sl.getBoundingClientRect();
      const mid = r.left + r.width/2;
      const d = Math.abs(mid - cMid);
      if (d < bestDist) { bestDist = d; bestI = i; }
    });
    setActive(bestI);
  }

  let ticking = false;
  const onScroll = () => {
    if (!ticking) {
      window.requestAnimationFrame(() => { setActiveByCenter(); ticking = false; });
      ticking = true;
    }
  };

  container.addEventListener('scroll', onScroll, {passive:true});
  window.addEventListener('resize', setActiveByCenter);
  setActiveByCenter();
})();

/* Likes + view tracking */
(function(){
  const ajax = {
    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    likeNonce: '<?php echo wp_create_nonce( 'dg_like_nonce' ); ?>'
  };

  const cards = Array.from(document.querySelectorAll('.discover-carousel .discover-card'));
  if (!cards.length) return;

  function setLikeState(card, btn, icon, countEl, liked, likes) {
    const likesInt = Math.max(0, parseInt(likes || '0', 10) || 0);
    card.dataset.liked = liked ? '1' : '0';
    card.dataset.likes = String(likesInt);

    if (countEl) countEl.textContent = likesInt;
    if (btn) {
      btn.classList.toggle('is-liked', liked);
      btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
    }
    if (icon) {
      icon.classList.toggle('fa-solid', liked);
      icon.classList.toggle('fa-regular', !liked);
    }
  }

  function trackView(postId) {
    if (!postId) return;
    const params = new URLSearchParams({ action: 'ileg_track_view', post_id: postId });
    const encoded = params.toString();
    if (navigator.sendBeacon) {
      const blob = new Blob([encoded], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
      navigator.sendBeacon(ajax.url, blob);
    } else {
      fetch(ajax.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: encoded
      }).catch(function(){});
    }
  }

  cards.forEach(function(card){
    const likeBtn   = card.querySelector('.discover-card__like-btn');
    const likeIcon  = likeBtn ? likeBtn.querySelector('.fa-heart') : null;
    const likeCount = likeBtn ? likeBtn.querySelector('.discover-card__like-count') : null;
    const link      = card.querySelector('.discover-card__link');
    const postId    = card.dataset.postId || '';

    if (link && postId) {
      link.addEventListener('click', function(){
        trackView(postId);
      });
    }

    if (!likeBtn || !postId) return;

    // Sync initial state from dataset
    setLikeState(
      card,
      likeBtn,
      likeIcon,
      likeCount,
      card.dataset.liked === '1',
      card.dataset.likes || '0'
    );

    likeBtn.addEventListener('click', function(e){
      e.preventDefault();
      e.stopPropagation();

      const currentLiked = card.dataset.liked === '1';
      const currentLikes = parseInt(card.dataset.likes || '0', 10) || 0;
      const nextLiked    = !currentLiked;
      const optimistic   = Math.max(0, currentLikes + (nextLiked ? 1 : -1));

      setLikeState(card, likeBtn, likeIcon, likeCount, nextLiked, optimistic);

      fetch(ajax.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: new URLSearchParams({
          action: 'ileg_toggle_like',
          nonce: ajax.likeNonce,
          post_id: postId
        })
      })
      .then(function(res){ return res.json(); })
      .then(function(data){
        if (!data || !data.success || !data.data) return;
        const serverLikes = parseInt(data.data.likes || '0', 10) || 0;
        const serverLiked = !!data.data.liked;
        setLikeState(card, likeBtn, likeIcon, likeCount, serverLiked, serverLikes);
      })
      .catch(function(){});
    });
  });
})();
</script>
