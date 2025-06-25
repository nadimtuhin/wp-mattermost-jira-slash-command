<?php
/**
 * Test script to generate valid curl commands for API testing
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>Curl Authentication Test</h1>";

// Generate fresh nonces
$rest_nonce = wp_create_nonce('wp_rest');
$ajax_nonce = wp_create_nonce('wp_mm_slash_jira_nonce');

echo "<h2>1. Generated Nonces</h2>";
echo "<p><strong>REST API Nonce:</strong> <code>$rest_nonce</code></p>";
echo "<p><strong>AJAX Nonce:</strong> <code>$ajax_nonce</code></p>";

// Verify nonces
$rest_valid = wp_verify_nonce($rest_nonce, 'wp_rest');
$ajax_valid = wp_verify_nonce($ajax_nonce, 'wp_mm_slash_jira_nonce');

echo "<p><strong>REST Nonce Valid:</strong> " . ($rest_valid ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>AJAX Nonce Valid:</strong> " . ($ajax_valid ? '✅ Yes' : '❌ No') . "</p>";

// Get site URL and REST URL
$site_url = get_site_url();
$rest_base = rest_url('jira/mattermost/slash/');

echo "<h2>2. API URLs</h2>";
echo "<p><strong>Site URL:</strong> <code>$site_url</code></p>";
echo "<p><strong>REST Base URL:</strong> <code>$rest_base</code></p>";

// Check if logs table exists and get a valid log ID
echo "<h2>3. Available Log IDs</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'mm_jira_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Logs table exists with $count entries</p>";
    
    if ($count > 0) {
        $logs = $wpdb->get_results("SELECT id, channel_name, user_name, command, timestamp FROM $table_name ORDER BY id DESC LIMIT 5");
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Channel</th><th>User</th><th>Command</th><th>Timestamp</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log->id}</td>";
            echo "<td>{$log->channel_name}</td>";
            echo "<td>{$log->user_name}</td>";
            echo "<td>{$log->command}</td>";
            echo "<td>{$log->timestamp}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $first_log_id = $logs[0]->id;
    } else {
        echo "<p style='color: orange;'>⚠️ No logs available for testing</p>";
        $first_log_id = 1; // Use 1 as fallback
    }
} else {
    echo "<p style='color: red;'>❌ Logs table does not exist</p>";
    $first_log_id = 1; // Use 1 as fallback
}

echo "<h2>4. Working Curl Commands</h2>";

// Test the specific log ID from the user's curl command
$test_log_id = 13;
echo "<h3>Test Log ID 13 (from your curl command):</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;'>";
echo "curl '$site_url/wp-json/jira/mattermost/slash/logs/$test_log_id' \\\n";
echo "  -H 'Accept: */*' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-WP-Nonce: $rest_nonce' \\\n";
echo "  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36' \\\n";
echo "  --insecure";
echo "</div>";

echo "<h3>Test with Available Log ID ($first_log_id):</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;'>";
echo "curl '$site_url/wp-json/jira/mattermost/slash/logs/$first_log_id' \\\n";
echo "  -H 'Accept: */*' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-WP-Nonce: $rest_nonce' \\\n";
echo "  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36' \\\n";
echo "  --insecure";
echo "</div>";

echo "<h3>Test Mappings Endpoint:</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;'>";
echo "curl '$site_url/wp-json/jira/mattermost/slash/mappings' \\\n";
echo "  -H 'Accept: */*' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-WP-Nonce: $rest_nonce' \\\n";
echo "  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36' \\\n";
echo "  --insecure";
echo "</div>";

echo "<h2>5. Test the API Directly</h2>";

// Test the API directly
$server = rest_get_server();
$request = new WP_REST_Request('GET', "/jira/mattermost/slash/logs/$test_log_id");
$request->set_header('X-WP-Nonce', $rest_nonce);

$response = $server->dispatch($request);

echo "<h3>Direct API Test for Log ID $test_log_id:</h3>";
if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Error: " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Success! Response status: " . $response->get_status() . "</p>";
    $data = $response->get_data();
    if ($data) {
        echo "<p><strong>Log ID:</strong> " . $data->id . "</p>";
        echo "<p><strong>Channel:</strong> " . $data->channel_name . "</p>";
        echo "<p><strong>User:</strong> " . $data->user_name . "</p>";
        echo "<p><strong>Command:</strong> " . $data->command . "</p>";
    }
}

echo "<h2>6. Troubleshooting</h2>";
echo "<ul>";
echo "<li><strong>Nonce Expiration:</strong> Nonces expire after 24 hours. Generate a fresh one if needed.</li>";
echo "<li><strong>Log ID Exists:</strong> Make sure the log ID exists in the database.</li>";
echo "<li><strong>User Permissions:</strong> Ensure you're logged in as an administrator.</li>";
echo "<li><strong>WordPress Debug:</strong> Check WordPress debug logs for additional errors.</li>";
echo "</ul>";

echo "<h2>7. Quick Test Commands</h2>";
echo "<p>Copy and paste these commands in your terminal:</p>";

echo "<h3>Test with fresh nonce:</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;'>";
echo "# Test log ID 13\n";
echo "curl '$site_url/wp-json/jira/mattermost/slash/logs/13' \\\n";
echo "  -H 'X-WP-Nonce: $rest_nonce' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  --insecure\n\n";
echo "# Test mappings endpoint\n";
echo "curl '$site_url/wp-json/jira/mattermost/slash/mappings' \\\n";
echo "  -H 'X-WP-Nonce: $rest_nonce' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  --insecure";
echo "</div>";

echo "<h2>8. Expected Responses</h2>";
echo "<p><strong>Success (200):</strong> JSON response with log data</p>";
echo "<p><strong>Not Found (404):</strong> Log entry not found</p>";
echo "<p><strong>Forbidden (403):</strong> Authentication/permission error</p>";
echo "<p><strong>Bad Request (400):</strong> Invalid log ID format</p>"; 