// Function to toggle password visibility
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleButton = passwordField.nextElementSibling;

    if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleButton.textContent = "🙈"; // Change icon to "hide" state
    } else {
        passwordField.type = "password";
        toggleButton.textContent = "👁️"; // Change icon to "show" state
    }
}

// Function to validate the form and handle submission
function validateForm(event) {
    // Prevent form submission
    event.preventDefault();

    // Get form fields
    const firstName = document.getElementById("first-name").value.trim();
    const lastName = document.getElementById("last-name").value.trim();
    const age = document.getElementById("age").value.trim();
    const sex = document.getElementById("sex").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirm-password").value.trim();
    const termsCheckbox = document.getElementById("terms");

    // Check if all fields are filled out
    if (!firstName || !lastName || !age || !sex || !email || !password || !confirmPassword) {
        alert("Please fill out all fields.");
        return;
    }

    // Check terms and conditions
    if (!termsCheckbox.checked) {
        alert("Please accept the Terms of use & Privacy Policy.");
        return;
    }

    // Check if passwords match
    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return;
    }

    // If validation passes, submit the form 
    const form = event.target;
    const formData = new FormData(form);

    fetch(form.action, {  // Use form's actual action attribute
        method: form.method,
        body: formData,
    })
    .then((response) => response.text())
    .then((data) => {
        // Check for error messages from the server
        if (data.includes("Error:")) {
            alert(data);
        } else if (data.includes("Account created successfully")) {
            alert("Signup successful! Redirecting to login page...");
            setTimeout(() => {
                window.location.href = "login.php";
            }, 2000);
        } else {
            // Generic error handling
            alert("An unexpected response was received.");
        }
    })
    .catch((error) => {
        console.error("Submission error:", error);
        alert("An error occurred during signup. Please try again.");
    });
}

// Attach event listeners
document.addEventListener("DOMContentLoaded", function () {
    // Attach toggle functionality to password fields
    document.querySelectorAll(".toggle-password").forEach((button) => {
        button.addEventListener("click", function () {
            const fieldId = this.getAttribute("data-field");
            togglePassword(fieldId);
        });
    });

    // Attach form validation to the form submission
    const form = document.querySelector("form");
    if (form) {
        form.addEventListener("submit", validateForm);
    }
});