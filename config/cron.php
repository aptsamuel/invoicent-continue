<?php
/**
 * Invoicent - Automated System Maintenance Routine
 * This file must run via CLI Cron Job only.
 */

// Force script to execute ONLY via server terminal command line (CLI)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("❌ Security Access Violation: Internal System Use Only.");
}

// Ensure base configurations load cleanly
require_once dirname(__DIR__) . '/config/config.php';

logError("Cron Job Execution Initialized: Starting automated data sanitation loops.");

try {
    // Task 1: Automatically purge login tracking metrics older than 30 days
    $purgeLogsQuery = "DELETE FROM login_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $conn->prepare($purgeLogsQuery);
    $stmt->execute();
    $purgedRows = $stmt->affected_rows;
    $stmt->close();
    
    logError("Sanitation Completed: Successfully cleared $purgedRows stale system logs entries.");
    
    // Future expansion point: You can add invoice email reminders or subscription checkers here!
    
} catch (Exception $e) {
    logError("Cron System Exception: " . $e->getMessage());
}
