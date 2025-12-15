<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$a = wp_parse_args( $attributes ?? [], [
  'text'        => 'Learn More',
  'url'         => '#',
  'accent'      => '#FD593C',
  'textColor'   => '#ffffff',
  'borderColor' => 'transparent',
  'target'      => '',
  'rel'         => '',
  'ariaLabel'   => '',
  'className'   => ''
]);

$classes = trim( 'cta-bounce ' . ( $a['className'] ? sanitize_html_class( $a['className'] ) : '' ) );

$style   =
  '--cta-accent:' . esc_attr( $a['accent'] )      . ';' .
  '--cta-text:'   . esc_attr( $a['textColor'] )   . ';' .
  '--cta-border:' . esc_attr( $a['borderColor'] ) . ';';

$target = $a['target']    ? ' target="' . esc_attr($a['target']) . '"' : '';
$rel    = $a['rel']       ? ' rel="'    . esc_attr($a['rel'])    . '"' : '';
$aria   = $a['ariaLabel'] ? ' aria-label="' . esc_attr($a['ariaLabel']) . '"' : '';

echo '<a class="' . esc_attr($classes) . '" href="' . esc_url($a['url']) . '" style="' . esc_attr($style) . '"' . $target . $rel . $aria . '>';
echo   '<span class="cta-bounce__label">' . esc_html($a['text']) . '</span>';
echo '</a>';
