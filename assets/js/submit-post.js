document.addEventListener('DOMContentLoaded', function () {
  if (typeof IlegSubmitPost === 'undefined') return;

  var form = document.getElementById('community-submit-form');
  if (!form) return;

  var nonceField = form.querySelector('input[name="community_post_nonce"]');
  if (!nonceField) return;

  nonceField.value = IlegSubmitPost.nonce;
});
