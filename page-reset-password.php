<?php
/*
 * Template Name: Frontend Reset Password
 */

// Logged-in users don’t need this page
if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/community/' ) );
    exit;
}

$key   = isset( $_REQUEST['key'] )   ? sanitize_text_field( $_REQUEST['key'] )   : '';
$login = isset( $_REQUEST['login'] ) ? sanitize_text_field( $_REQUEST['login'] ) : '';

$errors = '';
$user   = false;

// Validate key + login on first load
if ( $key && $login ) {
    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) ) {
        $errors = 'invalid_key';
    }
} else {
    $errors = 'missing_key';
}

// Handle POST (actual password change)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset( $_POST['ileg_reset_nonce'] ) &&
    wp_verify_nonce( $_POST['ileg_reset_nonce'], 'ileg_reset_password' )
) {
    $key   = isset( $_POST['key'] )   ? sanitize_text_field( $_POST['key'] )   : '';
    $login = isset( $_POST['login'] ) ? sanitize_text_field( $_POST['login'] ) : '';
    $pass1 = isset( $_POST['pass1'] ) ? $_POST['pass1'] : '';
    $pass2 = isset( $_POST['pass2'] ) ? $_POST['pass2'] : '';

    $user = ( $key && $login ) ? check_password_reset_key( $key, $login ) : new WP_Error( 'invalid_key' );

    if ( is_wp_error( $user ) ) {
        $errors = 'invalid_key';
    } elseif ( $pass1 === '' || $pass2 === '' ) {
        $errors = 'empty_pass';
    } elseif ( $pass1 !== $pass2 ) {
        $errors = 'mismatch';
    } else {
        // All good – set the new password
        reset_password( $user, $pass1 );

        // Send them to your nice login page with a flag
        $login_url = add_query_arg( 'password', 'changed', home_url( '/login/' ) );
        wp_redirect( $login_url );
        exit;
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>

    <style>
        body.reset-page {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            font-family: 'Outfit', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .reset-card-wrapper {
            width: 100%;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-card {
            max-width: 430px;
            width: 100%;
            background: #FDCD3B;
            border-radius: 24px;
            padding: 2.5rem 2rem 2.25rem;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
            position: relative;
        }

        .reset-close {
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

        .reset-close:hover {
            background: #ffffff;
        }

        .reset-title {
            margin: 0 0 .3rem;
            font-weight: 700;
            color: #fd593c;
            text-align: center;
        }

        .reset-subtitle {
            margin: 0 0 1.6rem;
            font-weight: 500;
            color: #16324f;
            text-align: center;
            font-size: 0.95rem;
        }

        .reset-card form p {
            margin: 0 0 1rem;
        }

        .reset-card label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: .35rem;
            color: #333;
        }

        .reset-card input[type="password"] {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #ddd;
            padding: .7rem 1rem;
            font-size: 0.95rem;
            outline: none;
        }

        .reset-card input[type="password"]:focus {
            border-color: #fd593c;
            box-shadow: 0 0 0 2px rgba(253, 89, 60, 0.25);
        }

        .reset-submit-btn {
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

        .reset-submit-btn:hover {
            background: #ff6c46;
        }

        .reset-alert {
            margin-bottom: 1rem;
            padding: .6rem .8rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .reset-alert--error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .reset-info {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #444;
            text-align: center;
        }

        .reset-info a {
            color: #fd593c;
            text-decoration: none;
        }

        .reset-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body <?php body_class( 'reset-page' ); ?>>

<div class="reset-card-wrapper">
    <div class="reset-card">
        <a
            href="<?php echo esc_url( home_url( '/login/' ) ); ?>"
            class="reset-close"
            aria-label="Back to login"
        >
            &times;
        </a>

        <h2 class="reset-title">Set New Password</h2>
        <p class="reset-subtitle">
            Choose a new password for your account.
        </p>

        <?php if ( $errors === 'invalid_key' ) : ?>
            <div class="reset-alert reset-alert--error">
                This reset link is invalid or has already been used. Please request a new one.
            </div>
        <?php elseif ( $errors === 'missing_key' ) : ?>
            <div class="reset-alert reset-alert--error">
                Reset link is missing or incomplete. Please use the link from your email.
            </div>
        <?php elseif ( $errors === 'empty_pass' ) : ?>
            <div class="reset-alert reset-alert--error">
                Please enter your new password in both fields.
            </div>
        <?php elseif ( $errors === 'mismatch' ) : ?>
            <div class="reset-alert reset-alert--error">
                The passwords do not match. Please try again.
            </div>
        <?php endif; ?>

        <?php if ( ! $errors || in_array( $errors, array( 'empty_pass', 'mismatch' ), true ) ) : ?>
            <form method="post">
                <?php wp_nonce_field( 'ileg_reset_password', 'ileg_reset_nonce' ); ?>
                <input type="hidden" name="key"   value="<?php echo esc_attr( $key ); ?>" />
                <input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>" />

                <p>
                    <label for="pass1">New password</label>
                    <input type="password"
                           name="pass1"
                           id="pass1"
                           class="input"
                           autocomplete="new-password" />
                </p>

                <p>
                    <label for="pass2">Confirm new password</label>
                    <input type="password"
                           name="pass2"
                           id="pass2"
                           class="input"
                           autocomplete="new-password" />
                </p>

                <p>
                    <input type="submit"
                           class="reset-submit-btn"
                           value="<?php esc_attr_e( 'Save New Password' ); ?>" />
                </p>
            </form>
        <?php endif; ?>

        <p class="reset-info">
            Remembered your password?
            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">Back to login</a>
        </p>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
