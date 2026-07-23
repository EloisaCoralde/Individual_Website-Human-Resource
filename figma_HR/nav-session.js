/* ==========================================================
   nav-session.js
   Included on every public page (Coralde.html, contact.html,
   careers.html, etc). Checks api/session_check.php to see if
   the visitor already has an active session, and - without
   requiring them to log in again - swaps the navbar's "Login"
   button for "My Profile" (employees) or "Admin Dashboard"
   (admins) so they can jump straight back in.
========================================================== */

(async function () {
    const loginBtn = document.getElementById("navLoginBtn");
    if (!loginBtn) return; // page doesn't have the button, nothing to do

    try {
        const res = await fetch("api/session_check.php", { credentials: "include" });
        const data = await res.json();

        if (!data.success) return; // leave the default "Login" button as-is

        if (data.loggedInAsAccount) {
            loginBtn.textContent = "My Profile";
            loginBtn.href = "profile.html";
        } else if (data.loggedInAsAdmin) {
            loginBtn.textContent = "Admin Dashboard";
            loginBtn.href = "admin.html";
        }
        // if neither, the button stays exactly as it was: "Login" -> login.html

    } catch (err) {
        // if the API/database isn't reachable, just leave the Login button alone
        console.error("Could not check session status:", err);
    }
})();
