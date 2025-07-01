document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    const showPasswordCheckbox = document.getElementById('show-password');
    const passwordInput = document.getElementById('password');

    // Handle form submission
    document.getElementById('show-password').addEventListener('change', function() {
        var passwordField = document.getElementById('password');
        if (this.checked) {
            passwordField.type = 'text';
        } else {
            passwordField.type = 'password';
        }
    });
    // Toggle password visibility
    showPasswordCheckbox.addEventListener('change', function() {
        passwordInput.type = showPasswordCheckbox.checked ? 'text' : 'password';
    });
}); 
