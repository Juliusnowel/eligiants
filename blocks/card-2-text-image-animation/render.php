<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$headline       = trim( $attributes['headline']      ?? '' );
$paragraphOne   = trim( $attributes['paragraphOne']  ?? '' );
$paragraphTwo   = trim( $attributes['paragraphTwo']  ?? '' );

$buttonText     = trim( $attributes['buttonText']    ?? '' );
$buttonUrl      =        $attributes['buttonUrl']    ?? '#';
$buttonAccent   =        $attributes['buttonAccent'] ?? '#FD593C';
$ctaAlignRaw    =        $attributes['ctaAlign']     ?? 'right';
$ctaAlign       = in_array($ctaAlignRaw, ['left','right','hidden'], true) ? $ctaAlignRaw : 'right';

$imageUrl       = $attributes['imageUrl']            ?? '';
$imageAlt       = $attributes['imageAlt']            ?? '';
$imgRadius      = isset($attributes['imageBorderRadius']) ? (int)$attributes['imageBorderRadius'] : 24;

$imagePosition  = in_array(($attributes['imagePosition'] ?? 'right'), ['left','right'], true)
                  ? $attributes['imagePosition'] : 'right';

$sectionBg      = trim((string)($attributes['backgroundColor'] ?? ''));
$textBg         = trim((string)($attributes['textBgColor'] ?? ''));
$textPadY       = is_numeric($attributes['textPadY'] ?? null) ? (int)$attributes['textPadY'] : 28;
$textRadius     = isset($attributes['textBorderRadius']) ? (int)$attributes['textBorderRadius'] : 16;

$decor          = is_array($attributes['decor']     ?? null) ? $attributes['decor']     : [];
$textDecor      = is_array($attributes['textDecor'] ?? null) ? $attributes['textDecor'] : [];

$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';
$pos_class   = $imagePosition === 'left' ? 'is-img-left' : 'is-img-right';
$hasSectionBg = $sectionBg !== '';

$wrapper_attributes = get_block_wrapper_attributes([
  'class' => 'card-2-text-img-resp child-block ' . $align_class . ' ' . $pos_class . ( $hasSectionBg ? ' has-section-bg' : '' ),
  'style' => ($hasSectionBg ? '--card-bg:' . esc_attr($sectionBg) . ';' : '')
]);

ob_start(); ?>
<div <?= $wrapper_attributes; ?>>

  <?php if ( ! empty($decor) ) :
    foreach ( $decor as $d ) :
      $url  = isset($d['url'])  ? esc_url($d['url'])  : '';
      if ($url === '') { continue; }
      $alt  = isset($d['alt'])  ? esc_attr($d['alt']) : '';
      $side = (isset($d['side']) && in_array($d['side'], ['left','right'], true)) ? $d['side'] : 'right';
      $side_class = 'card-2-text-img-resp__decor--' . $side; ?>
      <img class="card-2-text-img-resp__decor <?= esc_attr($side_class); ?>" src="<?= $url; ?>" alt="<?= $alt; ?>" loading="lazy" decoding="async" />
  <?php endforeach; endif; ?>

  <div class="card-2-text-img-resp__inner">

    <div class="card-2-text-img-resp__col card-2-text-img-resp__text"
         style="--panel-bg: <?= esc_attr($textBg ?: 'transparent'); ?>;
                --panel-radius: <?= esc_attr($textRadius); ?>px;
                --panel-pad-y: <?= esc_attr($textPadY); ?>px;">
      <?php
        $panel_classes = 'card-2-text-img-resp__panel';
        $panel_classes .= ($textBg === '' ? ' is-transparent' : ' has-bg');
      ?>
      <div class="<?= esc_attr($panel_classes); ?>">
        <?php if ( ! empty($textDecor) ) :
          foreach ( $textDecor as $td ) :
            $turl = isset($td['url']) ? esc_url($td['url']) : '';
            if ($turl === '') { continue; }
            $talt = isset($td['alt']) ? esc_attr($td['alt']) : '';
            $pos  = isset($td['pos']) ? strtolower($td['pos']) : 'tl';
            $pos  = in_array($pos, ['tl','tr','bl','br'], true) ? $pos : 'tl'; ?>
            <img class="card-2-text-img-resp__panel-decor card-2-text-img-resp__panel-decor--<?= esc_attr($pos); ?>"
                 src="<?= $turl; ?>" alt="<?= $talt; ?>" loading="lazy" decoding="async" />
        <?php endforeach; endif; ?>

        <?php if ($headline !== ''): ?>
          <h2 class="card-2-text-img-resp__headline"><?= esc_html($headline); ?></h2>
        <?php endif; ?>

        <?php if ($paragraphOne !== ''): ?>
          <p class="card-2-text-img-resp__copy"><?= wp_kses_post($paragraphOne); ?></p>
        <?php endif; ?>

        <?php if ($paragraphTwo !== ''): ?>
          <p class="card-2-text-img-resp__copy"><?= wp_kses_post($paragraphTwo); ?></p>
        <?php endif; ?>

        <?php if ($buttonText !== '' && $ctaAlign !== 'hidden'): ?>
          <div class="card-2-text-img-resp__cta-wrap card-2-text-img-resp__cta-wrap--<?= esc_attr($ctaAlign); ?>">
            <?= render_block([
              'blockName'   => 'ilegiants/cta-bounce',
              'attrs'       => [ 'text' => $buttonText, 'url' => ($buttonUrl ?: '#'), 'accent' => $buttonAccent ],
              'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []
            ]); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-2-text-img-resp__col card-2-text-img-resp__media">
      <?php if ($imageUrl): ?>
        <figure class="card-2-text-img-resp__figure" style="--img-radius: <?= esc_attr($imgRadius); ?>px;">
          <img class="card-2-text-img-resp__image" src="<?= esc_url($imageUrl); ?>" alt="<?= esc_attr($imageAlt); ?>" loading="lazy" decoding="async" />
        </figure>
      <?php endif; ?>
    </div>

  </div>
</div>
