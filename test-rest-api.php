<?php
/**
 * Test script to verify REST API endpoints
 * Run this in your browser to test the API endpoints
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin permissions
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator to run this test.');
}

echo "<h1>REST API Test Results</h1>";

// Test 1: Check if user has proper permissions
echo "<h2>1. User Permissions</h2>";
echo "<p><strong>User ID:</strong> " . get_current_user_id() . "</p>";
echo "<p><strong>Username:</strong> " . wp_get_current_user()->user_login . "</p>";
echo "<p><strong>Can manage options:</strong> " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</p>";

// Test 2: Check REST API URL
echo "<h2>2. REST API Information</h2>";
$rest_url = rest_url('jira/mattermost/slash/');
echo "<p><strong>REST Base URL:</strong> " . $rest_url . "</p>";
echo "<p><strong>Mappings URL:</strong> " . $rest_url . "mappings</p>";
echo "<p><strong>Logs URL:</strong> " . $rest_url . "logs/1</p>";

// Test 3: Test mappings endpoint
echo "<h2>3. Mappings Endpoint Test</h2>";
echo "<p>Testing GET /jira/mattermost/slash/mappings...</p>";

// Create a REST request
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/mappings');

// Get the API instance
$api = new WP_MM_Slash_Jira_API();
$response = $api->get_mappings($request);

if (is_wp_error($response)) {
    echo "<p><strong>Error:</strong> " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Success:</strong> Mappings endpoint working correctly</p>";
    echo "<p><strong>Response Status:</strong> " . $response->get_status() . "</p>";
    $data = $response->get_data();
    echo "<p><strong>Number of mappings:</strong> " . count($data) . "</p>";
}

// Test 4: Test log details endpoint
echo "<h2>4. Log Details Endpoint Test</h2>";
echo "<p>Testing GET /jira/mattermost/slash/logs/1...</p>";

// Create a REST request for log details
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/1');
$request->set_param('id', 1);

$response = $api->get_log_details($request);

if (is_wp_error($response)) {
    echo "<p><strong>Error:</strong> " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Success:</strong> Log details endpoint working correctly</p>";
    echo "<p><strong>Response Status:</strong> " . $response->get_status() . "</p>";
}

// Test 5: Test create mapping endpoint
echo "<h2>5. Create Mapping Endpoint Test</h2>";
echo "<p>Testing POST /jira/mattermost/slash/mappings...</p>";

// Create a REST request for creating a mapping
$request = new WP_REST_Request('POST', '/jira/mattermost/slash/mappings');
$request->set_param('channel_id', 'test-channel-' . time());
$request->set_param('channel_name', 'Test Channel');
$request->set_param('jira_project_key', 'TEST');

$response = $api->create_mapping($request);

if (is_wp_error($response)) {
    echo "<p><strong>Error:</strong> " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Success:</strong> Create mapping endpoint working correctly</p>";
    echo "<p><strong>Response Status:</strong> " . $response->get_status() . "</p>";
    $data = $response->get_data();
    echo "<p><strong>Created mapping ID:</strong> " . $data['id'] . "</p>";
}

// Test 6: Test actual REST API call
echo "<h2>6. Actual REST API Call Test</h2>";
echo "<p>Testing actual REST API call to mappings endpoint...</p>";

// Get the REST server
$server = rest_get_server();

// Create a request
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/mappings');

// Dispatch the request
$response = $server->dispatch($request);

if (is_wp_error($response)) {
    echo "<p><strong>Error:</strong> " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Success:</strong> REST API call working correctly</p>";
    echo "<p><strong>Response Status:</strong> " . $response->get_status() . "</p>";
    $data = $response->get_data();
    echo "<p><strong>Number of mappings:</strong> " . count($data) . "</p>";
}

// Test 7: Test log details with actual REST API call
echo "<h2>7. Log Details REST API Call Test</h2>";
echo "<p>Testing actual REST API call to log details endpoint...</p>";

// Create a request for log details
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/1');
$request->set_param('id', 1);

// Dispatch the request
$response = $server->dispatch($request);

if (is_wp_error($response)) {
    echo "<p><strong>Error:</strong> " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Success:</strong> Log details REST API call working correctly</p>";
    echo "<p><strong>Response Status:</strong> " . $response->get_status() . "</p>";
}

echo "<h2>8. Manual Test Instructions</h2>";
echo "<ol>";
echo "<li>Go to the plugin admin page</li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to the Network tab</li>";
echo "<li>Try clicking on a log entry in the Invocation Logs tab</li>";
echo "<li>Check if the REST API call succeeds</li>";
echo "<li>Look for any error messages in the Console tab</li>";
echo "</ol>";

echo "<h2>9. JavaScript Test</h2>";
echo "<p>Open browser console and run this JavaScript:</p>";
echo "<pre>";
echo "console.log('REST URL:', '" . $rest_url . "');\n";
echo "console.log('Testing mappings endpoint...');\n";
echo "fetch('" . $rest_url . "mappings')\n";
echo "  .then(response => response.json())\n";
echo "  .then(data => console.log('Mappings:', data))\n";
echo "  .catch(error => console.error('Error:', error));\n";
echo "</pre>";
?> 