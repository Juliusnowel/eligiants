<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$title      = (string) ( $attributes['title'] ?? '' );
$sub        = (string) ( $attributes['subheading'] ?? '' );
$buttons    = is_array( $attributes['buttons'] ?? null ) ? $attributes['buttons'] : [];
$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';
$decor       = is_array( $attributes['decor'] ?? null ) ? $attributes['decor'] : [];

$wrapper = get_block_wrapper_attributes( [
  'class' => 'end-block ' . $align_class . ' child-block',
] );
?>
<div <?= $wrapper; ?>>
  <?php if ( ! empty( $decor ) ) :
    foreach ( $decor as $d ) :
      $url  = isset( $d['url'] ) ? esc_url( $d['url'] ) : '';
      if ( $url === '' ) { continue; }
      $alt  = isset( $d['alt'] ) ? esc_attr( $d['alt'] ) : '';
      $side = ( isset( $d['side'] ) && in_array( $d['side'], ['left','right'], true ) ) ? $d['side'] : 'right';
      $side_class = 'end-block__decor--' . $side;
      ?>
      <img class="end-block__decor <?= esc_attr( $side_class ); ?>"
           src="<?= $url; ?>" alt="<?= $alt; ?>" loading="lazy" decoding="async" />
  <?php endforeach; endif; ?>
  <div class="end-block__inner">
    <div class="end-block__copy">
      <?php if ( $title ) : ?>
        <h2 class="end-block__title"><?= esc_html( $title ); ?></h2>
      <?php endif; ?>

      <?php if ( $sub ) : ?>
        <p class="end-block__sub"><?= wp_kses_post( $sub ); ?></p>
      <?php endif; ?>
    </div>

    <?php if ( $buttons ) : ?>
      <div class="end-block__actions" role="group" aria-label="<?= esc_attr__( 'Actions', 'childtheme' ); ?>">
        <?php foreach ( $buttons as $b ) :
          $text   = isset( $b['text'] ) ? (string) $b['text'] : '';
          $url    = isset( $b['url'] ) ? (string) $b['url'] : '#';
          $accent = isset( $b['accent'] ) ? (string) $b['accent'] : '#FD593C';
          if ( $text === '' ) { continue; }

          echo render_block( [
            'blockName'   => 'ilegiants/cta-bounce',
            'attrs'       => [
              'text'   => $text,
              'url'    => $url,
              'accent' => $accent,
            ],
            'innerBlocks'  => [],
            'innerHTML'    => '',
            'innerContent' => [],
          ] );
        endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
