<?php
/**
 * Test script to verify that the permission issue is fixed
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>Permission Fix Test Results</h1>";

// Test 1: Check current user
echo "<h2>1. Current User Information</h2>";
$current_user = wp_get_current_user();
echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
echo "<p><strong>Username:</strong> " . $current_user->user_login . "</p>";
echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";
echo "<p><strong>Can manage options:</strong> " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</p>";

// Test 2: Test the check_admin_permissions method directly
echo "<h2>2. Direct Permission Check</h2>";
$api = new WP_MM_Slash_Jira_API();
$permission_result = $api->check_admin_permissions();
echo "<p><strong>check_admin_permissions() result:</strong> " . ($permission_result ? 'TRUE' : 'FALSE') . "</p>";

// Test 3: Check if logs table exists and has data
echo "<h2>3. Logs Table Check</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'mm_jira_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    echo "<p style='color: green;'>✅ Logs table exists</p>";
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Number of logs: $count</p>";
    
    if ($count > 0) {
        $first_log = $wpdb->get_row("SELECT id FROM $table_name ORDER BY id ASC LIMIT 1");
        echo "<p>First log ID: {$first_log->id}</p>";
        
        // Test 4: Test the get_log_details method directly
        echo "<h2>4. Direct API Method Test</h2>";
        $request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/' . $first_log->id);
        $request->set_param('id', $first_log->id);
        
        $response = $api->get_log_details($request);
        
        if (is_wp_error($response)) {
            echo "<p style='color: red;'>❌ API Error: " . $response->get_error_message() . "</p>";
            echo "<p>Error Code: " . $response->get_error_code() . "</p>";
        } else {
            echo "<p style='color: green;'>✅ API call successful</p>";
            echo "<p>Response status: " . $response->get_status() . "</p>";
            $data = $response->get_data();
            echo "<p>Log ID: " . $data->id . "</p>";
            echo "<p>Channel: " . $data->channel_name . "</p>";
            echo "<p>User: " . $data->user_name . "</p>";
        }
        
        // Test 5: Test REST API endpoint
        echo "<h2>5. REST API Endpoint Test</h2>";
        $server = rest_get_server();
        $request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/' . $first_log->id);
        $request->set_param('id', $first_log->id);
        
        $response = $server->dispatch($request);
        
        if (is_wp_error($response)) {
            echo "<p style='color: red;'>❌ REST API Error: " . $response->get_error_message() . "</p>";
            echo "<p>Error Code: " . $response->get_error_code() . "</p>";
        } else {
            echo "<p style='color: green;'>✅ REST API call successful</p>";
            echo "<p>Response status: " . $response->get_status() . "</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No logs available for testing</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Logs table does not exist</p>";
    echo "<p>Please deactivate and reactivate the plugin to create the table.</p>";
}

// Test 6: Test other admin endpoints
echo "<h2>6. Other Admin Endpoints Test</h2>";

// Test mappings endpoint
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/mappings');
$response = $api->get_mappings($request);

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Mappings endpoint error: " . $response->get_error_message() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Mappings endpoint working</p>";
}

echo "<h2>Summary</h2>";
if ($permission_result) {
    echo "<p style='color: green;'>✅ Permission check is working correctly</p>";
} else {
    echo "<p style='color: red;'>❌ Permission check is failing</p>";
}

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If all tests pass, the permission issue should be fixed</li>";
echo "<li>Try accessing the admin interface and viewing log details</li>";
echo "<li>If issues persist, check WordPress debug logs for additional errors</li>";
echo "</ul>"; 