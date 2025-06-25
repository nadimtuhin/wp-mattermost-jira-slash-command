<?php
/**
 * Test script to verify authentication for API calls from admin interface
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>Authentication Test Results</h1>";

// Test 1: Check current user and permissions
echo "<h2>1. User Authentication</h2>";
$current_user = wp_get_current_user();
echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
echo "<p><strong>Username:</strong> " . $current_user->user_login . "</p>";
echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";
echo "<p><strong>Can manage options:</strong> " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</p>";

// Test 2: Check nonce generation
echo "<h2>2. Nonce Generation</h2>";
$rest_nonce = wp_create_nonce('wp_rest');
$ajax_nonce = wp_create_nonce('wp_mm_slash_jira_nonce');

echo "<p><strong>REST API Nonce:</strong> " . (empty($rest_nonce) ? 'Failed' : 'Generated') . "</p>";
echo "<p><strong>AJAX Nonce:</strong> " . (empty($ajax_nonce) ? 'Failed' : 'Generated') . "</p>";

// Test 3: Test nonce verification
echo "<h2>3. Nonce Verification</h2>";
$rest_valid = wp_verify_nonce($rest_nonce, 'wp_rest');
$ajax_valid = wp_verify_nonce($ajax_nonce, 'wp_mm_slash_jira_nonce');

echo "<p><strong>REST Nonce Valid:</strong> " . ($rest_valid ? 'Yes' : 'No') . "</p>";
echo "<p><strong>AJAX Nonce Valid:</strong> " . ($ajax_valid ? 'Yes' : 'No') . "</p>";

// Test 4: Test API permission check
echo "<h2>4. API Permission Check</h2>";
$api = new WP_MM_Slash_Jira_API();
$permission_result = $api->check_admin_permissions();
echo "<p><strong>API Permission Check:</strong> " . ($permission_result ? 'PASSED' : 'FAILED') . "</p>";

// Test 5: Test REST API endpoints with authentication
echo "<h2>5. REST API Endpoints with Authentication</h2>";

// Test mappings endpoint
$server = rest_get_server();
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/mappings');
$request->set_header('X-WP-Nonce', $rest_nonce);

$response = $server->dispatch($request);

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Mappings endpoint error: " . $response->get_error_message() . "</p>";
    echo "<p>Error Code: " . $response->get_error_code() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Mappings endpoint working with authentication</p>";
    echo "<p>Response status: " . $response->get_status() . "</p>";
}

// Test 6: Check if logs table exists and test log details endpoint
echo "<h2>6. Log Details Endpoint with Authentication</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'mm_jira_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Logs table exists with $count entries</p>";
    
    if ($count > 0) {
        $first_log = $wpdb->get_row("SELECT id FROM $table_name ORDER BY id ASC LIMIT 1");
        
        $request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/' . $first_log->id);
        $request->set_header('X-WP-Nonce', $rest_nonce);
        
        $response = $server->dispatch($request);
        
        if (is_wp_error($response)) {
            echo "<p style='color: red;'>❌ Log details endpoint error: " . $response->get_error_message() . "</p>";
            echo "<p>Error Code: " . $response->get_error_code() . "</p>";
        } else {
            echo "<p style='color: green;'>✅ Log details endpoint working with authentication</p>";
            echo "<p>Response status: " . $response->get_status() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No logs available for testing</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Logs table does not exist</p>";
}

// Test 7: Test AJAX endpoint
echo "<h2>7. AJAX Endpoint Test</h2>";

// Simulate AJAX request
$_POST['action'] = 'create_mm_jira_tables';
$_POST['nonce'] = $ajax_nonce;

// Capture output
ob_start();
try {
    do_action('wp_ajax_create_mm_jira_tables');
    $ajax_output = ob_get_clean();
    
    if (empty($ajax_output)) {
        echo "<p style='color: green;'>✅ AJAX endpoint working with authentication</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ AJAX endpoint returned output: " . htmlspecialchars($ajax_output) . "</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ AJAX endpoint error: " . $e->getMessage() . "</p>";
}

// Test 8: JavaScript variables test
echo "<h2>8. JavaScript Variables Test</h2>";
echo "<p>These variables should be available in the admin interface:</p>";
echo "<ul>";
echo "<li><strong>wp_mm_slash_jira.rest_url:</strong> " . rest_url('jira/mattermost/slash/') . "</li>";
echo "<li><strong>wp_mm_slash_jira.ajax_url:</strong> " . admin_url('admin-ajax.php') . "</li>";
echo "<li><strong>wp_mm_slash_jira.nonce:</strong> " . (empty($rest_nonce) ? 'Missing' : 'Available') . "</li>";
echo "<li><strong>wp_mm_slash_jira.ajax_nonce:</strong> " . (empty($ajax_nonce) ? 'Missing' : 'Available') . "</li>";
echo "</ul>";

// Test 9: Simulate JavaScript AJAX calls
echo "<h2>9. Simulated JavaScript AJAX Calls</h2>";

// Simulate mappings GET request
$mappings_request = new WP_REST_Request('GET', '/jira/mattermost/slash/mappings');
$mappings_request->set_header('X-WP-Nonce', $rest_nonce);
$mappings_response = $server->dispatch($mappings_request);

if (is_wp_error($mappings_response)) {
    echo "<p style='color: red;'>❌ Simulated mappings GET failed: " . $mappings_response->get_error_message() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Simulated mappings GET successful</p>";
}

// Simulate log details GET request
if ($table_exists && $count > 0) {
    $log_request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/' . $first_log->id);
    $log_request->set_header('X-WP-Nonce', $rest_nonce);
    $log_response = $server->dispatch($log_request);
    
    if (is_wp_error($log_response)) {
        echo "<p style='color: red;'>❌ Simulated log details GET failed: " . $log_response->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Simulated log details GET successful</p>";
    }
}

echo "<h2>Summary</h2>";
$all_tests_passed = $permission_result && $rest_valid && $ajax_valid;

if ($all_tests_passed) {
    echo "<p style='color: green;'>✅ All authentication tests passed! The admin interface should work correctly.</p>";
} else {
    echo "<p style='color: red;'>❌ Some authentication tests failed. Check the details above.</p>";
}

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If all tests pass, try accessing the admin interface</li>";
echo "<li>Test loading mappings, viewing log details, and other admin functions</li>";
echo "<li>If issues persist, check browser console for JavaScript errors</li>";
echo "<li>Check WordPress debug logs for additional errors</li>";
echo "</ul>";

echo "<h2>Debug Information</h2>";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Current Time:</strong> " . current_time('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Site URL:</strong> " . get_site_url() . "</p>";
echo "<p><strong>Admin URL:</strong> " . admin_url() . "</p>"; 