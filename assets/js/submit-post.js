document.addEventListener('DOMContentLoaded', function () {
  if (typeof IlegSubmitPost === 'undefined') return;

  var form = document.getElementById('community-submit-form');
  if (!form) return;

  var nonceField = form.querySelector('input[name="community_post_nonce"]');
  if (!nonceField) return;

  nonceField.value = IlegSubmitPost.nonce;
});

document.addEventListener('DOMContentLoaded', function () {
    const variantField   = document.getElementById('post_variant');
    const titleField     = document.getElementById('post_title');
    const categoryGroup  = document.getElementById('category_group');
    const contentGroup   = document.getElementById('content_group');
    const imageBtn       = document.getElementById('toggle_image_post');
    const blogBtn        = document.getElementById('toggle_blog_post');
    const mediaButtons   = document.getElementById('wp-post_content-media-buttons');
    const tagsField      = document.getElementById('post_tags');
    const excerptField   = document.getElementById('post_excerpt');

    function setVariant(type) {
        variantField.value = type;

        if (mediaButtons) {
            mediaButtons.style.display = (type === 'image') ? 'none' : '';
        }

        if (type === 'image') {
            imageBtn.classList.add('submit-type-toggle__btn--active');
            blogBtn.classList.remove('submit-type-toggle__btn--active');

            // Title optional for image posts
            titleField.removeAttribute('required');

            // Hide blog-only bits
            if (categoryGroup) categoryGroup.style.display = 'none';
            if (contentGroup)  contentGroup.style.display  = 'none';

        } else {
            blogBtn.classList.add('submit-type-toggle__btn--active');
            imageBtn.classList.remove('submit-type-toggle__btn--active');

            // Title required for blog posts
            titleField.setAttribute('required', 'required');

            if (categoryGroup) categoryGroup.style.display = '';
            if (contentGroup)  contentGroup.style.display  = '';
        }
    }


    imageBtn.addEventListener('click', function () { setVariant('image'); });
    blogBtn.addEventListener('click', function () { setVariant('blog'); });

    // Default: Image post
    setVariant('image');
});

