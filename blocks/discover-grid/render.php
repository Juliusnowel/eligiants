<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$A = ( isset( $attributes ) && is_array( $attributes ) ) ? $attributes : [];

/** BASIC ATTRIBUTES */
$title   = isset( $A['title'] ) ? trim( (string) $A['title'] ) : '';
$intro   = isset( $A['intro'] ) ? trim( (string) $A['intro'] ) : '';

$posts_per_page = isset( $A['postsPerPage'] ) ? (int) $A['postsPerPage'] : 12;
$rows_desktop   = isset( $A['rowsDesktop'] ) ? (int) $A['rowsDesktop'] : 3;

$date_mode        = isset( $A['dateFilterMode'] ) ? (string) $A['dateFilterMode'] : 'none';
$metrics_cutoff   = isset( $A['metricsCutoffDays'] ) ? (int) $A['metricsCutoffDays'] : 7;
$show_search      = ! empty( $A['showSearch'] );
$show_cat_filter  = ! empty( $A['showCategoryFilter'] );
$config_cat_slugs = ! empty( $A['categorySlugs'] ) && is_array( $A['categorySlugs'] ) ? array_map( 'sanitize_title', $A['categorySlugs'] ) : [];

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
 * 1) OPTIONAL: LOAD TITLE/INTRO FROM REGION JSON
 *    e.g. mapping from page-discover.html -> inc/region-data.php
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
			// list-style or object-style
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
 * 2) BUILD QUERY (post type: post)
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
	'posts_per_page'      => 100,       // load enough for client-side pagination
	'orderby'             => 'date',
	'order'               => 'DESC',
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
];

if ( ! empty( $date_query ) ) {
	$args['date_query'] = $date_query;
}

// Optional static category constraint from block settings
if ( ! empty( $config_cat_slugs ) ) {
	$args['tax_query'] = [
		[
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => $config_cat_slugs,
			'operator' => 'IN',
		],
	];
}

$query = new WP_Query( $args );
if ( ! $query->have_posts() ) {
	return '';
}

/**
 * 3) COLLECT POSTS + CATEGORY MAP
 */
$items         = [];
$category_map  = []; // slug => name
$now_ts        = current_time( 'timestamp' );
$current_user  = get_current_user_id();

while ( $query->have_posts() ) {
	$query->the_post();
	$post_id = get_the_ID();

	$thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( ! $thumb_url ) {
		$thumb_url = ''; // or your global placeholder
	}

	$author_id   = (int) get_post_field( 'post_author', $post_id );
	$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';

	$posted_ts = get_the_time( 'U', $post_id );
	$age_days  = floor( ( $now_ts - $posted_ts ) / DAY_IN_SECONDS );
	$time_ago  = human_time_diff( $posted_ts, $now_ts ) . ' ago';

	$likes_raw = get_post_meta( $post_id, 'likes', true );
	$views_raw = get_post_meta( $post_id, 'views', true );
	$likes     = $likes_raw !== '' ? (int) $likes_raw : 0;
	$views     = $views_raw !== '' ? (int) $views_raw : 0;

	$cats        = get_the_category( $post_id );
	$cat_slugs   = [];
	if ( $cats && ! is_wp_error( $cats ) ) {
		foreach ( $cats as $c ) {
			$category_map[ $c->slug ] = $c->name;
			$cat_slugs[]             = $c->slug;
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
	];
}
wp_reset_postdata();

if ( empty( $items ) ) {
	return '';
}

/**
 * 4) LAYOUT METRICS
 */
$total_posts      = count( $items );
$posts_per_page   = max( 1, $posts_per_page );
$rows_desktop     = max( 1, $rows_desktop );
$cols_desktop     = max( 1, min( $posts_per_page, (int) ceil( $posts_per_page / $rows_desktop ) ) );
$total_pages_init = max( 1, (int) ceil( $total_posts / $posts_per_page ) );

$instance_id = 'discover_grid_' . wp_generate_uuid4();
$grid_style  = '--cols-desktop:' . $cols_desktop . ';';

$aria_label = $title !== '' ? $title : __( 'Discover posts', 'child' );

ob_start();
?>
<section id="<?php echo esc_attr( $instance_id ); ?>"
         class="discover-grid-block alignwide"
         aria-label="<?php echo esc_attr( $aria_label ); ?>"
         data-per-page="<?php echo esc_attr( $posts_per_page ); ?>">

	<header class="discover-grid__head">
		<?php if ( $title ) : ?>
			<h2 class="discover-grid__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<?php if ( $intro ) : ?>
			<p class="discover-grid__intro"><?php echo esc_html( $intro ); ?></p>
		<?php endif; ?>
	</header>

	<?php
	$has_filters = $show_search || ( $show_cat_filter && ! empty( $category_map ) );
	if ( $has_filters ) :
		?>
		<div class="discover-grid__filters">
			<?php if ( $show_search ) : ?>
				<div class="discover-grid__search-wrap">
					<input type="search"
					       class="discover-grid__search"
					       placeholder="<?php esc_attr_e( 'Search posts…', 'child' ); ?>">
				</div>
			<?php endif; ?>

			<?php if ( $show_cat_filter && ! empty( $category_map ) ) : ?>
				<div class="discover-grid__categories">
					<button type="button"
					        class="discover-grid__cat-btn is-active"
					        data-cat="all">
						<?php esc_html_e( 'All', 'child' ); ?>
					</button>
					<?php foreach ( $category_map as $slug => $name ) : ?>
						<button type="button"
						        class="discover-grid__cat-btn"
						        data-cat="<?php echo esc_attr( $slug ); ?>">
							<?php echo esc_html( $name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="discover-grid__grid"
	     style="<?php echo esc_attr( $grid_style ); ?>">

		<?php foreach ( $items as $i => $item ) : ?>
			<?php
			$title_safe   = $item['title'];
			$excerpt_safe = $item['excerpt'];
			$img          = $item['image_url'];
			$url          = $item['permalink'];
			$author_name  = $item['author_name'];
			$likes        = (int) $item['likes'];
			$views        = (int) $item['views'];
			$time_ago     = $item['time_ago'];
			$age_days     = (int) $item['age_days'];
			$cat_slugs    = $item['categories'];
			$data_cats    = $cat_slugs ? implode( ',', array_map( 'sanitize_title', $cat_slugs ) ) : '';
			$is_author    = ( $item['author_id'] === $current_user );
			$download_url = $img ? $img : $url;
			?>
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

						<div class="dg-card__meta-footer">
							<?php if ( $likes ) : ?>
								<span><?php echo esc_html( $likes ); ?> <?php esc_html_e( 'likes', 'child' ); ?></span>
							<?php endif; ?>
							<?php if ( $views ) : ?>
								<span><?php echo esc_html( $views ); ?> <?php esc_html_e( 'Views', 'child' ); ?></span>
							<?php endif; ?>
							<?php if ( $time_ago ) : ?>
								<span class="dg-card__meta-muted">
									<?php echo esc_html( $time_ago ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<nav class="discover-grid__pagination" aria-label="<?php esc_attr_e( 'Posts pagination', 'child' ); ?>"></nav>
</section>

<script>
(function(){
  const root = document.getElementById('<?php echo esc_js( $instance_id ); ?>');
  if (!root) return;

  const grid        = root.querySelector('.discover-grid__grid');
  const cards       = Array.from(root.querySelectorAll('.dg-card'));
  const searchInput = root.querySelector('.discover-grid__search');
  const catButtons  = Array.from(root.querySelectorAll('.discover-grid__cat-btn'));
  const pagination  = root.querySelector('.discover-grid__pagination');

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

    // hide all
    cards.forEach(function(card){
      card.style.display = 'none';
    });

    // show slice
    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;
    filtered.slice(start, end).forEach(function(card){
      card.style.display = '';
    });

    buildPagination(totalPages);
  }

  // Search
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      currentSearch = searchInput.value || '';
      currentPage = 1;
      render();
    });
  }

  // Category filter
  if (catButtons.length) {
    catButtons.forEach(function(btn){
      btn.addEventListener('click', function() {
        currentCategory = btn.dataset.cat || 'all';
        catButtons.forEach(function(b){
          b.classList.toggle('is-active', b === btn);
        });
        currentPage = 1;
        render();
      });
    });
  }

  // Share buttons – always credit owner via canonical URL
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

  // Initial render
  render();
})();
</script>
