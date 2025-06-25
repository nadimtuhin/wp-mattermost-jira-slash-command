<?php
/**
 * Test script to check nonce functionality
 * Run this in your browser to test nonce generation and verification
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin permissions
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator to run this test.');
}

echo "<h1>Nonce Test Results</h1>";

// Test 1: Generate nonces
echo "<h2>1. Nonce Generation</h2>";
$custom_nonce = wp_create_nonce('wp_mm_slash_jira_nonce');
$rest_nonce = wp_create_nonce('wp_rest');

echo "<p><strong>Custom Nonce:</strong> " . $custom_nonce . "</p>";
echo "<p><strong>REST Nonce:</strong> " . $rest_nonce . "</p>";

// Test 2: Verify nonces
echo "<h2>2. Nonce Verification</h2>";
$custom_valid = wp_verify_nonce($custom_nonce, 'wp_mm_slash_jira_nonce');
$rest_valid = wp_verify_nonce($rest_nonce, 'wp_rest');

echo "<p><strong>Custom Nonce Valid:</strong> " . ($custom_valid ? 'YES' : 'NO') . "</p>";
echo "<p><strong>REST Nonce Valid:</strong> " . ($rest_valid ? 'YES' : 'NO') . "</p>";

// Test 3: Cross-verification
echo "<h2>3. Cross-Verification</h2>";
$custom_with_rest = wp_verify_nonce($custom_nonce, 'wp_rest');
$rest_with_custom = wp_verify_nonce($rest_nonce, 'wp_mm_slash_jira_nonce');

echo "<p><strong>Custom Nonce with REST action:</strong> " . ($custom_with_rest ? 'YES' : 'NO') . "</p>";
echo "<p><strong>REST Nonce with Custom action:</strong> " . ($rest_with_custom ? 'YES' : 'NO') . "</p>";

// Test 4: Current user info
echo "<h2>4. Current User Information</h2>";
$current_user = wp_get_current_user();
echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
echo "<p><strong>Username:</strong> " . $current_user->user_login . "</p>";
echo "<p><strong>Can manage options:</strong> " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</p>";

// Test 5: REST API URL
echo "<h2>5. REST API Information</h2>";
echo "<p><strong>REST URL:</strong> " . rest_url('jira/mattermost/slash/') . "</p>";
echo "<p><strong>Admin URL:</strong> " . admin_url() . "</p>";

// Test 6: Test API endpoints
echo "<h2>6. API Endpoint Test</h2>";
echo "<p>Testing mappings endpoint...</p>";

// Simulate a REST request
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/mappings');
$request->add_header('X-WP-Nonce', $rest_nonce);

// Get the API instance
$api = new WP_MM_Slash_Jira_API();
$response = $api->get_mappings($request);

if (is_wp_error($response)) {
    echo "<p><strong>Error:</strong> " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Success:</strong> Mappings endpoint working correctly</p>";
    echo "<p><strong>Response Status:</strong> " . $response->get_status() . "</p>";
}

echo "<h2>7. JavaScript Test</h2>";
echo "<p>Open browser console and run this JavaScript:</p>";
echo "<pre>";
echo "console.log('Custom nonce:', '" . $custom_nonce . "');\n";
echo "console.log('REST nonce:', '" . $rest_nonce . "');\n";
echo "console.log('REST URL:', '" . rest_url('jira/mattermost/slash/') . "');\n";
echo "</pre>";

echo "<h2>8. Manual Test Instructions</h2>";
echo "<ol>";
echo "<li>Go to the plugin admin page</li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to the Console tab</li>";
echo "<li>Run: <code>console.log(wp_mm_slash_jira)</code></li>";
echo "<li>Check if nonce values are present</li>";
echo "<li>Try clicking on a log entry to test log details</li>";
echo "</ol>";
?> 