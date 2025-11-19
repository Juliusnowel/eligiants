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
  // wp_enqueue_style(
  //   'ileg-header-community',
  //   get_stylesheet_directory_uri() . '/assets/css/header.css',
  //   ['ileg-child-style'],
  //   wp_get_theme()->get('Version')
  // );
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

add_filter( 'template_include', function( $template ) {
  if ( is_page( 'community-register' ) ) {  
      $custom = get_stylesheet_directory() . '/page-register.php';
      if ( file_exists( $custom ) ) {
          return $custom;
      }
  }

  return $template;
} );

add_action( 'wp_enqueue_scripts', function () {

    // Make sure this runs after your main scripts are registered
    wp_enqueue_script(
        'ileg-auth-header',
        get_stylesheet_directory_uri() . '/assets/js/auth-header.js',
        [],
        wp_get_theme()->get( 'Version' ),
        true
    );

    // Resolve URLs
    $login_page = get_page_by_path( 'login' );
    $login_url  = $login_page ? get_permalink( $login_page ) : wp_login_url();

    // Adjust this if your register slug is different
    $profile_url = home_url( '/community/profile/' ); // placeholder

    wp_localize_script( 'ileg-auth-header', 'IlegAuth', [
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl'   => $login_url,
        'logoutUrl'  => wp_logout_url( home_url( '/community/' ) ),
        'profileUrl' => $profile_url,
    ] );
}, 25 );

/**
 * Handle frontend profile update
 */
add_action( 'admin_post_ileg_update_profile', 'ileg_handle_profile_update' );
add_action( 'admin_post_nopriv_ileg_update_profile', 'ileg_handle_profile_update_guest' );

function ileg_handle_profile_update_guest() {
    wp_redirect( wp_login_url( home_url( '/community/profile/' ) ) );
    exit;
}

function ileg_handle_profile_update() {
    if ( ! is_user_logged_in() ) {
        wp_redirect( wp_login_url( home_url( '/community/profile/' ) ) );
        exit;
    }

    if (
        ! isset( $_POST['ileg_profile_nonce'] ) ||
        ! wp_verify_nonce( $_POST['ileg_profile_nonce'], 'ileg_profile_update' )
    ) {
        wp_die( 'Security check failed.' );
    }

    $user_id       = get_current_user_id();
    $redirect_base = wp_get_referer() ?: home_url( '/community/profile/' );

    $userdata = [ 'ID' => $user_id ];

    if ( isset( $_POST['display_name'] ) ) {
        $userdata['display_name'] = sanitize_text_field( $_POST['display_name'] );
    }
    if ( isset( $_POST['user_email'] ) ) {
        $userdata['user_email'] = sanitize_email( $_POST['user_email'] );
    }

    // Password
    $pass1 = isset( $_POST['pass1'] ) ? $_POST['pass1'] : '';
    $pass2 = isset( $_POST['pass2'] ) ? $_POST['pass2'] : '';

    if ( $pass1 || $pass2 ) {
        if ( $pass1 !== $pass2 ) {
            wp_redirect( add_query_arg( 'profile-error', 'password_mismatch', $redirect_base ) );
            exit;
        }
        $userdata['user_pass'] = $pass1;
    }

    $result = wp_update_user( $userdata );
    if ( is_wp_error( $result ) ) {
        wp_redirect( add_query_arg( 'profile-error', $result->get_error_code(), $redirect_base ) );
        exit;
    }

    // Extra meta
    if ( isset( $_POST['first_name'] ) ) {
        update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
    }
    if ( isset( $_POST['last_name'] ) ) {
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['last_name'] ) );
    }

    if ( isset( $_GET['submit-success'] ) && $_GET['submit-success'] === '1' ) : ?>
        <div class="alert alert-success mb-4">
            Your post has been submitted for review.
        </div>
    <?php endif;

    wp_redirect( add_query_arg( 'profile-updated', '1', $redirect_base ) );
    exit;
}

/**
 * Frontend "Submit Post" handler
 */
add_action( 'admin_post_community_submit_post', 'ileg_handle_community_submit_post' );
add_action( 'admin_post_nopriv_community_submit_post', 'ileg_handle_community_submit_post_guest' );

function ileg_handle_community_submit_post_guest() {
    // Force guests to log in before posting
    $redirect = wp_get_referer() ?: home_url( '/community/submit-post/' );
    wp_redirect( wp_login_url( $redirect ) );
    exit;
}

function ileg_handle_community_submit_post() {
    if ( ! is_user_logged_in() ) {
        $redirect = wp_get_referer() ?: home_url( '/community/submit-post/' );
        wp_redirect( wp_login_url( $redirect ) );
        exit;
    }

    // Nonce check
    if (
        ! isset( $_POST['community_post_nonce'] ) ||
        ! wp_verify_nonce( $_POST['community_post_nonce'], 'community_submit_post' )
    ) {
        wp_die( 'Security check failed. Please reload the page and try again.' );
    }

    $redirect_base = wp_get_referer() ?: home_url( '/community/submit-post/' );

    // Sanitize inputs
    $title   = isset( $_POST['post_title'] )   ? sanitize_text_field( $_POST['post_title'] )   : '';
    $content = isset( $_POST['post_content'] ) ? wp_kses_post( $_POST['post_content'] )        : '';

    if ( $title === '' || $content === '' ) {
        wp_redirect( add_query_arg( 'submit-error', 'missing_fields', $redirect_base ) );
        exit;
    }

    // Insert post
    $post_id = wp_insert_post( [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'pending',          // or 'draft' / 'publish' per your workflow
        'post_type'    => 'post',
        'post_author'  => get_current_user_id(),
    ], true );

    if ( is_wp_error( $post_id ) ) {
        wp_redirect( add_query_arg( 'submit-error', 'insert_failed', $redirect_base ) );
        exit;
    }

    // Redirect to profile (or wherever) with success flag
    $success_target = home_url( '/community/profile/' );
    wp_redirect( add_query_arg( 'submit-success', '1', $success_target ) );
    exit;
}

add_action( 'wp_enqueue_scripts', function () {
    // Only load on the submit post page to keep things lean
    if ( ! is_page( 'community-submit-post' ) && ! is_page( 'submit-post' ) ) {
        return;
    }

    wp_enqueue_script(
        'ileg-submit-post',
        get_stylesheet_directory_uri() . '/assets/js/submit-post.js',
        [],
        wp_get_theme()->get( 'Version' ),
        true
    );

    wp_localize_script( 'ileg-submit-post', 'IlegSubmitPost', [
        'nonce' => wp_create_nonce( 'community_submit_post' ),
    ] );
}, 30 );

add_filter( 'show_admin_bar', '__return_false' );

