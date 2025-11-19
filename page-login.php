<?php
/*
 * Template Name: Frontend Login
 */

// Redirect logged-in users away from login page
if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/community/' ) );
    exit;
}

// Where to send the user after login
$redirect = ! empty( $_GET['redirect_to'] )
    ? esc_url_raw( $_GET['redirect_to'] )
    : home_url( '/community/' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_head(); ?>

    <style>
        body.login-page {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            font-family: 'Outfit', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .login-card-wrapper {
            width: 100%;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            max-width: 430px;
            width: 100%;
            background: #FDCD3B;
            border-radius: 24px;
            padding: 2.5rem 2rem 2.25rem;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
        }

        .login-title {
            margin: 0 0 .3rem;
            font-weight: 700;
            color: #fd593c;
        }

        .login-subtitle {
            margin: 0 0 2rem;
            font-weight: 600;
            color: #16324f;
        }

        /* WordPress login form structure */
        .login-card form p {
            margin: 0 0 1rem;
        }

        .login-card label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: .35rem;
            color: #333;
        }

        .login-card input[type="text"],
        .login-card input[type="password"] {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #ddd;
            padding: .7rem 1rem;
            font-size: 0.95rem;
            outline: none;
        }

        .login-card input[type="text"]:focus,
        .login-card input[type="password"]:focus {
            border-color: #fd593c;
            box-shadow: 0 0 0 2px rgba(253, 89, 60, 0.25);
        }

        /* Remember me row */
        .login-card .login-remember label {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .login-card input#rememberme {
            width: auto;
        }

        /* Login button */
        #login-submit {
            width: 100%;
            border: none;
            border-radius: 999px;
            background: #fd593c;
            padding: .7rem 1rem;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            margin-top: .4rem;
        }

        #login-submit:hover {
            background: #ff6c46;
        }

        .forgot-link {
            font-size: 0.9rem;
            color: #fd593c;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-meta-row {
            display: flex;
            justify-content: flex-end;
            margin-top: .25rem;
            margin-bottom: 1rem;
        }

        .register-row {
            margin-top: 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .6rem;
            font-size: 0.95rem;
        }

        .btn-register {
            display: inline-block;
            border-radius: 999px;
            padding: .6rem 2.4rem;
            background: #fd593c;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-register:hover {
            background: #ff6c46;
            color: #fff;
        }

        @media (min-width: 576px) {
            .register-row {
                flex-direction: row;
                justify-content: center;
            }
        }
    </style>
</head>
<body <?php body_class( 'login-page' ); ?>>

<div class="login-card-wrapper">
    <div class="login-card">
        <h2 class="login-title text-center">Log In</h2>
        <p class="login-subtitle text-center">
            Hey, Welcome Back!
        </p>

        <?php
        wp_login_form( [
            'redirect'       => $redirect,
            'label_username' => __( 'Email Address' ),
            'label_password' => __( 'Password' ),
            'label_remember' => __( 'Remember me' ),
            'label_log_in'   => __( 'Log In' ),
            'remember'       => true,
            'id_submit'      => 'login-submit',
        ] );
        ?>

        <div class="login-meta-row">
            <a class="forgot-link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
                Forgot Password?
            </a>
        </div>

        <?php
        $register_page = get_page_by_path( 'community-register' );
        $register_url  = $register_page ? get_permalink( $register_page ) : wp_registration_url();
        ?>
        <div class="register-row">
            <span>Don’t have an account?</span>
            <a class="btn-register" href="<?php echo esc_url( $register_url ); ?>">
                Register
            </a>
        </div>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
