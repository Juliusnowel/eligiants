<?php
if ( ! defined('ABSPATH') ) { exit; }

$a = wp_parse_args($attributes ?? [], [
  'text'      => 'Read More',
  'url'       => '#',
  'accent'    => '#FD593C',
  'context'   => 'on-image',
  'size'      => 'md',
  'target'    => '',
  'rel'       => '',
  'ariaLabel' => '',
  'className' => ''
]);

$classes = trim(implode(' ', array_filter([
  'cta-wave-card',
  'cta-wave-card--' . sanitize_html_class($a['context']),
  'cta-wave-card--' . sanitize_html_class($a['size']),
  $a['className'] ? sanitize_html_class($a['className']) : ''
])));

$style = '--cta-accent:' . esc_attr($a['accent']) . ';';

$target = $a['target'] ? ' target="' . esc_attr($a['target']) . '"' : '';
$rel    = $a['rel']    ? ' rel="'    . esc_attr($a['rel'])    . '"' : '';
$aria   = $a['ariaLabel'] ? ' aria-label="' . esc_attr($a['ariaLabel']) . '"' : '';

// build the wave label (accessible text via aria-label)
$text = (string) $a['text'];
$chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

echo '<a class="' . esc_attr($classes) . '" href="' . esc_url($a['url']) . '" style="' . esc_attr($style) . '"' . $target . $rel . $aria . '>';
echo '  <span class="btn-wave-inner" aria-hidden="true">';
foreach ($chars as $i => $ch) {
  echo '<span class="wave-char" style="--i:' . intval($i) . '">'
     . ($ch === ' ' ? '&nbsp;' : esc_html($ch))
     . '</span>';
}
echo '  </span>';
echo '</a>';
