/**
 * BC Form Protection
 * Automatically injects CSRF tokens into forms and handles form data restoration
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bcProtectformConfig === 'undefined') {
        return;
    }

    // Function to add CSRF token to a form
    function addCsrfTokenToForm(form) {
        // Check if token already exists
        if (form.querySelector('input[name="bc_csrf_token"]')) {
            return;
        }

        if (!bcProtectformConfig.signup_enabled && form?.id === 'customer-form') {
            return;
        }

        if (!bcProtectformConfig.login_enabled && form?.id === 'login-form') {
            return;
        }

        // Create token input
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'bc_csrf_token';
        input.value = bcProtectformConfig.token;
        form.appendChild(input);

        // Restore form data if available
        if (typeof bcFormData !== 'undefined') {
            Object.keys(bcFormData).forEach(function (key) {
                var input = form.querySelector('[name="' + key + '"]');
                if (input && input.type !== 'password') {
                    input.value = bcFormData[key];
                }
            });
        }
    }

    // Process forms based on configuration
    var forms = document.forms;
    for (var i = 0; i < forms.length; i++) {
        addCsrfTokenToForm(forms[i]);
    }

    // Handle dynamically added forms
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeName === 'FORM') {
                    addCsrfTokenToForm(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}); 