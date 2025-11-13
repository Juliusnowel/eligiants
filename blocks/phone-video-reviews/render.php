<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$heading    = isset( $attributes['heading'] )    ? trim( (string) $attributes['heading'] )    : '';
$subheading = isset( $attributes['subheading'] ) ? trim( (string) $attributes['subheading'] ) : '';
$cards      = is_array( $attributes['cards'] ?? null ) ? $attributes['cards'] : [];

$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';

$wrapper = get_block_wrapper_attributes( [
  'class' => 'phone-reviews ' . $align_class . ' child-block',
] );
?>

<div <?= $wrapper; ?>>
  <div class="phone-reviews__header">
    <?php if ( $heading ) : ?>
      <h2 class="phone-reviews__title"><?= esc_html( $heading ); ?></h2>
    <?php endif; ?>

    <?php if ( $subheading ) : ?>
      <p class="phone-reviews__sub"><?= wp_kses_post( $subheading ); ?></p>
    <?php endif; ?>
  </div>

  <?php if ( $cards ) : ?>
    <div class="phone-reviews__grid">
      <?php foreach ( $cards as $card ) :
        $label    = isset( $card['label'] )    ? trim( (string) $card['label'] )    : '';
        $videoUrl = isset( $card['videoUrl'] ) ? trim( (string) $card['videoUrl'] ) : '';

        if ( $videoUrl === '' ) {
          continue;
        }

        // Expect a YouTube embed or watch URL; if it's a plain watch URL, convert to embed quickly.
        $embed_src = $videoUrl;

        if ( strpos( $videoUrl, 'youtube.com/watch' ) !== false ) {
          $parts = wp_parse_url( $videoUrl );
          if ( ! empty( $parts['query'] ) ) {
            parse_str( $parts['query'], $qs );
            if ( ! empty( $qs['v'] ) ) {
              $embed_src = 'https://www.youtube.com/embed/' . rawurlencode( $qs['v'] );
            }
          }
        } elseif ( strpos( $videoUrl, 'youtu.be/' ) !== false ) {
          $parts = wp_parse_url( $videoUrl );
          if ( ! empty( $parts['path'] ) ) {
            $id = ltrim( $parts['path'], '/' );
            $embed_src = 'https://www.youtube.com/embed/' . rawurlencode( $id );
          }
        }
        ?>
        <article
            class="phone-card"
            data-embed-src="<?= esc_url( $embed_src ); ?>"
            data-video-label="<?= esc_attr( $label ?: 'Video review' ); ?>"
            >
            <div class="phone-card__shell">
                <div class="phone-card__screen">
                <iframe
                    src="<?= esc_url( $embed_src ); ?>?rel=0"
                    title="<?= esc_attr( $label ?: 'Video review' ); ?>"
                    loading="lazy"
                    allowfullscreen
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                ></iframe>
                </div>
            </div>
            <?php if ( $label ) : ?>
                <p class="phone-card__label"><?= esc_html( $label ); ?></p>
            <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
   <!-- Cinematic overlay (reused for all cards in this block) -->
  <div class="phone-reviews__overlay" aria-hidden="true">
    <div class="phone-reviews__overlay-backdrop"></div>

    <div class="phone-reviews__overlay-shell">
      <button class="phone-reviews__overlay-close" type="button" aria-label="<?php esc_attr_e( 'Close video', 'childtheme' ); ?>">
        ×
      </button>
      <div class="phone-reviews__overlay-screen">
        <iframe
          src=""
          title=""
          loading="lazy"
          allowfullscreen
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        ></iframe>
      </div>
      <p class="phone-reviews__overlay-label" data-phone-overlay-title></p>
    </div>
  </div>
</div>
</div>
<script>
(function(){
  function initPhoneReviews(block){
    var cards    = block.querySelectorAll('.phone-card');
    var overlay  = block.querySelector('.phone-reviews__overlay');
    if (!cards.length || !overlay) return;

    var frame    = overlay.querySelector('iframe');
    var titleEl  = overlay.querySelector('[data-phone-overlay-title]');
    var backdrop = overlay.querySelector('.phone-reviews__overlay-backdrop');
    var closeBtn = overlay.querySelector('.phone-reviews__overlay-close');

    function openOverlay(card){
      var src = card.getAttribute('data-embed-src') || '';
      if (!src) {
        var inlineIframe = card.querySelector('iframe');
        if (inlineIframe) src = inlineIframe.src;
      }
      if (!src) return;

      // strip existing autoplay and rebuild with autoplay=1
      src = src.replace(/(&|\?)autoplay=\d+/,'');
      var sep = src.indexOf('?') === -1 ? '?' : '&';
      frame.src = src + sep + 'autoplay=1';

      var label = card.getAttribute('data-video-label') || '';
      if (titleEl) titleEl.textContent = label;

      overlay.classList.add('is-open');
      document.documentElement.classList.add('phone-reviews--overlay-open');
    }

    function closeOverlay(){
      overlay.classList.remove('is-open');
      frame.src = ''; // kill playback
      document.documentElement.classList.remove('phone-reviews--overlay-open');
    }

    cards.forEach(function(card){
      card.addEventListener('click', function(e){
        // ignore clicks on links/buttons inside the card
        if (e.target.closest('a, button')) return;
        e.preventDefault();
        openOverlay(card);
      });
    });

    if (backdrop){
      backdrop.addEventListener('click', function(e){
        e.preventDefault();
        closeOverlay();
      });
    }

    if (closeBtn){
      closeBtn.addEventListener('click', function(e){
        e.preventDefault();
        closeOverlay();
      });
    }

    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape' && overlay.classList.contains('is-open')){
        closeOverlay();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.phone-reviews').forEach(initPhoneReviews);
  });
})();
</script>
