<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$heading  = trim((string)($attributes['heading'] ?? ''));
$intro    = (string)($attributes['intro'] ?? '');
$cols_raw = intval($attributes['columns'] ?? 3);
$columns  = max(1, $cols_raw);

$items_raw = $attributes['items'] ?? [];
$items = is_array($items_raw) ? $items_raw : [];

$decor_raw = $attributes['decor'] ?? [];
$decor_items = array_values(array_filter(is_array($decor_raw) ? $decor_raw : [], function($d){
  $side = isset($d['side']) ? strtolower((string)$d['side']) : '';
  $pos  = isset($d['position']) ? strtolower((string)$d['position']) : 'center';
  return !empty($d['url']) && in_array($side, ['left','right'], true) && in_array($pos, ['top','center','bottom'], true);
}));

$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';
$classes = trim(implode(' ', array_filter([
  'travel-tips-grid',
  'child-block',
  $align_class
])));

$style = '--cols:' . $columns . ';';
$wrapper_attributes = get_block_wrapper_attributes([
  'class' => $classes,
  'style' => $style,
]);
?>

<div <?= $wrapper_attributes; ?>>

  <?php foreach ($decor_items as $d):
    $side = strtolower($d['side']);
    $pos  = strtolower($d['position'] ?? 'center');
    $alt  = isset($d['alt']) ? (string)$d['alt'] : '';
  ?>
    <img
      class="travel-tips__decor travel-tips__decor--<?= esc_attr($side); ?> travel-tips__decor--pos-<?= esc_attr($pos); ?>"
      src="<?= esc_url($d['url']); ?>"
      alt="<?= esc_attr($alt); ?>"
      loading="lazy"
      decoding="async"
    />
  <?php endforeach; ?>

  <div class="travel-tips__inner">
    <?php if ($heading): ?>
      <div class="travel-tips__heading">
        <h2 class="travel-tips__title"><?= esc_html($heading); ?></h2>
        <span class="travel-tips__rule" aria-hidden="true"></span>
      </div>
    <?php endif; ?>
    <?php if ($intro): ?>
      <p class="travel-tips__intro"><?= wp_kses_post($intro); ?></p>
    <?php endif; ?>
  </div>

  <div class="travel-tips__grid" role="list" aria-label="Travel tips">
    <?php foreach ($items as $item):
      $icon_url = isset($item['iconUrl']) ? (string)$item['iconUrl'] : '';
      $icon_alt = isset($item['iconAlt']) ? (string)$item['iconAlt'] : '';
      $title    = isset($item['title']) ? (string)$item['title'] : '';
      $body     = isset($item['body']) ? (string)$item['body'] : '';

      $list_items_raw = isset($item['list']) && is_array($item['list']) ? $item['list'] : [];
      $list_items = [];

      foreach ($list_items_raw as $li) {
        if (is_array($li)) {
          $text = isset($li['text']) ? (string)$li['text'] : '';
        } else {
          $text = (string)$li;
        }
        if ($text !== '') {
          $list_items[] = $text;
        }
      }
    ?>
      <article class="travel-tips__card" role="listitem">
        <?php if ($icon_url): ?>
          <div class="travel-tips__icon" aria-hidden="<?= $icon_alt === '' ? 'true' : 'false'; ?>">
            <img src="<?= esc_url($icon_url); ?>" alt="<?= esc_attr($icon_alt); ?>" loading="lazy" decoding="async" />
          </div>
        <?php endif; ?>

        <?php if ($title): ?>
          <h3 class="travel-tips__card-title"><?= esc_html($title); ?></h3>
        <?php endif; ?>

        <?php if ($body): ?>
          <p class="travel-tips__card-body"><?= wp_kses_post($body); ?></p>
        <?php endif; ?>

        <?php if (!empty($list_items)): ?>
          <ul class="travel-tips__list">
            <?php foreach ($list_items as $text): ?>
              <li class="travel-tips__list-item">
                <?= esc_html($text); ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</div>
