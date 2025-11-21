<?php
/*
Template Name: Community Submit Post
*/
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>

	<style>
		.community-submit-wrapper {
			background: #ffffff;
		}

		.submit-card {
			border-radius: 24px;
			background: #ffffff;
			box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
		}

		.submit-title {
			font-weight: 700;
			color: #fd593c;
		}

		.submit-subtitle {
			color: #777;
		}

		label.form-label {
			font-weight: 600;
			color: #fd593c;
		}

		input.form-control,
		select.form-select,
		textarea.form-control {
			border-radius: 14px;
			border: 1px solid #ddd;
			padding: 0.65rem 0.85rem;
		}

		.btn-submit {
			background: #fd593c;
			border-radius: 50px;
			color: #fff;
			min-width: 140px;
			border: 2px solid #fd593c;
			font-weight: 600;
		}

		.btn-submit:hover {
			background: #FDCD3B;
			color: #fff;
            border: 2px solid #FDCD3B;
		}

        .btn-draft {
			background: #fff;
			border-radius: 50px;
			color: #fd593c;
			min-width: 140px;
			border: 1px solid #fd593c;
			font-weight: 600;
		}

		.btn-draft:hover {
			background: #fff;
			color: #fd593c;
            border: 2px solid #fd593c;
		}

		h2 {
			color: #fd593c;
		}

		.submit-type-toggle {
			border-radius: 999px;
			background: #fff3b8;
			padding: 2px;
		}

		.submit-type-toggle__btn {
			border: none;
			background: transparent;
			padding: 0.25rem 0.9rem;
			border-radius: 999px;
			font-size: 0.85rem;
			font-weight: 600;
			color: #fd593c;
			cursor: pointer;
		}

		.submit-type-toggle__btn--active {
			background: #ffd84a;
			color: #16324f;
		}

	</style>
</head>

<body <?php body_class(); ?>>

<?php
// Header template part
if (function_exists('do_blocks')) {
	echo do_blocks('<!-- wp:template-part {"slug":"header-community","tagName":"header"} /-->');
}
?>

<main class="community-submit-wrapper py-5">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-10 col-xl-9">

				<!-- CARD -->
				<div class="submit-card p-4 p-md-5">

					<h2 class="submit-title mb-3">Submit Post</h2>
					<p class="submit-subtitle mb-4">Share your travel story with the community.</p>

					<!-- TOGGLE: IMAGE vs BLOG -->
					<div class="mb-4 d-inline-flex submit-type-toggle">
						<button type="button"
								id="toggle_image_post"
								class="submit-type-toggle__btn submit-type-toggle__btn--active">
							Image Post
						</button>
						<button type="button"
								id="toggle_blog_post"
								class="submit-type-toggle__btn">
							Blog Post
						</button>
					</div>

					<?php if (isset($_GET['submit-error'])) : ?>
						<div class="alert alert-danger mb-4">
							There was an error submitting your post. Please check your inputs.
						</div>
					<?php endif; ?>

					<form id="community-submit-form"
						method="post"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						enctype="multipart/form-data">

						<input type="hidden" name="action" value="community_submit_post">
						<input type="hidden" name="community_post_nonce" value="">
						<input type="hidden" name="post_action" id="post_action" value="submit">
						<!-- NEW: blog vs image -->
						<input type="hidden" name="post_variant" id="post_variant" value="image">

						<!-- TITLE -->
						<div class="mb-3">
							<label class="form-label" for="post_title">Title <span class="small text-muted">(required for blog posts)</span></label>
							<input id="post_title"
								class="form-control"
								name="post_title"
								placeholder="Enter your post title">
						</div>

						<!-- CONTENT -->
						<div class="mb-3" id="content_group">
							<label class="form-label">Content</label>
							<?php
							wp_editor(
								'',
								'post_content',
								[
									'textarea_name' => 'post_content',
									'media_buttons' => true,
									'teeny'         => false,
									'quicktags'     => true
								]
							);
							?>
						</div>

						<!-- FEATURED IMAGE (this will be the key field for Image Post) -->
						<div class="mb-3">
							<label class="form-label" for="featured_image">Featured Image</label>
							<input type="file"
								class="form-control"
								name="featured_image"
								id="featured_image"
								accept="image/*">
						</div>

						<!-- CATEGORY: shown only for blog posts -->
						<div class="mb-3" id="category_group">
							<label class="form-label">Category</label>
							<?php
							wp_dropdown_categories([
								'taxonomy'        => 'category',
								'hide_empty'      => false,
								'name'            => 'post_category',
								'class'           => 'form-select',
								'show_option_all' => 'Select a category',
							]);
							?>
						</div>

						<!-- TAGS -->
						<div class="mb-3">
							<label class="form-label" for="post_tags">Tags (comma separated)</label>
							<input type="text"
								class="form-control"
								id="post_tags"
								name="post_tags"
								placeholder="e.g. beaches, adventure, food">
						</div>

						<!-- EXCERPT -->
						<div class="mb-3">
							<label class="form-label" for="post_excerpt">Excerpt</label>
							<textarea class="form-control"
									id="post_excerpt"
									name="post_excerpt"
									rows="3"
									placeholder="Write a short summary..."></textarea>
						</div>

						<div class="d-flex gap-3 mt-4">
							<button type="submit"
									class="btn btn-draft"
									onclick="document.getElementById('post_action').value='draft'">
								Save as Draft
							</button>

							<button type="submit"
									class="btn btn-submit"
									onclick="document.getElementById('post_action').value='submit'">
								Submit Post
							</button>
						</div>

					</form>


				</div>

			</div>
		</div>
	</div>
</main>

<?php
// Footer template part
if (function_exists('block_template_part')) {
	block_template_part('footer');
}

wp_footer();
?>
</body>
</html>
