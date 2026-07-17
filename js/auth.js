/**
 * Invoicent SaaS Application - Global Authentication & Security Helper
 * Path: /invoicent/js/auth.js
 */

// Centralized configuration options for your project paths
const AUTH_CONFIG = {
    baseUrl: '',
    endpoints: {
        csrf: '../api/csrf.php',
        register: '../api/register.php',
        profile: '../api/profile.php',
        profileGet: '../api/get_profile.php',
        resetRequest: '../api/password_reset_request.php',
        resetVerify: '../api/verify_reset_token.php',
        resetComplete: '../api/password_reset_complete.php'
    }
};

/**
 * Reusable helper to securely fetch a fresh CSRF token from the API
 * @returns {Promise<string|null>} The CSRF token or null if failed
 */
async function getCSRFToken() {
    try {
        const response = await fetch(AUTH_CONFIG.endpoints.csrf, {
            method: 'GET',
            headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        return data.csrf_token || null;
    } catch (error) {
        console.error('CSRF Security Retrieval Failure:', error);
        return null;
    }
}

/**
 * Universal wrapper to make secure POST requests with fresh CSRF tokens automatically injected.
 * @param {string} endpointName - The key from AUTH_CONFIG.endpoints
 * @param {Object} dataPayload - The body contents to be delivered
 * @returns {Promise<Object>} The parsed JSON server response
 */
async function securePostRequest(endpointUrl, dataPayload = {}) {
    const freshToken = await getCSRFToken();
    if (!freshToken) {
        return { success: false, message: 'Security validation initialization failed. Please reload the webpage.' };
    }

    // Embed the security signature alongside your input model
    const finalizedPayload = {
        ...dataPayload,
        csrf_token: freshToken
    };

    try {
        const response = await fetch(endpointUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(finalizedPayload)
        });

        const result = await response.json();
        // Pack the raw HTTP status along with data metrics for flexible front-end error tracking
        return { ...result, httpOk: response.ok, httpStatus: response.status };
    } catch (error) {
        console.error(`Secure Request Failure at [${endpointUrl}]:`, error);
        return { success: false, message: 'Unable to communicate with core application servers.' };
    }
}

/**
 * Global input sanitization helper to handle presentation layer text properties safely
 * @param {string} rawString 
 * @returns {string} Cleaned safe data string
 */
function sanitizeInputString(rawString) {
    if (typeof rawString !== 'string') return '';
    return rawString
        .trim()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

/**
 * Universal interface display utilities for consistent alert management
 */
const AuthUI = {
    showError: (containerId, message) => {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `<div class="error-item"><i class="fas fa-exclamation-circle"></i> ${sanitizeInputString(message)}</div>`;
        }
    },
    showErrorsArray: (containerId, errorsArray) => {
        const container = document.getElementById(containerId);
        if (container && Array.isArray(errorsArray)) {
            container.innerHTML = errorsArray.map(err => 
                `<div class="error-item"><i class="fas fa-exclamation-circle"></i> ${sanitizeInputString(err)}</div>`
            ).join('');
        }
    },
    showSuccessGlobal: (message) => {
        const existingAlert = document.querySelector('.alert-success-global');
        if (existingAlert) existingAlert.remove();

        const alert = document.createElement('div');
        alert.className = 'alert-success alert-success-global';
        alert.style.cssText = "position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 15px 25px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);";
        alert.innerHTML = `<i class="fas fa-check-circle"></i> ${sanitizeInputString(message)}`;
        document.body.appendChild(alert);
        
        setTimeout(() => alert.remove(), 4000);
    }
};
