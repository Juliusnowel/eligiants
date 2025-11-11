<?php
/**
 * Child theme bootstrap
 */
add_action('wp_enqueue_scripts', function () {
  // Parent first
  wp_enqueue_style(
    'parent-style',
    get_template_directory_uri() . '/style.css',
    [],
    wp_get_theme(get_template())->get('Version')
  );

  // Child global
  wp_enqueue_style(
    'ileg-child-style',
    get_stylesheet_uri(),
    ['parent-style'],
    wp_get_theme()->get('Version')
  );

  // Optional component CSS
  wp_enqueue_style(
    'ileg-header',
    get_stylesheet_directory_uri() . '/assets/css/header.css',
    ['ileg-child-style'],
    wp_get_theme()->get('Version')
  );
  wp_enqueue_style(
    'ileg-footer',
    get_stylesheet_directory_uri() . '/assets/css/footer.css',
    ['ileg-child-style'],
    wp_get_theme()->get('Version')
  );

  // Scripts
  wp_enqueue_script(
    'ileg-app',
    get_stylesheet_directory_uri() . '/assets/js/app.js',
    [],
    wp_get_theme()->get('Version'),
    true
  );
});

add_action('wp_enqueue_scripts', function () {
  // Detect common Bootstrap handles (best-effort)
  $css_handles = ['bootstrap', 'bootstrap-css', 'bs5', 'bootstrap-5'];
  $js_handles  = ['bootstrap', 'bootstrap-js', 'bootstrap-bundle', 'bs5'];

  $has_bootstrap_css = array_reduce($css_handles, fn($c,$h)=> $c || wp_style_is($h,'enqueued') || wp_style_is($h,'registered'), false);
  $has_bootstrap_js  = array_reduce($js_handles,  fn($c,$h)=> $c || wp_script_is($h,'enqueued')|| wp_script_is($h,'registered'), false);

  if (!$has_bootstrap_css) {
    wp_enqueue_style(
      'bootstrap', // our handle
      'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
      [],
      '5.3.3'
    );
  }

  if (!$has_bootstrap_js) {
    wp_enqueue_script(
      'bootstrap-bundle', // Popper included
      'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
      [],
      '5.3.3',
      true
    );
  }
}, 20);


add_action('after_setup_theme', function () {
  add_theme_support('title-tag');
  add_theme_support('wp-block-styles');
});

// Auto-register any block with block.json under /blocks/*/
add_action('init', function () {
  $base = get_stylesheet_directory() . '/blocks';
  foreach (glob($base . '/*/block.json') as $json) register_block_type(dirname($json));
});

add_action( 'wp_enqueue_scripts', function () {
  wp_enqueue_style(
    'fa-6',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
    [],
    '6.5.2'
  );
} );

add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style(
    'ilegiants-outfit',
    'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap',
    [],
    null
  );
});
add_action('enqueue_block_editor_assets', function () {
  wp_enqueue_style('ilegiants-outfit-editor', 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap', [], null);
});
