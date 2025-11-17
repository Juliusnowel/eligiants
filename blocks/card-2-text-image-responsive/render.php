  <?php
  if ( ! defined( 'ABSPATH' ) ) { exit; }

  $headline       = trim( $attributes['headline']      ?? '' );
  $paragraphOne   = trim( $attributes['paragraphOne']  ?? '' );
  $paragraphTwo   = trim( $attributes['paragraphTwo']  ?? '' );

  $buttonText     = trim( $attributes['buttonText']    ?? '' );
  $buttonUrl      =        $attributes['buttonUrl']    ?? '#';
  $buttonAccent   =        $attributes['buttonAccent'] ?? '#FD593C';
  $buttonTextCol  =        $attributes['textColor']    ?? '#FFFFFF';
  $buttonBorderCol=        $attributes['borderColor']  ?? 'transparent';

  $button2Text    = trim( $attributes['button2Text']   ?? '' );
  $button2Url     =        $attributes['button2Url']   ?? '#';
  $button2Accent  =        $attributes['button2Accent']?? '';
  $button2TextCol =        $attributes['textColor2']   ?? '#FFFFFF';
  $button2BorderCol=       $attributes['borderColor2'] ?? 'transparent';

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

  /** Anim attributes */
  $enterAnimRaw     = $attributes['enterAnim']     ?? 'none';
  $enterAnim        = in_array($enterAnimRaw, ['none','left','right','up','down'], true) ? $enterAnimRaw : 'none';

  $textDecorAnimRaw = $attributes['textDecorAnim'] ?? 'none';
  $textDecorAnim    = in_array($textDecorAnimRaw, ['none','spin','spin-x'], true) ? $textDecorAnimRaw : 'none';

  $textDecorPosRaw = $attributes['textDecorPosition'] ?? 'auto';
  $textDecorPos    = in_array($textDecorPosRaw, ['auto','left','right'], true)
    ? $textDecorPosRaw
    : 'auto';

  $align_class  = isset($attributes['align']) ? 'align' . $attributes['align'] : '';
  $pos_class    = $imagePosition === 'left' ? 'is-img-left' : 'is-img-right';
  $hasSectionBg = $sectionBg !== '';

  $anim_classes = '';
  if ( $enterAnim !== 'none' ) {
    $anim_classes .= ' has-enter-anim enter-from-' . $enterAnim;
  }

  $wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'card-2-text-img-resp child-block ' . $align_class . ' ' . $pos_class
            . ( $hasSectionBg ? ' has-section-bg' : '' )
            . $anim_classes,
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
        <img class="card-2-text-img-resp__decor <?= esc_attr($side_class); ?>"
            src="<?= $url; ?>" alt="<?= $alt; ?>" loading="lazy" decoding="async" />
    <?php endforeach; endif; ?>

    <div class="card-2-text-img-resp__inner">

      <div class="card-2-text-img-resp__col card-2-text-img-resp__text"
          style="--panel-bg: <?= esc_attr($textBg ?: 'transparent'); ?>;
                  --panel-radius: <?= esc_attr($textRadius); ?>px;
                  --panel-pad-y: <?= esc_attr($textPadY); ?>px;">
        <?php
          $panel_classes = 'card-2-text-img-resp__panel';
          $panel_classes .= ($textBg === '' ? ' is-transparent' : ' has-bg');

          /**
           * Attach side class for text decor.
           * auto = decor on the *text side outer edge*:
           *   image left  -> decor right
           *   image right -> decor left
           */
          $effectiveSide = $textDecorPos;
          if ($effectiveSide === 'auto') {
            $effectiveSide = ($imagePosition === 'left') ? 'right' : 'left';
          }

          if ($effectiveSide === 'left') {
            $panel_classes .= ' text-decor-side-left';
          } elseif ($effectiveSide === 'right') {
            $panel_classes .= ' text-decor-side-right';
          }
        ?>
        <div class="<?= esc_attr($panel_classes); ?>">

          <?php if ( ! empty($textDecor) ) :
            $decor_anim_class = '';
            if ($textDecorAnim === 'spin') {
              $decor_anim_class = ' is-decor-spin';
            } elseif ($textDecorAnim === 'spin-x') {
              $decor_anim_class = ' is-decor-spin-x';
            }

            foreach ( $textDecor as $td ) :
              $turl = isset($td['url']) ? esc_url($td['url']) : '';
              if ($turl === '') { continue; }
              $talt = isset($td['alt']) ? esc_attr($td['alt']) : '';
              $pos  = isset($td['pos']) ? strtolower($td['pos']) : 'tl';
              $pos  = in_array($pos, ['tl','tr','bl','br'], true) ? $pos : 'tl'; ?>
              <img class="card-2-text-img-resp__panel-decor card-2-text-img-resp__panel-decor--<?= esc_attr($pos); ?><?= $decor_anim_class ? ' ' . esc_attr($decor_anim_class) : ''; ?>"
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

          <?php if ( ($buttonText !== '' || $button2Text !== '') && $ctaAlign !== 'hidden' ): ?>
            <div class="card-2-text-img-resp__cta-wrap card-2-text-img-resp__cta-wrap--<?= esc_attr($ctaAlign); ?>">

              <?php if ($buttonText !== ''): ?>
                <?= render_block([
                  'blockName'   => 'ilegiants/cta-bounce',
                  'attrs'       => [
                    'text'        => $buttonText,
                    'url'         => ($buttonUrl ?: '#'),
                    'accent'      => $buttonAccent,
                    'textColor'   => $buttonTextCol,
                    'borderColor' => $buttonBorderCol,
                  ],
                  'innerBlocks' => [],
                  'innerHTML'   => '',
                  'innerContent'=> []
                ]); ?>
              <?php endif; ?>

              <?php if ($button2Text !== ''):
                $btn2Accent = $button2Accent !== '' ? $button2Accent : $buttonAccent;
              ?>
                <?= render_block([
                  'blockName'   => 'ilegiants/cta-bounce',
                  'attrs'       => [
                    'text'        => $button2Text,
                    'url'         => ($button2Url ?: '#'),
                    'accent'      => $btn2Accent,
                    'textColor'   => $button2TextCol,
                    'borderColor' => $button2BorderCol,
                  ],
                  'innerBlocks' => [],
                  'innerHTML'   => '',
                  'innerContent'=> []
                ]); ?>
              <?php endif; ?>

            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card-2-text-img-resp__col card-2-text-img-resp__media">
        <?php if ($imageUrl): ?>
          <figure class="card-2-text-img-resp__figure" style="--img-radius: <?= esc_attr($imgRadius); ?>px;">
            <img class="card-2-text-img-resp__image"
                src="<?= esc_url($imageUrl); ?>"
                alt="<?= esc_attr($imageAlt); ?>"
                loading="lazy" decoding="async" />
          </figure>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <script>
  (function(){
    var cards = document.querySelectorAll('.card-2-text-img-resp.has-enter-anim:not(.js-enter-bound)');
    if (!cards.length) return;

    if (!('IntersectionObserver' in window)) {
      cards.forEach(function(el){
        el.classList.add('is-in-view', 'js-enter-bound');
      });
      return;
    }

    if (!window.card2TextImgRespObserver) {
      window.card2TextImgRespObserver = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in-view');
            window.card2TextImgRespObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
    }

    var io = window.card2TextImgRespObserver;

    cards.forEach(function(el){
      el.classList.add('js-enter-bound');
      io.observe(el);
    });
  })();
  </script>
