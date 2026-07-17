<?php
/**
 * Invoicent - Main Root Router
 * Instantly routes incoming domain visitors to the secure login module.
 */

// Route the browser to the login page using a standard 302 redirect
header("Location: auth/login.html");
exit;
