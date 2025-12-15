<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$heading    = $attributes['heading']    ?? '';
$subheading = $attributes['subheading'] ?? '';
$cols_raw   = intval($attributes['columns'] ?? 3);
$columns    = max(1, $cols_raw);
$items      = is_array($attributes['items'] ?? null) ? $attributes['items'] : [];
$cardBg     = trim((string)($attributes['cardBg'] ?? '#fff'));

/** NEW: decor parsing */
$decor_raw = is_array($attributes['decor'] ?? null) ? $attributes['decor'] : [];
$decor = array_values(array_filter($decor_raw, function($d){
  $side = isset($d['side']) ? strtolower((string)$d['side']) : '';
  return !empty($d['url']) && in_array($side, ['left','right'], true);
}));

$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';
$classes = trim(implode(' ', array_filter([
  'card-about',
  $align_class,     
  'child-block'
])));
$style   = '--cols:' . $columns . ';--card-bg:' . esc_attr($cardBg) . ';';

$wrapper_attributes = get_block_wrapper_attributes([
  'class' => $classes,
  'style' => $style,
]);
?>
<div <?= $wrapper_attributes; ?>>

  <?php /* NEW: background decor (supports left/right/both) */ ?>
  <?php foreach ($decor as $d):
    $side = strtolower($d['side'] ?? 'left');
    $alt  = isset($d['alt']) ? (string)$d['alt'] : '';
  ?>
    <div class="card-about__decor card-about__decor--<?= esc_attr($side); ?>" aria-hidden="<?= $alt === '' ? 'true' : 'false'; ?>">
      <img class="card-about__decor-img"
           src="<?= esc_url($d['url']); ?>"
           alt="<?= esc_attr($alt); ?>"
           loading="lazy"
           decoding="async" />
    </div>
  <?php endforeach; ?>

  <div class="card-about__header">
    <?php if ($heading): ?>
      <h2 class="card-about__title"><?= esc_html($heading); ?></h2>
    <?php endif; ?>
    <?php if ($subheading): ?>
      <p class="card-about__sub"><?= wp_kses_post($subheading); ?></p>
    <?php endif; ?>
  </div>

  <div class="card-about__track" role="list" aria-label="About Cards">
    <?php foreach ($items as $i => $item):
      $title   = $item['title']      ?? '';
      $excerpt = $item['excerpt']    ?? '';
      $btnTxt  = $item['buttonText'] ?? '';
      $btnUrl  = $item['buttonUrl']  ?? '#';
      $iconUrl = $item['iconUrl']    ?? '';
      $iconAlt = $item['iconAlt']    ?? ($title ? $title . ' icon' : 'Icon');
      $hasBadge = !empty($iconUrl);
    ?>
    <article class="card-about__item" role="listitem">
      <div class="card-about__card<?= $hasBadge ? ' card-about__card--has-badge' : '' ?>">
        <?php if ($iconUrl): ?>
          <div class="card-about__badge" aria-hidden="<?= $iconAlt === '' ? 'true' : 'false'; ?>">
            <img class="card-about__badge-img" src="<?= esc_url($iconUrl); ?>" alt="<?= esc_attr($iconAlt); ?>" loading="lazy" decoding="async" />
          </div>
        <?php endif; ?>

        <?php if ($title): ?>
          <h3 class="card-about__name">
            <?= esc_html($title); ?>
            <span class="card-about__rule" aria-hidden="true"></span>
          </h3>
        <?php endif; ?>

        <?php if ($excerpt): ?>
          <p class="card-about__blurb"><?= wp_kses_post($excerpt); ?></p>
        <?php endif; ?>

        <?php if ($btnTxt): ?>
          <p class="card-about__cta-wrap">
            <a class="card-about__cta" href="<?= esc_url($btnUrl); ?>"><?= esc_html($btnTxt); ?></a>
          </p>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</div>
