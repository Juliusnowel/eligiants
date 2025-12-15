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

  $headingIconUrlRaw   = $attributes['headingIconUrl']   ?? '';
  $headingIconAltRaw   = $attributes['headingIconAlt']   ?? '';
  $headingIconAlignRaw = $attributes['headingIconAlign'] ?? 'none';

  $headingIconUrl   = trim((string) $headingIconUrlRaw);
  $headingIconAlt   = trim((string) $headingIconAltRaw);
  $headingIconAlign = in_array($headingIconAlignRaw, ['none','left'], true)
    ? $headingIconAlignRaw
    : 'none';

  $bulletGroupsRaw = $attributes['bulletGroups'] ?? [];
  $bulletGroups    = is_array($bulletGroupsRaw) ? $bulletGroupsRaw : [];

  $footerText = trim((string) ($attributes['footerText'] ?? ''));

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
            <?php
              $has_icon_left = ($headingIconAlign === 'left' && $headingIconUrl !== '');
              $headline_classes_wrap = 'card-2-text-img-resp__headline-wrap';
              if ($has_icon_left) {
                $headline_classes_wrap .= ' has-icon-left';
              }
            ?>
            <div class="<?= esc_attr($headline_classes_wrap); ?>">
              <?php if ($has_icon_left): ?>
                <span class="card-2-text-img-resp__headline-icon">
                  <img src="<?= esc_url($headingIconUrl); ?>"
                      alt="<?= esc_attr($headingIconAlt); ?>"
                      loading="lazy" decoding="async" />
                </span>
              <?php endif; ?>

              <h2 class="card-2-text-img-resp__headline">
                <?php
                  // allow <b>, <strong>, etc. and support \n via CSS
                  echo wp_kses(
                    $headline,
                    [
                      'b'      => [],
                      'strong' => [],
                      'em'     => [],
                      'i'      => [],
                      'span'   => ['class' => []],
                    ]
                  );
                ?>
              </h2>
            </div>
          <?php endif; ?>

          <?php if ($paragraphOne !== ''): ?>
            <p class="card-2-text-img-resp__copy"><?= wp_kses_post($paragraphOne); ?></p>
          <?php endif; ?>
  
          <?php if (!empty($bulletGroups)): ?>
            <div class="card-2-text-img-resp__lists">
              <?php foreach ($bulletGroups as $group):

                // Title
                $groupTitleRaw = $group['title'] ?? '';
                $groupTitle    = trim((string) $groupTitleRaw);

                // New: body text inside the group
                $groupTextRaw  = $group['text'] ?? '';
                $groupText     = trim((string) $groupTextRaw);

                // Items
                $itemsRaw = (isset($group['items']) && is_array($group['items'])) ? $group['items'] : [];
                $items    = array_values(array_filter(array_map('trim', $itemsRaw)));

                // New: footer text for this group only
                $groupFooterRaw = $group['footerText'] ?? '';
                $groupFooter    = trim((string) $groupFooterRaw);

                // If EVERYTHING is empty, skip the group
                if ($groupTitle === '' && $groupText === '' && empty($items) && $groupFooter === '') {
                  continue;
                }
              ?>
                <div class="card-2-text-img-resp__list-group">
                  <?php if ($groupTitle !== ''): ?>
                    <p class="card-2-text-img-resp__list-heading">
                      <?= wp_kses(
                        $groupTitle,
                        [
                          'b'      => ['class' => [], 'style' => []],
                          'strong' => ['class' => [], 'style' => []],
                          'em'     => ['class' => [], 'style' => []],
                          'i'      => ['class' => [], 'style' => []],
                          'span'   => ['class' => [], 'style' => []],
                        ]
                      ); ?>
                    </p>
                  <?php endif; ?>

                  <?php if ($groupText !== ''): ?>
                    <p class="card-2-text-img-resp__list-text">
                      <?= wp_kses_post($groupText); ?>
                    </p>
                  <?php endif; ?>

                  <?php if (!empty($items)): ?>
                    <ul class="card-2-text-img-resp__list">
                      <?php foreach ($items as $li): ?>
                        <li class="card-2-text-img-resp__list-item">
                          <?= wp_kses(
                            $li,
                            [
                              'b'      => ['class' => [], 'style' => []],
                              'strong' => ['class' => [], 'style' => []],
                              'em'     => ['class' => [], 'style' => []],
                              'i'      => ['class' => [], 'style' => []],
                              'span'   => ['class' => [], 'style' => []],
                            ]
                          ); ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>

                  <?php if ($groupFooter !== ''): ?>
                    <p class="card-2-text-img-resp__list-footer">
                      <?= wp_kses_post($groupFooter); ?>
                    </p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
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

          <?php if ($footerText !== ''): ?>
            <p class="card-2-text-img-resp__footer">
              <?= wp_kses_post($footerText); ?>
            </p>
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
