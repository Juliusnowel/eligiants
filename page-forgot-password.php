<?php
/*
 * Template Name: Frontend Forgot Password
 */

// Logged in users don’t need this page
if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/community/' ) );
    exit;
}

// Optional message after submit
$status = isset( $_GET['reset'] ) ? sanitize_text_field( $_GET['reset'] ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_head(); ?>

    <style>
        body.forgot-page {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            font-family: 'Outfit', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .forgot-card-wrapper {
            width: 100%;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .forgot-card {
            max-width: 430px;
            width: 100%;
            background: #FDCD3B;
            border-radius: 24px;
            padding: 2.5rem 2rem 2.25rem;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
            position: relative;
        }

        .forgot-close {
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

        .forgot-close:hover {
            background: #ffffff;
        }

        .forgot-title {
            margin: 0 0 .3rem;
            font-weight: 700;
            color: #fd593c;
            text-align: center;
        }

        .forgot-subtitle {
            margin: 0 0 1.6rem;
            font-weight: 500;
            color: #16324f;
            text-align: center;
            font-size: 0.95rem;
        }

        .forgot-card form p {
            margin: 0 0 1rem;
        }

        .forgot-card label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: .35rem;
            color: #333;
        }

        .forgot-card input[type="text"],
        .forgot-card input[type="email"] {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #ddd;
            padding: .7rem 1rem;
            font-size: 0.95rem;
            outline: none;
        }

        .forgot-card input[type="text"]:focus,
        .forgot-card input[type="email"]:focus {
            border-color: #fd593c;
            box-shadow: 0 0 0 2px rgba(253, 89, 60, 0.25);
        }

        .forgot-submit-btn {
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

        .forgot-submit-btn:hover {
            background: #ff6c46;
        }

        .forgot-info {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #444;
            text-align: center;
        }

        .forgot-alert {
            margin-bottom: 1rem;
            padding: .6rem .8rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .forgot-alert--success {
            background: #e6f7e9;
            color: #166534;
        }

        .forgot-alert--error {
            background: #fee2e2;
            color: #b91c1c;
        }
    </style>
</head>
<body <?php body_class( 'forgot-page' ); ?>>

<div class="forgot-card-wrapper">
    <div class="forgot-card">

        <a
            href="<?php echo esc_url( home_url( '/login/' ) ); ?>"
            class="forgot-close"
            aria-label="Back to login"
        >
            &times;
        </a>

        <h2 class="forgot-title">Forgot Password</h2>
        <p class="forgot-subtitle">
            Enter the email address for your account and we’ll send you a reset link.
        </p>

        <?php if ( $status === 'sent' ) : ?>
            <div class="forgot-alert forgot-alert--success">
                If an account exists with that email, a reset link has been sent.
                Check your inbox and spam folder.
            </div>
        <?php elseif ( $status === 'invalid' ) : ?>
            <div class="forgot-alert forgot-alert--error">
                Something went wrong. Please try again.
            </div>
        <?php endif; ?>

        <form
            name="lostpasswordform"
            id="lostpasswordform"
            action="<?php echo esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>"
            method="post"
        >
            <p>
                <label for="user_login">Email Address</label>
                <input
                    type="email"
                    name="user_login"
                    id="user_login"
                    class="input"
                    value=""
                    size="20"
                    autocomplete="email"
                    required
                />
            </p>

            <p>
                <input
                    type="submit"
                    name="wp-submit"
                    id="wp-submit"
                    class="forgot-submit-btn"
                    value="<?php esc_attr_e( 'Send Reset Link' ); ?>"
                />
                <!-- after WP handles the request, send user back here with a flag -->
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( add_query_arg( 'reset', 'sent', get_permalink() ) ); ?>" />
            </p>
        </form>

        <p class="forgot-info">
            Remember your password?
            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">Back to login</a>
        </p>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
