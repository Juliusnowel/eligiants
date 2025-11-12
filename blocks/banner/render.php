<?php
if (!defined('ABSPATH')) { exit; }

/** Polyfill for PHP < 8.1 */
if (!function_exists('array_is_list')) {
  function array_is_list($array) {
    if (!is_array($array)) return false;
    $i = 0; foreach ($array as $k => $_) { if ($k !== $i++) return false; }
    return true;
  }
}

if (!function_exists('child_boolish')) {
  function child_boolish($v){
    if (is_bool($v)) return $v;
    $s = strtolower(trim((string)$v));
    return in_array($s, ['1','true','yes','on'], true);
  }
}

/** Map "h-1|h-2|h-3|body" -> theme font-size class */
if (!function_exists('child_font_size_class')) {
  function child_font_size_class($slug) {
    static $map = [
      'h-1' => 'has-h-1-font-size',
      'h-2' => 'has-h-2-font-size',
      'h-3' => 'has-h-3-font-size',
      'body'=> 'has-body-font-size',
    ];
    $slug = trim((string)$slug);
    return $map[$slug] ?? '';
  }
}

/** Multiline + bullet helper */
if (!function_exists('child_textarea_to_html')) {
  function child_textarea_to_html($txt) {
    $txt = (string)$txt;
    if ($txt === '') return '';
    $lines  = preg_split("/\r\n|\r|\n/", $txt);
    $out    = '';
    $inList = false;
    foreach ($lines as $line) {
      $raw = rtrim($line);
      if (preg_match('/^\s*\*\s?(.*)$/u', $raw, $m)) {
        if (!$inList) { $out .= '<ul class="bullet-list">'; $inList = true; }
        $out .= '<li>' . esc_html($m[1] ?? '') . '</li>';
        continue;
      }
      if ($inList) { $out .= '</ul>'; $inList = false; }
      if ($raw === '') { $out .= '<br>'; }
      else { $out .= '<span>' . esc_html($raw) . '</span><br>'; }
    }
    if ($inList) { $out .= '</ul>'; }
    return $out;
  }
}

$A = (isset($attributes) && is_array($attributes)) ? $attributes : [];

/* detect if totally blank -> hydrate from region json */
$hasAny =
  trim($A['title'] ?? '')       !== '' ||
  trim($A['description'] ?? '') !== '' ||
  trim($A['sub_title'] ?? '')   !== '' ||
  trim($A['img'] ?? '')         !== '' ||
  !empty($A['category_data'])   ||
  !empty($A['category_list']);

if (!$hasAny) {
  if (!function_exists('child_load_region_data')) {
    require_once trailingslashit(get_stylesheet_directory()) . 'inc/region-data.php';
  }
  $data = child_load_region_data();

  $key   = isset($A['dataKey'])   ? (string)$A['dataKey']   : 'banners';
  $index = isset($A['dataIndex']) ? (int)$A['dataIndex']    : 0;

  $loaded = null;
  if (!empty($data[$key]) && is_array($data[$key])) {
    if (array_is_list($data[$key]) && !empty($data[$key][$index]) && is_array($data[$key][$index])) {
      $loaded = $data[$key][$index];
    } elseif (!array_is_list($data[$key])) {
      $loaded = $data[$key];
    }
  }
  if (!$loaded && !empty($data['banner-gradient'])) $loaded = $data['banner-gradient'];
  if (!$loaded && !empty($data['banner_gradient'])) $loaded = $data['banner_gradient'];
  if (!$loaded && !empty($data['banner']))          $loaded = $data['banner'];

  if ($loaded) {
    $A = array_merge([
      'title'        => '',
      'description'  => '',
      'sub_title'    => '',
      'footer_text'  => '',
      'img'          => '',
      'mode'         => 'auto',
      'bgStart'      => '#2ea7e0',
      'bgEnd'        => '#73c6ff',
      'bgDirection'  => '90deg',
      'overlay'      => 'rgba(0,0,0,.10)',
      'hAlign'       => 'left',
      'vAlign'       => 'center',
      'banner_size'  => 'small',
      'showButton'   => false,
      'buttonText'   => '',
      'buttonUrl'    => '',
      'category_data'=> [],
      'category_list'=> [],
      'align'        => $attributes['align'] ?? null,
    ], $loaded);
  }
}

/* --- ALIGN: make sure wrapper sees it, and add class defensively --- */
$align = null;
if (isset($A['align']) && in_array($A['align'], ['wide','full'], true)) {
  $align = $A['align'];
} elseif (isset($attributes['align']) && in_array($attributes['align'], ['wide','full'], true)) {
  $align = $attributes['align'];
}
if ($align !== null) {
  $A['align'] = $align;
  $attributes['align'] = $align; // so get_block_wrapper_attributes prints align classes
}

$has_glass = child_boolish($A['effect'] ?? false);
$extra_class = 'wp-block-child-banner banner';
if ($align === 'full') { $extra_class .= ' alignfull'; }
if ($align === 'wide') { $extra_class .= ' alignwide'; }
if ($has_glass)         { $extra_class .= ' has-glass'; }  

/* ------------------------------------------------------------------ */

/* normalize data / legacy keys */
$title       = (string)($A['title'] ?? '');
$desc        = (string)($A['description'] !== '' ? $A['description'] : ($A['sub_title'] ?? ''));
$footer      = (string)($A['footer_text'] ?? '');
$img         = (string)($A['img'] ?? '');
$mode        = (string)($A['mode'] ?? 'auto');
$bgStart     = (string)($A['bgStart'] ?? '#2ea7e0');
$bgEnd       = (string)($A['bgEnd'] ?? '#73c6ff');
$bgDir       = (string)($A['bgDirection'] ?? '90deg');
$overlay     = (string)($A['overlay'] ?? 'rgba(0,0,0,.10)');
$hAlign      = strtolower(trim((string)($A['hAlign'] ?? ($A['position'] ?? 'left'))));
$vAlign      = strtolower(trim((string)($A['vAlign'] ?? 'center')));
$size        = (string)($A['banner_size'] ?? 'small');

$features    = is_array($A['category_data'] ?? null) ? $A['category_data'] : [];
$feat_list   = is_array($A['category_list'] ?? null) ? $A['category_list'] : [];

$showBtn     = !empty($A['showButton']);
$btnText     = (string)($A['buttonText'] ?? '');
$btnUrl      = (string)($A['buttonUrl'] ?? '');

$is_hero   = !empty($A['hero']);                   
$heading_tag = $is_hero ? 'h1' : 'h2';

// NEW: allow explicit h1/h2/h3 (or keep "auto")
$reqLevel = strtolower(trim((string)($A['headingLevel'] ?? 'auto')));
if (in_array($reqLevel, ['h1','h2','h3'], true)) {
  $heading_tag = $reqLevel;
}

// NEW: optional font-size classes
$title_fs = child_font_size_class($A['titleFont'] ?? '');
$desc_fs  = child_font_size_class($A['descFont']  ?? '');

if ($title === '' && $desc === '' && $img === '' && empty($features) && empty($feat_list)) { return; }

$height  = $size === 'large' ? '703px' : '403px';
$hAlign  = in_array($hAlign, ['left','center','right'], true) ? $hAlign : 'left';
$vAlign  = in_array($vAlign, ['top','center','bottom'], true) ? $vAlign : 'center';

$isGradient = ($mode === 'gradient') || ($mode === 'auto' && $img === '');

$wrapper_attributes = get_block_wrapper_attributes([
  'class'  => $extra_class,
  'style'  => '--h:' . esc_attr($height) . ';',
  'data-h' => esc_attr($hAlign),
  'data-v' => esc_attr($vAlign),
  'data-bg'=> $isGradient ? 'gradient' : 'image',
  'data-size' => esc_attr($size),
]);
?>
<section <?php echo $wrapper_attributes; ?> role="banner">
  <?php if ($isGradient): ?>
    <div class="banner__bg" style="
      background: linear-gradient(<?php echo esc_attr($bgDir); ?>, <?php echo esc_attr($bgStart); ?>, <?php echo esc_attr($bgEnd); ?>);
    "></div>
  <?php else: ?>
    <div class="banner__bg" style="background-image:url('<?php echo esc_url($img); ?>');"></div>
    <div class="banner__overlay" style="background: <?php echo esc_attr($overlay); ?>;"></div>
  <?php endif; ?>

  <div class="banner__frame">
    <div class="banner__container">
      <?php if ($has_glass): ?>
        <div class="banner__glass">
      <?php endif; ?>
      <?php if ($title !== ''): ?>
        <?php $tag = in_array($heading_tag, ['h1','h2','h3'], true) ? $heading_tag : 'h2'; ?>
        <<?= $tag; ?> class="banner__title<?= $title_fs ? ' ' . esc_attr($title_fs) : ''; ?>">
          <?= esc_html($title); ?>
        </<?= $tag; ?>>
      <?php endif; ?>

      <?php if ($desc !== ''): ?>
        <div class="banner__desc<?= $desc_fs ? ' ' . esc_attr($desc_fs) : ''; ?>">
          <?= child_textarea_to_html($desc); ?>
        </div>
      <?php endif; ?>

      <?php if ($has_glass): ?>
        </div> <!-- /.banner__glass -->
      <?php endif; ?>

      <?php if (!empty($features)): ?>
        <div class="banner__features row">
          <?php foreach ($features as $card):
            $cImg  = $card['img'] ?? '';
            $h3    = $card['h3'] ?? '';
            $p     = $card['description'] ?? '';
          ?>
            <article class="feature">
              <?php if ($cImg): ?><img src="<?php echo esc_url($cImg); ?>" alt="<?php echo $h3; ?>" class="icon"><?php endif; ?>
              <?php if ($h3):   ?><h3><?php echo esc_html($h3); ?></h3><?php endif; ?>
              <?php if ($p):    ?><p><?php echo esc_html($p);  ?></p><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($feat_list)): ?>
        <div class="banner__list">
          <ul>
            <?php foreach ($feat_list as $li):
              $line = is_array($li) ? ($li['description'] ?? '') : $li;
              $line = trim((string)$line);
              if ($line === '') continue;
            ?>
              <li><?php echo esc_html($line); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($showBtn && $btnText !== ''): ?>
        <?php if ($btnUrl): ?>
          <a class="banner__btn" href="<?php echo esc_url($btnUrl); ?>"><?php echo esc_html($btnText); ?></a>
        <?php else: ?>
          <button class="banner__btn" type="button"><?php echo esc_html($btnText); ?></button>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($footer !== ''): ?>
        <div class="banner__tip"><?php echo child_textarea_to_html($footer); ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>
