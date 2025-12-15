<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$heading        = $attributes['heading'] ?? '';
$subheading     = $attributes['subheading'] ?? '';
$columns        = max(3, min(4, intval($attributes['columns'] ?? 3)));
$bg             = trim( (string) ($attributes['backgroundColor'] ?? '') );
$items          = is_array($attributes['items'] ?? null) ? $attributes['items'] : [];
$sectionCtaText = $attributes['sectionCtaText'] ?? '';
$sectionCtaUrl  = $attributes['sectionCtaUrl']  ?? '';

/** NEW: decor parsing */
$decor_raw = is_array($attributes['decor'] ?? null) ? $attributes['decor'] : [];
$decor = array_values(array_filter($decor_raw, function($d){
  $side = isset($d['side']) ? strtolower((string)$d['side']) : 'left';
  return !empty($d['url']) && in_array($side, ['left','right'], true);
}));

$is_carousel = count($items) > $columns;

$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';
$classes = 'card-region ' . $align_class . ' child-block ' . ( $is_carousel ? ' is-carousel' : ' is-grid' ) . ( $bg !== '' ? ' has-bg' : '' );
$style   = $bg !== '' ? '--region-bg:' . esc_attr($bg) . ';--cols:' . $columns . ';' : '--cols:' . $columns . ';';

$wrapper_attributes = get_block_wrapper_attributes([
  'class' => $classes,
  'style' => $style,
]);

ob_start(); ?>
<div <?= $wrapper_attributes; ?>>

  <?php /* NEW: background decor (supports left/right/both) */ ?>
  <?php foreach ($decor as $d):
    $side = strtolower($d['side'] ?? 'left');
    $alt  = isset($d['alt']) ? (string)$d['alt'] : '';
  ?>
    <div class="card-region__decor card-region__decor--<?= esc_attr($side); ?>" aria-hidden="<?= $alt === '' ? 'true' : 'false'; ?>">
      <img class="card-region__decor-img"
           src="<?= esc_url($d['url']); ?>"
           alt="<?= esc_attr($alt); ?>"
           loading="lazy"
           decoding="async" />
    </div>
  <?php endforeach; ?>

  <div class="card-region__header">
    <?php if ($heading) : ?>
      <h2 class="card-region__title"><?= esc_html($heading); ?></h2>
    <?php endif; ?>
    <?php if ($subheading) : ?>
      <p class="card-region__sub"><?= wp_kses_post($subheading); ?></p>
    <?php endif; ?>
  </div>

  <div class="card-region__track" role="list" aria-label="Destinations">
    <?php foreach ($items as $i => $item):
      $title   = $item['title']      ?? '';
      $excerpt = $item['excerpt']    ?? '';
      $img     = $item['imageUrl']   ?? '';
      $alt     = $item['imageAlt']   ?? ($title ?: 'Card image');
      $btnTxt  = $item['buttonText'] ?? '';
      $btnUrl  = $item['buttonUrl']  ?? '#';
    ?>
      <article class="card-region__item" role="listitem">
        <figure class="card-region__figure">
          <?php if ($img): ?>
            <img class="card-region__image" src="<?= esc_url($img); ?>" alt="<?= esc_attr($alt); ?>" loading="lazy" decoding="async" />
          <?php endif; ?>
          <figcaption class="card-region__overlay">
            <?php if ($title): ?>
              <h3 class="card-region__name"><?= esc_html($title); ?></h3>
            <?php endif; ?>
            <?php if ($excerpt): ?> 
              <p class="card-region__blurb"><?= wp_kses_post($excerpt); ?></p>
            <?php endif; ?>
            <?php if ($btnTxt): ?>
              <p class="card-region__cta">
                <?= render_block([
                  'blockName'   => 'ilegiants/cta-wave-card',
                  'attrs'       => [
                    'text'    => $btnTxt,
                    'url'     => $btnUrl,
                    'context' => 'on-image',
                    'size'    => 'md',
                    'accent'  => 'var(--accent, #FD593C)'
                  ],
                  'innerBlocks'  => [],
                  'innerHTML'    => '',
                  'innerContent' => []
                ]); ?>
              </p>
            <?php endif; ?>
          </figcaption>
        </figure>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($sectionCtaText && $sectionCtaUrl): ?>
	<div class="card-region__footer">
		<a class="card-region__section-cta card-region__section-cta--flip"
		href="<?= esc_url($sectionCtaUrl); ?>"
		aria-label="<?= esc_attr($sectionCtaText); ?>">
		<span class="cta-flip__inner">
			<!-- FRONT: label -->
			<span class="cta-flip__face cta-flip__front">
				<span class="card-region__section-text"><?= esc_html($sectionCtaText); ?></span>
			</span>

			<!-- BACK: map icon or image (choose one) -->
			<span class="cta-flip__face cta-flip__back" aria-hidden="true">
			<!-- Option A: Font Awesome -->
			<i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>

			<!-- Option B: fallback image (uncomment if not using FA) -->
			<!-- <img src="<?= esc_url( get_stylesheet_directory_uri() . '/assets/images/map.png' ); ?>" alt="" /> -->
			</span>
		</span>
		</a>
	</div>
  <?php endif; ?>

</div>