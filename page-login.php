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
            position: relative; /* enable absolute child */
        }

        .login-close {
            position: absolute;
            top: 0.9rem;
            right: 0.9rem;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            color: #fd593c;
            font-size: 1.5rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .login-close:hover {
            background: #ffffff;
        }

        .login-title {
            margin: 0 0 .3rem;
            font-weight: 700;
            color: #fd593c;
            text-align: center;
        }

        .login-subtitle {
            margin: 0 0 2rem;
            font-weight: 600;
            color: #16324f;
            text-align: center;
        }

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

        /* Remember + Forgot row */
        .login-extra-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0.4rem 0 1rem;
        }

        .login-extra-row .remember-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.9rem;
            color: #333;
        }

        .login-extra-row input#rememberme {
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
            text-decoration: none;
            font-weight: 600;
            color: #ff6c46;
            text-decoration: underline;
        }

        .btn-register:hover {
            color: #0EB6D1;
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

        <a
            href="<?php echo esc_url( home_url( '/community/' ) ); ?>"
            class="login-close"
            aria-label="Close login and go back to community"
        >
            &times;
        </a>

        <h2 class="login-title">Log In</h2>
        <p class="login-subtitle">
            Hey, Welcome Back!
        </p>

        <?php if ( isset( $_GET['login'] ) && $_GET['login'] === 'failed' ) : ?>
            <div style="margin-bottom:1rem;padding:.6rem .9rem;border-radius:8px;
                        background:#fee2e2;color:#b91c1c;font-size:0.9rem;">
                Invalid email or password. Please try again.
            </div>
        <?php endif; ?>


        <form
            name="loginform"
            id="loginform"
            action="<?php echo esc_url( wp_login_url( $redirect ) ); ?>"
            method="post"
        >
            <p class="login-username">
                <label for="user_login">Email Address</label>
                <input
                    type="text"
                    name="log"
                    id="user_login"
                    class="input"
                    value=""
                    size="20"
                    autocomplete="username"
                />
            </p>

            <p class="login-password">
                <label for="user_pass">Password</label>
                <input
                    type="password"
                    name="pwd"
                    id="user_pass"
                    class="input"
                    value=""
                    size="20"
                    autocomplete="current-password"
                />
            </p>

            <div class="login-extra-row">
                <label class="remember-label">
                    <input
                        name="rememberme"
                        type="checkbox"
                        id="rememberme"
                        value="forever"
                    />
                    Remember me
                </label>

                <a class="forgot-link" href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>">
                    Forgot Password?
                </a>

            </div>

            <p class="login-submit">
                <input
                    type="submit"
                    name="wp-submit"
                    id="login-submit"
                    class="button button-primary"
                    value="Log In"
                />
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect ); ?>" />
                <input type="hidden" name="testcookie" value="1" />
            </p>
        </form>

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
