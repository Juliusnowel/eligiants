<?php
/**
 * Server-side render for Discover Grid block.
 *
 * Block: child/discover-grid
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$A = ( isset( $attributes ) && is_array( $attributes ) ) ? $attributes : [];

// Basic attributes.
$title = isset( $A['title'] ) ? trim( (string) $A['title'] ) : '';
$intro = isset( $A['intro'] ) ? trim( (string) $A['intro'] ) : '';

// Layout / query controls.
$posts_per_page = (int) ( $A['postsPerPage'] ?? 12 );
$rows_desktop   = (int) ( $A['rowsDesktop'] ?? 3 );
$date_mode      = isset( $A['dateFilterMode'] ) ? (string) $A['dateFilterMode'] : 'none';
$metrics_cutoff = (int) ( $A['metricsCutoffDays'] ?? 7 );

$show_search     = ! empty( $A['showSearch'] );
$show_cat_filter = ! empty( $A['showCategoryFilter'] );
$image_mode      = ! empty( $A['imageMode'] );

$config_cat_slugs = ! empty( $A['categorySlugs'] ) && is_array( $A['categorySlugs'] )
	? array_map( 'sanitize_title', $A['categorySlugs'] )
	: [];

// Normalize.
if ( $posts_per_page <= 0 ) {
	$posts_per_page = 12;
}
if ( $rows_desktop <= 0 ) {
	$rows_desktop = 3;
}
if ( $metrics_cutoff <= 0 ) {
	$metrics_cutoff = 7;
}
if ( ! in_array( $date_mode, [ 'new', 'old', 'none' ], true ) ) {
	$date_mode = 'none';
}

/**
 * Optional: load title/intro from region JSON mapping
 * (page-discover.html -> inc/region-data.php).
 */
if ( $title === '' && $intro === '' ) {
	if ( ! function_exists( 'child_load_region_data' ) ) {
		$inc = trailingslashit( get_stylesheet_directory() ) . 'inc/region-data.php';
		if ( file_exists( $inc ) ) {
			require_once $inc;
		}
	}

	if ( function_exists( 'child_load_region_data' ) ) {
		$data   = child_load_region_data();
		$key    = isset( $A['dataKey'] ) ? (string) $A['dataKey'] : 'discover';
		$index  = isset( $A['dataIndex'] ) ? (int) $A['dataIndex'] : 0;
		$loaded = null;

		if ( ! empty( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
			if ( isset( $data[ $key ][ $index ] ) && is_array( $data[ $key ][ $index ] ) ) {
				$loaded = $data[ $key ][ $index ];
			} else {
				$loaded = $data[ $key ];
			}
		}

		if ( $loaded ) {
			if ( $title === '' && ! empty( $loaded['title'] ) ) {
				$title = (string) $loaded['title'];
			}
			if ( $intro === '' && ! empty( $loaded['intro'] ) ) {
				$intro = (string) $loaded['intro'];
			}
		}
	}
}

/**
 * Build query (post type: post).
 */
$date_query = [];
$relative   = '-' . $metrics_cutoff . ' days';

if ( 'new' === $date_mode ) {
	$date_query[] = [
		'after'     => $relative,
		'inclusive' => true,
		'column'    => 'post_date',
	];
} elseif ( 'old' === $date_mode ) {
	$date_query[] = [
		'before'    => $relative,
		'inclusive' => false,
		'column'    => 'post_date',
	];
}

$args = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 100, // load enough for client-side pagination
	'orderby'             => 'date',
	'order'               => 'DESC',
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
];

if ( ! empty( $date_query ) ) {
	$args['date_query'] = $date_query;
}

// Category constraints from block settings + image mode.
$tax_query = [];

if ( $image_mode ) {
	$tax_query[] = [
		'taxonomy' => 'category',
		'field'    => 'slug',
		'terms'    => [ 'imagepost' ],
		'operator' => 'IN',
	];
}

if ( ! empty( $config_cat_slugs ) ) {
	$tax_query[] = [
		'taxonomy' => 'category',
		'field'    => 'slug',
		'terms'    => $config_cat_slugs,
		'operator' => 'IN',
	];
}

if ( ! empty( $tax_query ) ) {
	$args['tax_query'] = count( $tax_query ) > 1
		? array_merge( [ 'relation' => 'AND' ], $tax_query )
		: $tax_query;
}

$query = new WP_Query( $args );
if ( ! $query->have_posts() ) {
	return;
}

/**
 * Collect posts + category map.
 * Any post in category slug "imagepost" is excluded from filters.
 */
$items        = [];
$category_map = []; // slug => name
$now_ts       = current_time( 'timestamp' );
$current_user = get_current_user_id();
$user_liked_posts = [];
if ( $current_user ) {
	$user_liked_posts = array_map(
		'intval',
		(array) get_user_meta( $current_user, '_ileg_liked_posts', true )
	);
}

while ( $query->have_posts() ) {
	$query->the_post();
	$post_id = get_the_ID();

	$thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( ! $thumb_url ) {
		$thumb_url = '';
	}

	$author_id   = (int) get_post_field( 'post_author', $post_id );
	$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';

	$posted_ts = get_the_time( 'U', $post_id );
	$age_days  = max( 0, floor( ( $now_ts - $posted_ts ) / DAY_IN_SECONDS ) );
	$time_ago  = human_time_diff( $posted_ts, $now_ts ) . ' ago';

	$likes_raw = get_post_meta( $post_id, 'likes', true );
	$views_raw = get_post_meta( $post_id, 'views', true );
	$likes     = $likes_raw !== '' ? (int) $likes_raw : 0;
	$views     = $views_raw !== '' ? (int) $views_raw : 0;

	$cats          = get_the_category( $post_id );
	$cat_slugs     = [];
	$has_imagepost = false;

	if ( $cats && ! is_wp_error( $cats ) ) {
		foreach ( $cats as $c ) {
			$slug = $c->slug;

			if ( 'imagepost' === $slug ) {
				$has_imagepost = true;
				continue; // do not show "imagepost" in filters.
			}

			$category_map[ $slug ] = $c->name;
			$cat_slugs[]           = $slug;
		}
	}

	// In image mode, only keep posts that actually have imagepost.
	if ( $image_mode && ! $has_imagepost ) {
		continue;
	}

	// In normal mode, skip imagepost-only content.
	if ( ! $image_mode && $has_imagepost ) {
		continue;
	}

	$tags = [];
	$post_tags = get_the_tags( $post_id );
	if ( $post_tags && ! is_wp_error( $post_tags ) ) {
		foreach ( $post_tags as $tag ) {
			$tags[] = $tag->name;
		}
	}

	$items[] = [
		'post_id'     => $post_id,
		'title'       => html_entity_decode( get_the_title() ),
		'excerpt'     => wp_strip_all_tags( get_the_excerpt() ),
		'permalink'   => get_permalink( $post_id ),
		'image_url'   => $thumb_url,
		'author_id'   => $author_id,
		'author_name' => $author_name,
		'age_days'    => $age_days,
		'time_ago'    => $time_ago,
		'likes'       => $likes,
		'views'       => $views,
		'liked_by_user' => in_array( $post_id, $user_liked_posts, true ),
		'categories'  => $cat_slugs,
		'tags'        => $tags,
	];
}
wp_reset_postdata();

if ( empty( $items ) ) {
	return;
}

/**
 * Layout metrics.
 */
$total_posts    = count( $items );
$posts_per_page = max( 1, $posts_per_page );
$rows_desktop   = max( 1, $rows_desktop );
$cols_desktop   = max( 1, min( $posts_per_page, (int) ceil( $posts_per_page / $rows_desktop ) ) );

$instance_id = 'discover_grid_' . wp_generate_uuid4();
$grid_style  = '--cols-desktop:' . $cols_desktop . ';';

if ( $image_mode ) {
	// drive Pinterest column-count via rowsDesktop (4, 5, etc.)
	$image_cols = max( 1, $rows_desktop );
	$grid_style .= '--image-cols-desktop:' . $image_cols . ';';
}

$aria_label = $title !== '' ? $title : __( 'Discover posts', 'child' );

?>
<section id="<?php echo esc_attr( $instance_id ); ?>"
         class="discover-grid-block alignwide<?php echo $image_mode ? ' discover-grid--images' : ''; ?>"
         aria-label="<?php echo esc_attr( $aria_label ); ?>"
         data-per-page="<?php echo esc_attr( $posts_per_page ); ?>">

	<header class="discover-grid__head">
		<div class="discover-grid__head-main">
			<?php if ( $title ) : ?>
				<h2 class="discover-grid__title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
			<?php if ( $intro ) : ?>
				<p class="discover-grid__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $show_search ) : ?>
			<div class="discover-grid__search-wrap">
				<input type="search"
				       class="discover-grid__search"
				       placeholder="<?php esc_attr_e( 'Search posts…', 'child' ); ?>">
			</div>
		<?php endif; ?>
	</header>

	<?php
	$has_filters = ( $show_cat_filter && ! empty( $category_map ) );
	if ( $has_filters ) :
		$select_id = $instance_id . '_cat_select';
		?>
		<div class="discover-grid__filters">
			<div class="discover-grid__categories-wrap">
				<div class="discover-grid__categories">
					<button type="button"
					        class="discover-grid__cat-btn is-active"
					        data-cat="all">
						<?php esc_html_e( 'Show All', 'child' ); ?>
					</button>
					<?php foreach ( $category_map as $slug => $name ) : ?>
						<button type="button"
						        class="discover-grid__cat-btn"
						        data-cat="<?php echo esc_attr( $slug ); ?>">
							<?php echo esc_html( $name ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="discover-grid__categories-select-wrap">
					<label class="screen-reader-text" for="<?php echo esc_attr( $select_id ); ?>">
						<?php esc_html_e( 'Filter by category', 'child' ); ?>
					</label>
					<select id="<?php echo esc_attr( $select_id ); ?>" class="discover-grid__cat-select">
						<option value="all"><?php esc_html_e( 'Show All', 'child' ); ?></option>
						<?php foreach ( $category_map as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>">
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="discover-grid__grid"
	     style="<?php echo esc_attr( $grid_style ); ?>">

		<?php foreach ( $items as $item ) : ?>
			<?php
			$title_safe   = $item['title'];
			$excerpt_safe = $item['excerpt'];
			$img          = $item['image_url'];
			$url          = $item['permalink'];
			$author_name  = $item['author_name'];
			$likes        = (int) $item['likes'];
			$views        = (int) $item['views'];
			$age_days     = (int) $item['age_days'];
			$time_ago     = $item['time_ago'];
			$cat_slugs    = $item['categories'];
			$tags_safe    = $item['tags'];
			$data_cats    = $cat_slugs ? implode( ',', array_map( 'sanitize_title', $cat_slugs ) ) : '';
			$is_author    = ( $item['author_id'] === $current_user );
			$download_url = $img ? $img : $url;

			// For image posts, "(no_title)" means "user did not provide a title".
			$is_placeholder_title = ( $image_mode && $title_safe === '(no_title)' );

			// What we actually want to show / use in attributes.
			$display_title = $is_placeholder_title ? '' : $title_safe;
			$search_title  = strtolower( $display_title );

			?>

			<?php if ( $image_mode ) : ?>
				<article class="dg-card dg-card--images"
					data-post-id="<?php echo esc_attr( $item['post_id'] ); ?>"
					data-title="<?php echo esc_attr( $search_title ); ?>"
					data-cats="<?php echo esc_attr( $data_cats ); ?>"
					data-modal-title="<?php echo esc_attr( $display_title ); ?>"
					data-modal-excerpt="<?php echo esc_attr( $excerpt_safe ); ?>"
					data-modal-author="<?php echo esc_attr( $author_name ); ?>"
					data-modal-likes="<?php echo esc_attr( $likes ); ?>"
					data-modal-liked="<?php echo $item['liked_by_user'] ? '1' : '0'; ?>"
					data-modal-url="<?php echo esc_url( $url ); ?>"
					data-modal-img="<?php echo esc_url( $img ); ?>"
					data-modal-download="<?php echo esc_url( $download_url ); ?>">

					<div class="dg-card__inner">
						<button type="button" class="dg-card__image-btn">
							<div class="dg-card__image">
								<?php if ( $img ) : ?>
									<img src="<?php echo esc_url( $img ); ?>"
										alt="<?php echo esc_attr( $display_title ); ?>">
								<?php else : ?>
									<div class="dg-card__image-placeholder"></div>
								<?php endif; ?>
							</div>
						</button>

						<div class="dg-card__body dg-card__body--images">
							<div class="dg-card__top-row">
								<?php if ( ! empty( $tags_safe ) ) : ?>
								<div class="dg-card__tags">
									<?php echo esc_html( implode( ', ', $tags_safe ) ); ?>
								</div>
								<?php endif; ?>

								<a href="<?php echo esc_url( $download_url ); ?>"
								class="dg-card__download-icon"
								download>
								<i class="fa-solid fa-download" aria-hidden="true"></i>
								<span class="screen-reader-text">
									<?php esc_html_e( 'Download image', 'child' ); ?>
								</span>
								</a>
							</div>
							<?php if ( $display_title !== '' ) : ?>
								<h3 class="dg-card__title dg-card__title--images">
									<?php echo esc_html( $display_title ); ?>
								</h3>
							<?php endif; ?>



							<?php if ( $excerpt_safe ) : ?>
								<p class="dg-card__excerpt dg-card__excerpt--images">
									<?php echo esc_html( $excerpt_safe ); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php else : ?>
				<article class="dg-card"
				         data-title="<?php echo esc_attr( strtolower( $title_safe ) ); ?>"
				         data-cats="<?php echo esc_attr( $data_cats ); ?>"
				         data-post-id="<?php echo esc_attr( $item['post_id'] ); ?>"
				         data-url="<?php echo esc_url( $url ); ?>"
				         data-liked="<?php echo $item['liked_by_user'] ? '1' : '0'; ?>"
				         data-likes="<?php echo esc_attr( $likes ); ?>">
					<div class="dg-card__inner">

						<div class="dg-card__thumb-wrap">
							<div class="dg-card__top-actions">
								<button type="button"
								        class="dg-card__like-btn"
								        aria-pressed="<?php echo $item['liked_by_user'] ? 'true' : 'false'; ?>">
									<i class="fa<?php echo $item['liked_by_user'] ? 's' : 'r'; ?> fa-heart" aria-hidden="true"></i>
									<span class="dg-card__like-count"><?php echo esc_html( $likes ); ?></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Toggle like', 'child' ); ?></span>
								</button>

								<div class="dg-card__more">
									<button type="button" class="dg-card__more-btn" aria-haspopup="true" aria-expanded="false">
										<i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
										<span class="screen-reader-text"><?php esc_html_e( 'Open actions', 'child' ); ?></span>
									</button>
									<div class="dg-card__more-menu" role="menu">
										<a href="<?php echo esc_url( $download_url ); ?>"
										   class="dg-card__pill dg-card__pill--download"
										   download
										   role="menuitem">
											<i class="fa-solid fa-download" aria-hidden="true"></i>
											<?php esc_html_e( 'Download', 'child' ); ?>
										</a>
										<button type="button"
												class="dg-card__pill dg-card__pill--share"
												data-share-url="<?php echo esc_url( $url ); ?>"
												data-share-title="<?php echo esc_attr( $title_safe ); ?>"
												role="menuitem">
											<i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
											<span class="dg-card__share-label">
												<?php esc_html_e( 'Share', 'child' ); ?>
											</span>
										</button>
									</div>
								</div>
							</div>

							<div class="dg-card__image">
								<?php if ( $img ) : ?>
									<img src="<?php echo esc_url( $img ); ?>"
									     alt="<?php echo esc_attr( $title_safe ); ?>">
								<?php else : ?>
									<div class="dg-card__image-placeholder"></div>
								<?php endif; ?>
							</div>
						</div>

						<div class="dg-card__body">
							<h3 class="dg-card__title">
								<a href="<?php echo esc_url( $url ); ?>">
									<?php echo esc_html( $title_safe ); ?>
								</a>
							</h3>

							<?php if ( $excerpt_safe ) : ?>
								<p class="dg-card__excerpt">
									<?php echo esc_html( $excerpt_safe ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $author_name ) : ?>
								<p class="dg-card__meta-author">
									<span class="dg-card__meta-label"><?php esc_html_e( 'Posted By:', 'child' ); ?></span>
									<?php echo esc_html( $author_name ); ?>
								</p>
							<?php endif; ?>

							<div class="dg-card__meta-footer">
								<span class="dg-card__meta-muted">
									<?php echo esc_html( $views ); ?> <?php esc_html_e( 'Views', 'child' ); ?>
								</span>

								<?php if ( $age_days < $metrics_cutoff ) : ?>
									<span class="dg-card__meta-muted">
										<?php echo esc_html( $time_ago ); ?>
									</span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</article>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>

	<nav class="discover-grid__pagination" aria-label="<?php esc_attr_e( 'Posts pagination', 'child' ); ?>"></nav>

	<?php if ( $image_mode ) : ?>
		<div class="discover-grid__modal" aria-hidden="true" role="dialog">
			<div class="discover-grid__modal-backdrop"></div>

			<div class="discover-grid__modal-dialog" role="document">
				<button type="button" class="discover-grid__modal-close" aria-label="<?php esc_attr_e( 'Close', 'child' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>

				<div class="discover-grid__modal-inner">
					<div class="discover-grid__modal-media">
						<div class="discover-grid__modal-image-wrap">
							<img src="" alt="" class="discover-grid__modal-img" />
							<a href=""
							class="discover-grid__modal-download"
							download>
								<i class="fa-solid fa-download" aria-hidden="true"></i>
								<span class="screen-reader-text">
									<?php esc_html_e( 'Download image', 'child' ); ?>
								</span>
							</a>
						</div>
					</div>

					<div class="discover-grid__modal-body">
						<h3 class="discover-grid__modal-title"></h3>
						<div class="discover-grid__modal-excerpt"></div>
						<div class="discover-grid__modal-footer">
							<p class="discover-grid__modal-author-wrap">
								<span class="discover-grid__modal-author-label">
									<?php esc_html_e( 'Posted by:', 'child' ); ?>
								</span>
								<span class="discover-grid__modal-author"></span>
							</p>

							<div class="discover-grid__modal-meta">
								<button type="button" class="discover-grid__modal-likes">
									<i class="fa-regular fa-heart" aria-hidden="true"></i>
									<span class="discover-grid__modal-likes-count"></span>
								</button>

								<button type="button" class="discover-grid__modal-share">
									<i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
									<span class="screen-reader-text">
										<?php esc_html_e( 'Share image', 'child' ); ?>
									</span>
								</button>
							</div>
						</div>

					</div><!-- /.discover-grid__modal-body -->
				</div><!-- /.discover-grid__modal-inner -->
			</div><!-- /.discover-grid__modal-dialog -->
		</div>
	<?php endif; ?>

</section>

<script>
(function(){
  const dgAjax = {
    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    likeNonce: '<?php echo wp_create_nonce( 'dg_like_nonce' ); ?>'
  };


  const root = document.getElementById('<?php echo esc_js( $instance_id ); ?>');
  if (!root) return;

  const cards          = Array.from(root.querySelectorAll('.dg-card'));
  const searchInput    = root.querySelector('.discover-grid__search');
  const catButtons     = Array.from(root.querySelectorAll('.discover-grid__cat-btn'));
  const categoriesWrap = root.querySelector('.discover-grid__categories-wrap');
  const catSelect      = root.querySelector('.discover-grid__cat-select');
  const pagination     = root.querySelector('.discover-grid__pagination');
  const isImageMode    = root.classList.contains('discover-grid--images');

  const perPage = parseInt(root.getAttribute('data-per-page') || '12', 10) || 12;

  let currentPage     = 1;
  let currentSearch   = '';
  let currentCategory = 'all';

  function getFiltered() {
    const term = currentSearch.trim().toLowerCase();
    return cards.filter(function(card) {
      const title = (card.dataset.title || '').toLowerCase();
      const cats  = (card.dataset.cats || '').split(',').filter(Boolean);
      if (term && title.indexOf(term) === -1) return false;
      if (currentCategory !== 'all' && cats.indexOf(currentCategory) === -1) return false;
      return true;
    });
  }

  function buildPagination(totalPages) {
    pagination.innerHTML = '';
    if (totalPages <= 1) return;

    const ul = document.createElement('ul');
    ul.className = 'dg-pagination';

    for (let i = 1; i <= totalPages; i++) {
      const li = document.createElement('li');
      li.className = 'dg-pagination__item';

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'dg-pagination__link' + (i === currentPage ? ' is-active' : '');
      btn.textContent = String(i);
      btn.addEventListener('click', function() {
        currentPage = i;
        render();
      });

      li.appendChild(btn);
      ul.appendChild(li);
    }

    pagination.appendChild(ul);
  }

  function render() {
    const filtered   = getFiltered();
    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    cards.forEach(function(card){
      card.style.display = 'none';
    });

    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;
    filtered.slice(start, end).forEach(function(card){
      card.style.display = '';
    });

    buildPagination(totalPages);
  }

  function applyCategory(cat) {
    currentCategory = cat || 'all';
    currentPage = 1;

    // Sync pills
    catButtons.forEach(function(b){
      const slug = b.dataset.cat || 'all';
      b.classList.toggle('is-active', slug === currentCategory);
    });

    // Sync select
    if (catSelect) {
      catSelect.value = currentCategory;
    }

    render();
  }

  // Search
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      currentSearch = searchInput.value || '';
      currentPage = 1;
      render();
    });
  }

  // Category pills
  if (catButtons.length) {
    catButtons.forEach(function(btn){
      btn.addEventListener('click', function() {
        applyCategory(btn.dataset.cat || 'all');
      });
    });
  }

  // Category select (dropdown)
  if (catSelect) {
    catSelect.addEventListener('change', function() {
      applyCategory(catSelect.value || 'all');
    });
  }

  // Condense category pills into dropdown if they wrap too tall.
  function updateCategoryLayout() {
    if (!categoriesWrap) return;
    const pills      = categoriesWrap.querySelector('.discover-grid__categories');
    const selectWrap = categoriesWrap.querySelector('.discover-grid__categories-select-wrap');
    if (!pills || !selectWrap) return;

    const height = pills.offsetHeight;
    const condensed = height > 120; // ~3 lines of chips
    categoriesWrap.classList.toggle('is-condensed', condensed);
  }

  if (categoriesWrap) {
    setTimeout(updateCategoryLayout, 0);

    let resizeTimer = null;
    window.addEventListener('resize', function() {
      if (resizeTimer) window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(updateCategoryLayout, 150);
    });
  }

  // Image-mode modal
   // Image-mode modal
  if (isImageMode) {
    const modal         = root.querySelector('.discover-grid__modal');
    const modalImg      = modal ? modal.querySelector('.discover-grid__modal-img') : null;
    const modalClose    = modal ? modal.querySelector('.discover-grid__modal-close') : null;
    const modalBackdrop = modal ? modal.querySelector('.discover-grid__modal-backdrop') : null;

    const modalTitle       = modal ? modal.querySelector('.discover-grid__modal-title') : null;
    const modalExcerpt     = modal ? modal.querySelector('.discover-grid__modal-excerpt') : null;
    const modalAuthor      = modal ? modal.querySelector('.discover-grid__modal-author') : null;
    const modalLikesWrap   = modal ? modal.querySelector('.discover-grid__modal-likes') : null;
    const modalLikesIcon   = modalLikesWrap ? modalLikesWrap.querySelector('i') : null;
    const modalLikesCount  = modalLikesWrap ? modalLikesWrap.querySelector('.discover-grid__modal-likes-count') : null;
    const modalShare       = modal ? modal.querySelector('.discover-grid__modal-share') : null;
    const modalDownload    = modal ? modal.querySelector('.discover-grid__modal-download') : null;

    function closeModal() {
      if (!modal || !modalImg) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      modalImg.src = '';
      modalImg.alt = '';
    }

    function setOptionalText(el, value) {
      if (!el) return;
      if (value) {
        el.textContent = value;
        el.style.display = '';
      } else {
        el.textContent = '';
        el.style.display = 'none';
      }
    }

    if (modal && modalImg) {
      // open modal from image cards
      root.querySelectorAll('.dg-card--images .dg-card__image-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
          const card = btn.closest('.dg-card--images');
          if (!card) return;

		  const postId   = card.dataset.postId || '';

          // track view
          if (postId) {
            fetch(dgAjax.url, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
              body: new URLSearchParams({
                action: 'ileg_track_view',
                post_id: postId
              })
            }).catch(function () {});
          }

          const src      = card.dataset.modalImg || (card.querySelector('img') ? card.querySelector('img').src : '');
          const title    = card.dataset.modalTitle   || '';
          const excerpt  = card.dataset.modalExcerpt || '';
          const author   = card.dataset.modalAuthor  || '';
          const likesRaw = card.dataset.modalLikes   || '0';
          const likedRaw = card.dataset.modalLiked   === '1';
          const url      = card.dataset.modalUrl     || '';
          const download = card.dataset.modalDownload || src;

          if (!src) return;

          // image + download
          modalImg.src = src;
          modalImg.alt = title || author || '';
          if (modalDownload) {
            modalDownload.href = download;
          }

          // optional text fields
          setOptionalText(modalTitle,   title);
          setOptionalText(modalExcerpt, excerpt);
          setOptionalText(modalAuthor,  author);

          // likes – always show, even when 0
          if (modalLikesWrap && modalLikesCount) {
            const baseLikes = parseInt(likesRaw || '0', 10) || 0;
            modalLikesWrap.dataset.baseLikes = String(baseLikes);
            modalLikesWrap.dataset.liked = likedRaw ? '1' : '0';
            modalLikesWrap.dataset.postId = postId;
            modalLikesCount.textContent = baseLikes + ' likes';

            if (modalLikesIcon) {
              modalLikesIcon.classList.toggle('fa-solid', likedRaw);
              modalLikesIcon.classList.toggle('fa-regular', !likedRaw);
              modalLikesWrap.classList.toggle('is-liked', likedRaw);
            }
          }

          // share – fresh each open
          if (modalShare) {
            modalShare.onclick = function () {
              const shareUrl = url || src;
              if (navigator.share) {
                navigator.share({ title: title || document.title, url: shareUrl }).catch(function(){});
              } else {
                window.prompt('Copy this link to share:', shareUrl);
              }
            };
          }

          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
        });
      });

      // like button toggle (front-end only)
      if (modalLikesWrap && modalLikesIcon && modalLikesCount) {
        modalLikesWrap.addEventListener('click', function () {
          const postId    = modalLikesWrap.dataset.postId || '';
          const baseLikes = parseInt(modalLikesWrap.dataset.baseLikes || '0', 10) || 0;
          const isLiked   = modalLikesWrap.dataset.liked === '1';
          const newLiked  = !isLiked;

          // optimistic UI update
          const displayLikes = Math.max(0, baseLikes + (newLiked ? 1 : -1));
          modalLikesWrap.dataset.liked = newLiked ? '1' : '0';
          modalLikesWrap.dataset.baseLikes = String(displayLikes);
          modalLikesCount.textContent = displayLikes + ' likes';

          if (newLiked) {
            modalLikesWrap.classList.add('is-liked');
            modalLikesIcon.classList.remove('fa-regular');
            modalLikesIcon.classList.add('fa-solid');
          } else {
            modalLikesWrap.classList.remove('is-liked');
            modalLikesIcon.classList.remove('fa-solid');
            modalLikesIcon.classList.add('fa-regular');
          }

          // push to server (logged-in users only)
          if (postId) {
            fetch(dgAjax.url, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
              body: new URLSearchParams({
                action: 'ileg_toggle_like',
                nonce: dgAjax.likeNonce,
                post_id: postId
              })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              if (!data || !data.success || !data.data) return;

              var serverLikes = parseInt(data.data.likes || '0', 10) || 0;
              var serverLiked = !!data.data.liked;

              modalLikesWrap.dataset.baseLikes = String(serverLikes);
              modalLikesWrap.dataset.liked = serverLiked ? '1' : '0';
              modalLikesCount.textContent = serverLikes + ' likes';

              modalLikesWrap.classList.toggle('is-liked', serverLiked);
              modalLikesIcon.classList.toggle('fa-solid', serverLiked);
              modalLikesIcon.classList.toggle('fa-regular', !serverLiked);
            })
            .catch(function () {
              // ignore and keep optimistic state
            });
          }
        });
      }

      [modalClose, modalBackdrop].forEach(function(el){
        if (!el) return;
        el.addEventListener('click', closeModal);
      });

      document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') {
          closeModal();
        }
      });
    }
  }


  const gridEl = root.querySelector('.discover-grid__grid');

  function layoutMasonry() {
	if (!isImageMode || !gridEl) return;

	const visibleCards = cards.filter(function(card) {
		return card.classList.contains('dg-card--images') &&
			card.style.display !== 'none';
	});

	if (!visibleCards.length) {
		gridEl.style.height = '';
		return;
	}

	const gap = 24; // 1.5rem, keep in sync with CSS
	const containerWidth = gridEl.clientWidth;

	// --- fixed responsive steps: 5 -> 4 -> 3 -> 2 -> 1 ---
	let cols;
	if (containerWidth >= 1400) {
		cols = 5;
	} else if (containerWidth >= 1100) {
		cols = 4;
	} else if (containerWidth >= 768) {
		cols = 3;
	} else if (containerWidth >= 576) {
		cols = 2;
	} else {
		cols = 1;
	}

	// card width so columns always fill container
	const cardWidth = (containerWidth - gap * (cols - 1)) / cols;

	const colHeights = new Array(cols).fill(0);

	visibleCards.forEach(function(card) {
		// apply width so they actually use all the space
		card.style.width = cardWidth + 'px';

		// shortest column index
		let targetCol = 0;
		let minHeight = colHeights[0];
		for (let i = 1; i < cols; i++) {
		if (colHeights[i] < minHeight) {
			minHeight = colHeights[i];
			targetCol = i;
		}
		}

		const x = targetCol * (cardWidth + gap);
		const y = colHeights[targetCol];

		card.style.transform = 'translate(' + x + 'px, ' + y + 'px)';

		colHeights[targetCol] += card.offsetHeight + gap;
	});

	gridEl.style.height = Math.max.apply(Math, colHeights) + 'px';
  }

  function render() {
    const filtered   = getFiltered();
    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    cards.forEach(function(card){
      card.style.display = 'none';
      if (isImageMode) {
        card.style.transform = ''; // reset before layout
      }
    });

    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;
    filtered.slice(start, end).forEach(function(card){
      card.style.display = '';
    });

    buildPagination(totalPages);

    if (isImageMode) {
      requestAnimationFrame(layoutMasonry);
    }
  }

  if (isImageMode) {
    window.addEventListener('resize', function() {
      clearTimeout(window._dgMasonryTimer);
      window._dgMasonryTimer = setTimeout(layoutMasonry, 150);
    });

    // optional: re-layout when images finish loading
    root.querySelectorAll('.dg-card--images img').forEach(function(img){
      img.addEventListener('load', function() {
        requestAnimationFrame(layoutMasonry);
      });
    });
  }

  // Share buttons — always credit owner via canonical URL.
  // Share buttons — copy URL to clipboard and show "Copied" feedback.
  root.querySelectorAll('.dg-card__pill--share').forEach(function(btn){
    const labelEl = btn.querySelector('.dg-card__share-label') || btn;
    const originalText = (labelEl.textContent || 'Share').trim();

    function showCopiedFeedback() {
      labelEl.textContent = 'Copied';
      btn.classList.add('is-copied');

      setTimeout(function(){
        btn.classList.remove('is-copied');
        labelEl.textContent = originalText;
      }, 1000); // 1s then revert
    }

    btn.addEventListener('click', function(e){
      e.preventDefault();
      e.stopPropagation();

      const url = btn.dataset.shareUrl || window.location.href;

      // Modern clipboard API
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url)
          .then(showCopiedFeedback)
          .catch(function(){
            // fallback
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (err) {}
            document.body.removeChild(ta);
            showCopiedFeedback();
          });
      } else {
        // older browsers fallback
        const ta = document.createElement('textarea');
        ta.value = url;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (err) {}
        document.body.removeChild(ta);
        showCopiedFeedback();
      }
    });
  });


  // Make entire card clickable (article mode only)
  if (!isImageMode) {
    cards.forEach(function(card){
      const url = card.dataset.url;
      if (!url) return;

      card.addEventListener('click', function(e){
        const target = e.target;
        if (!target) return;
        const interactive = target.closest('a,button');
        if (interactive && interactive.closest('.dg-card') === card) return;
        window.location.href = url;
      });
    });
  }

  // Inline like buttons (article mode)
  if (!isImageMode) {
    const likeButtons = Array.from(root.querySelectorAll('.dg-card__like-btn'));

    function setLikeState(card, btn, icon, countEl, liked, likes) {
      const likeCount = Math.max(0, parseInt(likes || '0', 10) || 0);
      card.dataset.liked = liked ? '1' : '0';
      card.dataset.likes = String(likeCount);
      btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
      btn.classList.toggle('is-liked', liked);
      if (icon) {
        icon.classList.toggle('fa-solid', liked);
        icon.classList.toggle('fa-regular', !liked);
      }
      if (countEl) countEl.textContent = likeCount;
    }

    likeButtons.forEach(function(btn){
      const card = btn.closest('.dg-card');
      if (!card) return;
      const icon  = btn.querySelector('.fa-heart');
      const count = btn.querySelector('.dg-card__like-count');
      const postId = card.dataset.postId || '';

      // sync initial
      setLikeState(card, btn, icon, count, card.dataset.liked === '1', card.dataset.likes || '0');

      if (!postId) return;

      btn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();

        const currentLiked = card.dataset.liked === '1';
        const currentLikes = parseInt(card.dataset.likes || '0', 10) || 0;
        const nextLiked    = !currentLiked;
        const optimistic   = Math.max(0, currentLikes + (nextLiked ? 1 : -1));

        setLikeState(card, btn, icon, count, nextLiked, optimistic);

        fetch(dgAjax.url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({
            action: 'ileg_toggle_like',
            nonce: dgAjax.likeNonce,
            post_id: postId
          })
        })
        .then(function(res){ return res.json(); })
        .then(function(data){
          if (!data || !data.success || !data.data) return;
          const serverLikes = parseInt(data.data.likes || '0', 10) || 0;
          const serverLiked = !!data.data.liked;
          setLikeState(card, btn, icon, count, serverLiked, serverLikes);
        })
        .catch(function(){});
      });
    });

    // meatball menu for download/share
    const menus = Array.from(root.querySelectorAll('.dg-card__more'));

    function closeMenus(except) {
      menus.forEach(function(m){
        if (m !== except) m.classList.remove('is-open');
        const btn = m.querySelector('.dg-card__more-btn');
        if (btn) btn.setAttribute('aria-expanded', m === except && m.classList.contains('is-open') ? 'true' : 'false');
      });
    }

    menus.forEach(function(menu){
      const btn  = menu.querySelector('.dg-card__more-btn');
      const pane = menu.querySelector('.dg-card__more-menu');
      if (!btn || !pane) return;

      btn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        const nowOpen = !menu.classList.contains('is-open');
        closeMenus(nowOpen ? menu : null);
        if (nowOpen) {
          menu.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        } else {
          menu.classList.remove('is-open');
          btn.setAttribute('aria-expanded', 'false');
        }
      });

      pane.querySelectorAll('a,button').forEach(function(action){
        action.addEventListener('click', function(){
          if (action.classList.contains('dg-card__pill--share')) {
            return;
          }
          closeMenus(null);
        });
      });
    });

    document.addEventListener('click', function(e){
      const insideMenu = e.target.closest && e.target.closest('.dg-card__more');
      if (!insideMenu) {
        closeMenus(null);
      }
    });
  }

  render();
})();
</script>
