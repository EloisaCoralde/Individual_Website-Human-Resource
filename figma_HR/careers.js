/* ==========================================================
   careers.js
   Job-applications logic for careers.html.
   Everything is stored in MySQL via the PHP API in /api -
   nothing is kept in localStorage anymore.
========================================================== */

const applyModalEl   = document.getElementById("applyModal");
const applyModal     = new bootstrap.Modal(applyModalEl);
const applyForm      = document.getElementById("applyForm");
const applySuccess   = document.getElementById("applySuccessMessage");

/* ----------------------------------------------------------
   1. Show live "X applicants so far" counts under each job
      (reads from api/applicant_counts.php)
---------------------------------------------------------- */
async function loadApplicantCounts() {
    try {
        const res = await fetch("api/applicant_counts.php");
        const data = await res.json();
        if (!data.success) return;

        document.querySelectorAll("[data-applicant-count]").forEach(el => {
            const position = el.getAttribute("data-applicant-count");
            const count = data.counts[position] || 0;
            el.textContent = `${count} applicant${count === 1 ? "" : "s"} so far`;
        });
    } catch (err) {
        // if the API/database isn't reachable yet, just leave the counts blank
        console.error("Could not load applicant counts:", err);
    }
}

/* ----------------------------------------------------------
   1b. Restrict the phone field to digits only while typing,
       capped at 11 characters (matches the 09XXXXXXXXX format)
---------------------------------------------------------- */
document.getElementById("applyPhone").addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, "").slice(0, 11);
});

/* ----------------------------------------------------------
   2. Open the modal pre-filled with the chosen position
---------------------------------------------------------- */
document.querySelectorAll(".apply-now-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        const position = btn.getAttribute("data-position");

        document.getElementById("applyModalJobTitle").textContent = position;
        document.getElementById("applyPosition").value = position;

        // reset the form each time the modal is opened
        applyForm.reset();
        applyForm.style.display = "block";
        applySuccess.style.display = "none";
        document.getElementById("applyPosition").value = position;

        applyModal.show();
    });
});

/* ----------------------------------------------------------
   3. Submit the application to api/apply.php (MySQL insert)
---------------------------------------------------------- */
applyForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    const payload = {
        position: document.getElementById("applyPosition").value,
        name:     document.getElementById("applyName").value.trim(),
        email:    document.getElementById("applyEmail").value.trim(),
        phone:    document.getElementById("applyPhone").value.trim()
    };

    // Full name must be in "Surname, Firstname, M.I." format
    const namePattern = /^[A-Za-z\u00C0-\u017F.'\-]+(\s+[A-Za-z\u00C0-\u017F.'\-]+)*\s*,\s*[A-Za-z\u00C0-\u017F.'\-]+(\s+[A-Za-z\u00C0-\u017F.'\-]+)*\s*,\s*[A-Za-z]{1,2}\.?$/;
    if (!namePattern.test(payload.name)) {
        alert("Please enter your full name as: Surname, Firstname, M.I. (e.g. Dela Cruz, Juan, A.)");
        return;
    }

    // Basic email format check
    const emailPattern = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    if (!emailPattern.test(payload.email)) {
        alert("Please enter a valid email address, e.g. name@email.com");
        return;
    }

    // Phone must start with 09 and be exactly 11 digits
    if (!/^09\d{9}$/.test(payload.phone)) {
        alert("Please enter a valid phone number starting with 09, 11 digits total (e.g. 09123456789).");
        return;
    }

    const submitBtn = applyForm.querySelector("button[type='submit']");
    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting...";

    try {
        const res = await fetch("api/apply.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (result.success) {
            applyForm.style.display = "none";
            applySuccess.style.display = "block";
            applySuccess.innerHTML =
                `<div class="alert alert-success mb-0">
                    Thanks, ${payload.name}! Your application for
                    <strong>${payload.position}</strong> has been received.
                 </div>`;
            loadApplicantCounts(); // refresh the "X applicants so far" counts
        } else {
            alert(result.message || "Something went wrong. Please try again.");
        }
    } catch (err) {
        alert("Could not reach the server. Please try again.");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit Application";
    }
});

// clear the success message each time the modal is fully hidden
applyModalEl.addEventListener("hidden.bs.modal", () => {
    applyForm.reset();
    applyForm.style.display = "block";
    applySuccess.style.display = "none";
});

loadApplicantCounts();