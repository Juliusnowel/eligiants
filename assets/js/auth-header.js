document.addEventListener('DOMContentLoaded', function () {
  if (typeof IlegAuth === 'undefined') return;

  const iconLink = document.querySelector('.nav-login-icon-link');
  const btn      = document.getElementById('authBtn');

  if (!iconLink || !btn) return;

  if (IlegAuth.isLoggedIn) {
    // Logged in:
    // - button becomes "Log Out" and hits logout URL
    // - icon goes to profile page
    btn.textContent = 'Log Out';
    btn.setAttribute('href', IlegAuth.logoutUrl);

    iconLink.setAttribute('href', IlegAuth.profileUrl);
  } else {
    // Logged out:
    // - both icon and button go to login page
    btn.textContent = 'Log In';
    btn.setAttribute('href', IlegAuth.loginUrl);

    iconLink.setAttribute('href', IlegAuth.loginUrl);
  }
});
