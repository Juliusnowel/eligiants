<?php
/*
 * Template Name: Frontend Register
 */

// If already logged in, send them away from register page
if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/community/' ) );
    exit;
}

$errors = [];

// Handle form submit BEFORE any HTML output
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['frontend_register_nonce'] ) ) {

    if ( ! wp_verify_nonce( $_POST['frontend_register_nonce'], 'frontend_register' ) ) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $full_name = sanitize_text_field( $_POST['full_name'] ?? '' );
        $email     = sanitize_email( $_POST['email'] ?? '' );
        $pass1     = $_POST['password'] ?? '';
        $pass2     = $_POST['password_confirm'] ?? '';
        $terms     = ! empty( $_POST['terms'] );

        if ( ! $full_name ) {
            $errors[] = 'Full name is required.';
        }
        if ( ! $email ) {
            $errors[] = 'A valid email address is required.';
        } elseif ( email_exists( $email ) ) {
            $errors[] = 'An account with that email already exists.';
        }

        if ( ! $pass1 || ! $pass2 ) {
            $errors[] = 'Password and confirmation are required.';
        } elseif ( $pass1 !== $pass2 ) {
            $errors[] = 'Passwords do not match.';
        }

        if ( ! $terms ) {
            $errors[] = 'You must agree to the Terms and Conditions.';
        }

        if ( ! $errors ) {
            // Build a username from the email local part
            $username = sanitize_user( current( explode( '@', $email ) ), true );
            if ( ! $username ) {
                $username = 'user_' . wp_generate_password( 6, false );
            }

            // Ensure username is unique
            $base = $username;
            $i    = 1;
            while ( username_exists( $username ) ) {
                $username = $base . '_' . $i;
                $i++;
            }

            $user_id = wp_create_user( $username, $pass1, $email );

            if ( is_wp_error( $user_id ) ) {
                $errors[] = $user_id->get_error_message();
            } else {
                // Set display name
                wp_update_user( [
                    'ID'           => $user_id,
                    'display_name' => $full_name,
                ] );

                // Auto-login and redirect to community
                wp_set_current_user( $user_id );
                wp_set_auth_cookie( $user_id );
                wp_safe_redirect( home_url( '/community/' ) );
                exit;
            }
        }
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
        body.register-page {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            font-family: 'Outfit', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .register-card-wrapper {
            width: 100%;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-card {
            max-width: 430px;
            width: 100%;
            background: #FDCD3B;
            border-radius: 24px;
            padding: 2.5rem 2rem 2.25rem;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
            position: relative; 
        }

        .register-close {
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

        .register-close:hover {
            background: #ffffff;
        }

        .register-title {
            margin: 0 0 .2rem;
            font-weight: 700;
            color: #fd593c;
            text-align: center;
        }

        .register-subtitle {
            margin: 0 0 2rem;
            font-weight: 600;
            color: #16324f;
            text-align: center;
        }

        .register-card form .field-group {
            margin-bottom: 1rem;
        }

        /* Hide labels only for the text/email/password fields */
        .register-card .field-group label {
            display: none;
        }

        /* Terms label visible */
        .terms-row label {
            display: inline;
            font-size: 0.9rem;
            color: #333;
        }


        .register-card input[type="text"],
        .register-card input[type="email"],
        .register-card input[type="password"] {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #ddd;
            padding: .7rem 1rem;
            font-size: 0.95rem;
            outline: none;
        }

        .register-card input[type="text"]:focus,
        .register-card input[type="email"]:focus,
        .register-card input[type="password"]:focus {
            border-color: #fd593c;
            box-shadow: 0 0 0 2px rgba(253, 89, 60, 0.25);
        }

        .terms-row {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: 0.9rem;
            margin-top: .3rem;
            margin-bottom: 1.2rem;
        }

        .terms-row input[type="checkbox"] {
            width: auto;
            accent-color: #fd593c;
        }

        .terms-row a {
            color: #fd593c;
            text-decoration: underline;
        }

        .btn-register-submit {
            width: 100%;
            border: none;
            border-radius: 999px;
            background: #fd593c;
            padding: .75rem 1rem;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .btn-register-submit:hover {
            background: #ff6c46;
        }

        .register-errors {
            background: #fff3f2;
            border: 1px solid #f5b1a4;
            color: #8b2317;
            border-radius: 12px;
            padding: .75rem 1rem;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .register-errors ul {
            margin: 0;
            padding-left: 1.1rem;
        }
    </style>
</head>
<body <?php body_class( 'register-page' ); ?>>

<div class="register-card-wrapper">
    <div class="register-card">

        <a
            href="<?php echo esc_url( home_url( '/community/' ) ); ?>"
            class="register-close"
            aria-label="Close register and go back to community"
        >
            &times;
        </a>    

        <h2 class="register-title">Registration</h2>
        <p class="register-subtitle">Create an Account</p>

        <?php if ( $errors ) : ?>
            <div class="register-errors">
                <ul>
                    <?php foreach ( $errors as $error ) : ?>
                        <li><?php echo esc_html( $error ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'frontend_register', 'frontend_register_nonce' ); ?>

            <div class="field-group">
                <label for="full_name">Full Name</label>
                <input type="text"
                       id="full_name"
                       name="full_name"
                       placeholder="Full Name"
                       value="<?php echo isset( $_POST['full_name'] ) ? esc_attr( $_POST['full_name'] ) : ''; ?>"
                       required>
            </div>

            <div class="field-group">
                <label for="reg_email">Email Address</label>
                <input type="email"
                       id="reg_email"
                       name="email"
                       placeholder="Email Address"
                       value="<?php echo isset( $_POST['email'] ) ? esc_attr( $_POST['email'] ) : ''; ?>"
                       required>
            </div>

            <div class="field-group">
                <label for="reg_password">Password</label>
                <input type="password"
                       id="reg_password"
                       name="password"
                       placeholder="Password"
                       required>
            </div>

            <div class="field-group">
                <label for="reg_password_confirm">Confirm Password</label>
                <input type="password"
                       id="reg_password_confirm"
                       name="password_confirm"
                       placeholder="Confirm Password"
                       required>
            </div>

            <div class="terms-row">
                <input type="checkbox" id="terms" name="terms" <?php checked( ! empty( $_POST['terms'] ) ); ?>>
                <label for="terms">
                    I do agree the <a href="#">Terms and Conditions</a> of this site.
                </label>
            </div>

            <button type="submit" class="btn-register-submit">
                Register
            </button>
        </form>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
