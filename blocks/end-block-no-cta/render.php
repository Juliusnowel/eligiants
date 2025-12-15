<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$headline   = trim( $attributes['headline'] ?? '' );
$body       = trim( $attributes['body']     ?? '' );
$bg         = trim( (string) ( $attributes['backgroundColor'] ?? '' ) );
$decor      = is_array( $attributes['decor'] ?? null ) ? $attributes['decor'] : [];

$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';

$wrapper_attributes = get_block_wrapper_attributes( [
  'class' => 'end-block-no-cta child-block ' . $align_class,
  'style' => $bg !== '' ? '--end-bg:' . esc_attr($bg) . ';' : '',
] );
?>
<div <?= $wrapper_attributes; ?>>
  <?php if ( ! empty( $decor ) ) :
    foreach ( $decor as $d ) :
      $url  = isset($d['url'])  ? esc_url($d['url'])  : '';
      if ( $url === '' ) { continue; }
      $alt  = isset($d['alt'])  ? esc_attr($d['alt'])  : '';
      $side = (isset($d['side']) && in_array($d['side'], ['left','right'], true)) ? $d['side'] : 'right';
      $side_class = 'end-block-no-cta__decor--' . $side;
  ?>
    <img class="end-block-no-cta__decor <?= esc_attr($side_class); ?>" src="<?= $url; ?>" alt="<?= $alt; ?>" loading="lazy" decoding="async" />
  <?php endforeach; endif; ?>

  <div class="end-block-no-cta__inner">
    <?php if ( $headline !== '' ): ?>
      <h2 class="end-block-no-cta__title"><?= esc_html( $headline ); ?></h2>
    <?php endif; ?>

    <?php if ( $body !== '' ): ?>
      <p class="end-block-no-cta__body"><?= wp_kses_post( $body ); ?></p>
    <?php endif; ?>
  </div>
</div>
