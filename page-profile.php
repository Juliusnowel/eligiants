<?php
/*
 * Template Name: Community Profile
 */

if ( ! is_user_logged_in() ) {
    // Redirect guests to login with redirect back to profile
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

$first_name = get_user_meta( $user_id, 'first_name', true );
$last_name  = get_user_meta( $user_id, 'last_name', true );
$full_name  = trim( $first_name . ' ' . $last_name );
if ( $full_name === '' ) {
    $full_name = $current_user->display_name;
}

$avatar_url = get_avatar_url( $user_id, [ 'size' => 120 ] );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>

    <style>
        .community-profile-wrapper {
            background: #ffffff;
        }

        .profile-card {
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
        }

        .profile-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .profile-avatar-box {
            width: 96px;
            height: 96px;
            border-radius: 24px;
            background: #f3f3f3;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-avatar-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-summary-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .profile-summary-subtitle {
            margin: 0;
            color: #777;
        }

        .profile-edit-btn {
            background: #fd593c;
            border-radius: 50px;
            color: #fff;
            min-width: 110px;
            border: 2px solid #fd593c;
            align-self: center;
        }

        .profile-edit-btn:hover {
            border: 2px solid #fd593c;
            background: #fff;
            color: #f12f0dff;
        }

        .profile-info-grid {
            margin-top: 1.75rem;
        }

        .profile-info-box {
            border-radius: 16px;
            border: 1px solid #ddd;
            padding: 1rem 1.25rem;
            background: #fff;
        }

        .profile-info-label {
            display: block;
            font-weight: 600;
            color: #fd593c;
            margin-bottom: 0.25rem;
        }

        .profile-info-value {
            font-weight: 500;
            color: #16324f;
        }

        .profile-edit-form {
            margin-top: 2rem;
            border-top: 1px dashed #ddd;
            padding-top: 1.5rem;
        }

        .profile-posts-grid .profile-post-card {
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .profile-post-thumb-wrapper {
            display: block;
            background: #f4f4f4;
            aspect-ratio: 4 / 3;
            overflow: hidden;
        }

        .profile-post-thumb-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-post-body {
            padding: 1rem 1.25rem 1.2rem;
        }

        .profile-post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .profile-post-category {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            background: #fd593c;
            color: #fff;
        }

        .profile-post-status {
            text-transform: capitalize;
        }

        .profile-post-title {
            font-size: 0.98rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .profile-post-title a {
            text-decoration: none;
            color: #16324f;
        }

        .profile-post-excerpt {
            font-size: 0.85rem;
            color: #555;
            margin: 0;
        }

        h2 {
            color: #fd593c;
        }

        @media (max-width: 767.98px) {
            .profile-dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-edit-btn {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body <?php body_class(); ?>>

<?php
// Header template part
if ( function_exists( 'do_blocks' ) ) {
    echo do_blocks( '<!-- wp:template-part {"slug":"header-community","tagName":"header"} /-->' );
}
?>

<main class="community-profile-wrapper py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">

                <?php
                // Status messages
                if ( isset( $_GET['profile-updated'] ) && $_GET['profile-updated'] === '1' ) : ?>
                    <div class="alert alert-success mb-4">
                        Profile updated successfully.
                    </div>
                <?php endif; ?>

                <?php if ( isset( $_GET['profile-error'] ) ) : ?>
                    <div class="alert alert-danger mb-4">
                        <?php
                        switch ( $_GET['profile-error'] ) {
                            case 'password_mismatch':
                                echo 'Passwords do not match.';
                                break;
                            default:
                                echo 'There was a problem updating your profile.';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- PROFILE HEADER CARD -->
                <div class="profile-card submit-card p-4 p-md-5 mb-4">
                    <div class="profile-dashboard-header mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-avatar-box">
                                <?php if ( $avatar_url ) : ?>
                                    <img src="<?php echo esc_url( $avatar_url ); ?>"
                                         alt="<?php echo esc_attr( $full_name ); ?>">
                                <?php else : ?>
                                    <i class="fas fa-user fa-2x"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h2 class="profile-summary-title mb-1">Profile / Dashboard</h2>
                                <p class="profile-summary-subtitle">
                                    Manage your profile and community content.
                                </p>
                            </div>
                        </div>
                        <button type="button"
                                class="btn profile-edit-btn"
                                id="profileEditToggle">
                            Edit
                        </button>
                    </div>

                    <div class="row g-3 profile-info-grid">
                        <div class="col-md-6">
                            <div class="profile-info-box">
                                <span class="profile-info-label">Full Name</span>
                                <span class="profile-info-value">
                                    <?php echo esc_html( $full_name ); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="profile-info-box">
                                <span class="profile-info-label">Email</span>
                                <span class="profile-info-value">
                                    <?php echo esc_html( $current_user->user_email ); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- HIDDEN EDIT FORM -->
                    <div class="profile-edit-form d-none" id="profileEditForm">
                        <h5 class="mb-3 mt-4">Edit Profile</h5>
                        <p class="text-muted small mb-3">
                            Update your details below. Leave the password fields blank if you don’t want to change it.
                        </p>

                        <form method="post"
                              action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="ileg_update_profile">
                            <?php wp_nonce_field( 'ileg_profile_update', 'ileg_profile_nonce' ); ?>

                            <div class="row g-3">
                                <!-- <div class="col-md-6">
                                    <label class="form-label" for="first_name">First Name</label>
                                    <input type="text"
                                           class="form-control"
                                           id="first_name"
                                           name="first_name"
                                           value="<?php echo esc_attr( $first_name ); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="last_name">Last Name</label>
                                    <input type="text"
                                           class="form-control"
                                           id="last_name"
                                           name="last_name"
                                           value="<?php echo esc_attr( $last_name ); ?>">
                                </div> -->

                                <div class="col-md-6">
                                    <label class="form-label" for="display_name">Display Name</label>
                                    <input type="text"
                                           class="form-control"
                                           id="display_name"
                                           name="display_name"
                                           value="<?php echo esc_attr( $current_user->display_name ); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="user_email">Email Address</label>
                                    <input type="email"
                                           class="form-control"
                                           id="user_email"
                                           name="user_email"
                                           value="<?php echo esc_attr( $current_user->user_email ); ?>"
                                           required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="mb-3">Change Password</h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="pass1">New Password</label>
                                    <input type="password"
                                           class="form-control"
                                           id="pass1"
                                           name="pass1">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="pass2">Confirm New Password</label>
                                    <input type="password"
                                           class="form-control"
                                           id="pass2"
                                           name="pass2">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login mt-3">
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- MY POSTS -->
                <div class="submit-card p-4 p-md-5">
                    <h2 class="mb-3">My Posts</h2>
                    <p class="mb-4">These are the posts you’ve submitted.</p>

                    <?php
                    echo do_blocks( '<!-- wp:ileg/card-gatherer-profile {"typeblock":"posts"} /-->' );
                    ?>
                </div>

                <!-- MY IMAGES -->
               <div class="submit-card p-4 p-md-5 mt-4">
                    <h2 class="mb-3">My Images</h2>
                    <p class="mb-4">All images you have uploaded.</p>

                    <?php
                    echo do_blocks( '<!-- wp:ileg/card-gatherer-profile {"typeblock":"images"} /-->' );
                    ?>
                </div>

                <!-- My Drafts & Pending Posts -->
                <div class="submit-card p-4 p-md-5 mt-4">
                    <h2 class="mb-3">My Drafts & Pending Posts</h2>
                    <p class="mb-4">Work-in-progress and submitted posts that aren’t published yet, including drafts, pending review, and on-hold items.</p>

                    <?php
                    echo do_blocks( '<!-- wp:ileg/card-gatherer-profile {"typeblock":"drafts"} /-->' );
                    ?>
                </div>

                <!-- Analytics -->
                <div class="submit-card p-4 p-md-5 mt-4">
                    <h2 class="mb-3">Analytics</h2>
                    <p class="mb-4">These are the analytics for your posts and images.</p>

                    <?php
                    echo do_blocks( '<!-- wp:ileg/analytics {"typeblock":"analytics"} /-->' );
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('profileEditToggle');
    var formEl    = document.getElementById('profileEditForm');

    if (!toggleBtn || !formEl) return;

    toggleBtn.addEventListener('click', function () {
        var isHidden = formEl.classList.contains('d-none');
        if (isHidden) {
            formEl.classList.remove('d-none');
            toggleBtn.textContent = 'Close';
        } else {
            formEl.classList.add('d-none');
            toggleBtn.textContent = 'Edit';
        }
    });
});
</script>

<?php
// Footer template part + WP footer hook
if ( function_exists( 'block_template_part' ) ) {
    block_template_part( 'footer' );
}
wp_footer();
?>
</body>
</html>
