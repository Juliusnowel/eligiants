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
			?>

			<?php if ( $image_mode ) : ?>
				<article class="dg-card dg-card--images"
				         data-title="<?php echo esc_attr( strtolower( $title_safe ) ); ?>"
				         data-cats="<?php echo esc_attr( $data_cats ); ?>"
				         data-modal-title="<?php echo esc_attr( $title_safe ); ?>"
				         data-modal-excerpt="<?php echo esc_attr( $excerpt_safe ); ?>"
				         data-modal-author="<?php echo esc_attr( $author_name ); ?>"
				         data-modal-likes="<?php echo esc_attr( $likes ); ?>"
				         data-modal-url="<?php echo esc_url( $url ); ?>"
				         data-modal-img="<?php echo esc_url( $img ); ?>">
					<div class="dg-card__inner">
						<button type="button" class="dg-card__image-btn">
							<div class="dg-card__image">
								<?php if ( $img ) : ?>
									<img src="<?php echo esc_url( $img ); ?>"
									     alt="<?php echo esc_attr( $title_safe ); ?>">
								<?php else : ?>
									<div class="dg-card__image-placeholder"></div>
								<?php endif; ?>
							</div>
						</button>

						<div class="dg-card__body dg-card__body--images">
							<?php if ( ! empty( $tags_safe ) ) : ?>
								<div class="dg-card__tags">
									<?php echo esc_html( implode( ', ', $tags_safe ) ); ?>
								</div>
							<?php endif; ?>

							<?php if ( $title_safe ) : ?>
								<h3 class="dg-card__title dg-card__title--images">
									<?php echo esc_html( $title_safe ); ?>
								</h3>
							<?php endif; ?>

							<?php if ( $excerpt_safe ) : ?>
								<p class="dg-card__excerpt dg-card__excerpt--images">
									<?php echo esc_html( $excerpt_safe ); ?>
								</p>
							<?php endif; ?>

							<a href="<?php echo esc_url( $download_url ); ?>"
							   class="dg-card__download-icon"
							   download>
								<i class="fa-solid fa-download" aria-hidden="true"></i>
								<span class="screen-reader-text"><?php esc_html_e( 'Download image', 'child' ); ?></span>
							</a>
						</div>
					</div>
				</article>
			<?php else : ?>
				<article class="dg-card"
				         data-title="<?php echo esc_attr( strtolower( $title_safe ) ); ?>"
				         data-cats="<?php echo esc_attr( $data_cats ); ?>">
					<div class="dg-card__inner">

						<div class="dg-card__thumb-wrap">
							<div class="dg-card__image">
								<?php if ( $img ) : ?>
									<img src="<?php echo esc_url( $img ); ?>"
									     alt="<?php echo esc_attr( $title_safe ); ?>">
								<?php else : ?>
									<div class="dg-card__image-placeholder"></div>
								<?php endif; ?>
							</div>

							<?php if ( ! $is_author ) : ?>
								<div class="dg-card__actions">
									<a href="<?php echo esc_url( $download_url ); ?>"
									   class="dg-card__pill dg-card__pill--download"
									   download>
										<?php esc_html_e( 'Download', 'child' ); ?>
									</a>
									<button type="button"
									        class="dg-card__pill dg-card__pill--share"
									        data-share-url="<?php echo esc_url( $url ); ?>"
									        data-share-title="<?php echo esc_attr( $title_safe ); ?>">
										<?php esc_html_e( 'Share', 'child' ); ?>
									</button>
								</div>
							<?php endif; ?>
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

							<?php
							$show_metrics = ( $age_days < $metrics_cutoff );
							if ( $show_metrics ) :
								?>
								<div class="dg-card__meta-footer">
									<span class="dg-card__meta-muted">
										<?php echo esc_html( $likes ); ?> <?php esc_html_e( 'likes', 'child' ); ?>
									</span>

									<span class="dg-card__meta-muted">
										<?php echo esc_html( $views ); ?> <?php esc_html_e( 'Views', 'child' ); ?>
									</span>

									<span class="dg-card__meta-muted">
										<?php echo esc_html( $time_ago ); ?>
									</span>
								</div>
							<?php endif; ?>
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
						<img src="" alt="" class="discover-grid__modal-img" />
					</div>
					<div class="discover-grid__modal-body">
						<h3 class="discover-grid__modal-title"></h3>
						<div class="discover-grid__modal-excerpt"></div>
						<p class="discover-grid__modal-author"></p>
						<div class="discover-grid__modal-meta">
							<span class="discover-grid__modal-likes"></span>
							<button type="button" class="discover-grid__modal-share">
								<i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
								<span class="screen-reader-text"><?php esc_html_e( 'Share image', 'child' ); ?></span>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</section>

<script>
(function(){
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
  if (isImageMode) {
    const modal         = root.querySelector('.discover-grid__modal');
    const modalImg      = modal ? modal.querySelector('.discover-grid__modal-img') : null;
    const modalClose    = modal ? modal.querySelector('.discover-grid__modal-close') : null;
    const modalBackdrop = modal ? modal.querySelector('.discover-grid__modal-backdrop') : null;

    function closeModal() {
      if (!modal || !modalImg) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      modalImg.src = '';
      modalImg.alt = '';
    }

    if (modal && modalImg) {
      root.querySelectorAll('.dg-card--images .dg-card__image-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
          const card = btn.closest('.dg-card--images');
          if (!card) return;

          const src = card.dataset.modalImg || (card.querySelector('img') ? card.querySelector('img').src : '');
          const alt = card.dataset.modalTitle || (card.querySelector('img') ? card.querySelector('img').alt : '');

          if (!src) return;

          modalImg.src = src;
          modalImg.alt = alt;
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
        });
      });

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

  // Share buttons — always credit owner via canonical URL.
  root.querySelectorAll('.dg-card__pill--share').forEach(function(btn){
    btn.addEventListener('click', function() {
      const url   = btn.dataset.shareUrl;
      const title = btn.dataset.shareTitle;
      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function(){});
      } else {
        window.prompt('Copy this link to share:', url);
      }
    });
  });

  render();
})();
</script>
