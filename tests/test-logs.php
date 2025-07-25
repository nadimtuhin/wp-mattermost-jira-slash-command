<?php
/**
 * Test script to debug log details endpoint
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>Log Details Endpoint Test</h1>";

// Test 1: Check if logs table exists
global $wpdb;
$table_name = $wpdb->prefix . 'mm_jira_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo "<h2>Test 1: Database Table</h2>";
if ($table_exists) {
    echo "<p style='color: green;'>✅ Logs table exists</p>";
    
    // Check if table has data
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Number of logs: $count</p>";
    
    if ($count > 0) {
        // Get first log ID for testing
        $first_log = $wpdb->get_row("SELECT id FROM $table_name ORDER BY id ASC LIMIT 1");
        echo "<p>First log ID: {$first_log->id}</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Logs table does not exist</p>";
}

// Test 2: Check logger class
echo "<h2>Test 2: Logger Class</h2>";
try {
    $logger = new WP_MM_Slash_Jira_Logger();
    echo "<p style='color: green;'>✅ Logger class instantiated successfully</p>";
    
    if ($table_exists && $count > 0) {
        $test_log = $logger->get_log($first_log->id);
        if ($test_log) {
            echo "<p style='color: green;'>✅ get_log() method works</p>";
        } else {
            echo "<p style='color: red;'>❌ get_log() method failed</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Logger class error: " . $e->getMessage() . "</p>";
}

// Test 3: Check REST API endpoint
echo "<h2>Test 3: REST API Endpoint</h2>";
$rest_url = rest_url('jira/mattermost/slash/logs/1');
echo "<p>REST URL: <code>$rest_url</code></p>";

// Test 4: Check user permissions
echo "<h2>Test 4: User Permissions</h2>";
echo "<p>Current user: " . wp_get_current_user()->user_login . "</p>";
echo "<p>Can manage options: " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</p>";

// Test 5: Check nonce
echo "<h2>Test 5: Nonce</h2>";
$nonce = wp_create_nonce('wp_mm_slash_jira_nonce');
echo "<p>Nonce created: " . (empty($nonce) ? 'No' : 'Yes') . "</p>";
echo "<p>Nonce value: <code>$nonce</code></p>";

// Test 6: Manual API call simulation
echo "<h2>Test 6: Manual API Call</h2>";
if ($table_exists && $count > 0) {
    echo "<p>Testing manual API call for log ID: {$first_log->id}</p>";
    
    // Simulate the API call
    $api = new WP_MM_Slash_Jira_API();
    $api->init();
    
    // Create a mock request
    $request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/' . $first_log->id);
    $request->set_header('X-WP-Nonce', $nonce);
    $request->set_param('id', $first_log->id);
    
    try {
        $response = $api->get_log_details($request);
        
        if (is_wp_error($response)) {
            echo "<p style='color: red;'>❌ API Error: " . $response->get_error_message() . "</p>";
            echo "<p>Error Code: " . $response->get_error_code() . "</p>";
        } else {
            echo "<p style='color: green;'>✅ API call successful</p>";
            echo "<p>Response status: " . $response->get_status() . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No logs available for testing</p>";
}

// Test 7: Curl Payload Logging
echo "<h2>Test 7: Curl Payload Logging</h2>";
try {
    $logger = new WP_MM_Slash_Jira_Logger();
    
    // Test the new log_jira_curl method
    $test_log_id = $logger->log_jira_curl(
        'POST',
        'https://test-domain.atlassian.net/rest/api/2/issue',
        array(
            'Authorization' => 'Basic ' . base64_encode('test-api-key:'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ),
        json_encode(array(
            'fields' => array(
                'project' => array('key' => 'TEST'),
                'summary' => 'Test Issue',
                'issuetype' => array('name' => 'Task')
            )
        )),
        201,
        array('Content-Type' => 'application/json'),
        json_encode(array('key' => 'TEST-123', 'id' => '12345')),
        0.5,
        'success',
        null
    );
    
    if ($test_log_id) {
        echo "<p style='color: green;'>✅ Curl payload logging test successful</p>";
        echo "<p>Test log ID: $test_log_id</p>";
        
        // Retrieve and display the test log
        $test_log = $logger->get_log($test_log_id);
        if ($test_log) {
            echo "<p style='color: green;'>✅ Test log retrieved successfully</p>";
            echo "<p>Command: {$test_log->command}</p>";
            echo "<p>Status: {$test_log->status}</p>";
            echo "<p>Response Code: {$test_log->response_code}</p>";
            
            // Display the curl payload
            $curl_payload = json_decode($test_log->request_payload, true);
            if ($curl_payload) {
                echo "<h3>Curl Payload Details:</h3>";
                echo "<p><strong>Method:</strong> {$curl_payload['method']}</p>";
                echo "<p><strong>URL:</strong> {$curl_payload['url']}</p>";
                echo "<p><strong>Request Headers:</strong></p>";
                echo "<pre>" . print_r($curl_payload['request']['headers'], true) . "</pre>";
                echo "<p><strong>Request Body:</strong></p>";
                echo "<pre>" . htmlspecialchars($curl_payload['request']['body']) . "</pre>";
                echo "<p><strong>Response Code:</strong> {$curl_payload['response']['code']}</p>";
                echo "<p><strong>Response Body:</strong></p>";
                echo "<pre>" . htmlspecialchars($curl_payload['response']['body']) . "</pre>";
                echo "<p><strong>Execution Time:</strong> {$curl_payload['execution_time']} seconds</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to retrieve test log</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Curl payload logging test failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Curl payload logging error: " . $e->getMessage() . "</p>";
}

echo "<h2>Recommendations</h2>";
if (!$table_exists) {
    echo "<p style='color: red;'>⚠️ Create the logs table by deactivating and reactivating the plugin</p>";
}

if ($count == 0) {
    echo "<p style='color: orange;'>⚠️ No logs found. Enable logging and make some test requests to generate logs</p>";
}

echo "<p><a href='" . admin_url('options-general.php?page=wp-mm-slash-jira') . "'>← Back to Plugin Settings</a></p>";
?> 