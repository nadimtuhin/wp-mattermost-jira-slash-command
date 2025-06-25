<?php
/**
 * Test script to check user permissions and authentication
 * Run this in your browser to test user permissions
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>User Permissions Test Results</h1>";

// Test 1: Check if user is logged in
echo "<h2>1. User Authentication</h2>";
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<p><strong>Status:</strong> ✅ User is logged in</p>";
    echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
    echo "<p><strong>Username:</strong> " . $current_user->user_login . "</p>";
    echo "<p><strong>Email:</strong> " . $current_user->user_email . "</p>";
    echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";
} else {
    echo "<p><strong>Status:</strong> ❌ User is NOT logged in</p>";
    echo "<p>You must be logged in to access this test.</p>";
    exit;
}

// Test 2: Check specific capabilities
echo "<h2>2. User Capabilities</h2>";
$capabilities = array(
    'manage_options' => 'Manage Options (Admin)',
    'edit_posts' => 'Edit Posts',
    'read' => 'Read',
    'level_10' => 'Level 10',
    'level_0' => 'Level 0'
);

foreach ($capabilities as $capability => $description) {
    $has_cap = current_user_can($capability);
    $status = $has_cap ? '✅ YES' : '❌ NO';
    echo "<p><strong>{$description}:</strong> {$status}</p>";
}

// Test 3: Check user levels
echo "<h2>3. User Levels</h2>";
$user_level = get_user_meta($current_user->ID, 'user_level', true);
echo "<p><strong>User Level:</strong> " . ($user_level ? $user_level : 'Not set') . "</p>";

// Test 4: Check if user is admin
echo "<h2>4. Admin Status</h2>";
$is_admin = is_super_admin($current_user->ID);
echo "<p><strong>Is Super Admin:</strong> " . ($is_admin ? '✅ YES' : '❌ NO') . "</p>";

$is_admin_role = in_array('administrator', $current_user->roles);
echo "<p><strong>Has Administrator Role:</strong> " . ($is_admin_role ? '✅ YES' : '❌ NO') . "</p>";

// Test 5: Test current_user_can function directly
echo "<h2>5. Direct Permission Test</h2>";
$test_result = current_user_can('manage_options');
echo "<p><strong>current_user_can('manage_options'):</strong> " . ($test_result ? '✅ TRUE' : '❌ FALSE') . "</p>";

// Test 6: Check WordPress version and environment
echo "<h2>6. WordPress Environment</h2>";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>Site URL:</strong> " . get_site_url() . "</p>";
echo "<p><strong>Admin URL:</strong> " . admin_url() . "</p>";
echo "<p><strong>REST API URL:</strong> " . rest_url() . "</p>";

// Test 7: Check if we're in admin context
echo "<h2>7. Context Information</h2>";
echo "<p><strong>Is Admin:</strong> " . (is_admin() ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><strong>Is AJAX:</strong> " . (wp_doing_ajax() ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><strong>Is REST API:</strong> " . (defined('REST_REQUEST') && REST_REQUEST ? '✅ YES' : '❌ NO') . "</p>";

// Test 8: Test REST API authentication
echo "<h2>8. REST API Authentication Test</h2>";
echo "<p>Testing REST API authentication...</p>";

// Create a simple REST request
$request = new WP_REST_Request('GET', '/wp/v2/posts');
$request->set_param('per_page', 1);

// Get the REST server
$server = rest_get_server();

// Dispatch the request
$response = $server->dispatch($request);

if (is_wp_error($response)) {
    echo "<p><strong>REST API Test:</strong> ❌ Error - " . $response->get_error_message() . "</p>";
} else {
    echo "<p><strong>REST API Test:</strong> ✅ Success - Status: " . $response->get_status() . "</p>";
}

// Test 9: Test our specific endpoint
echo "<h2>9. Plugin Endpoint Test</h2>";
echo "<p>Testing our plugin's mappings endpoint...</p>";

// Create a request to our endpoint
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/mappings');

// Dispatch the request
$response = $server->dispatch($request);

if (is_wp_error($response)) {
    echo "<p><strong>Plugin Endpoint Test:</strong> ❌ Error - " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Plugin Endpoint Test:</strong> ✅ Success - Status: " . $response->get_status() . "</p>";
    $data = $response->get_data();
    echo "<p><strong>Response Type:</strong> " . gettype($data) . "</p>";
    if (is_array($data)) {
        echo "<p><strong>Number of items:</strong> " . count($data) . "</p>";
    }
}

// Test 9.5: Test log details endpoint specifically
echo "<h2>9.5. Log Details Endpoint Test</h2>";
echo "<p>Testing our plugin's log details endpoint...</p>";

// Create a request to our log details endpoint
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/1');
$request->set_param('id', 1);

// Dispatch the request
$response = $server->dispatch($request);

if (is_wp_error($response)) {
    echo "<p><strong>Log Details Endpoint Test:</strong> ❌ Error - " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Log Details Endpoint Test:</strong> ✅ Success - Status: " . $response->get_status() . "</p>";
    $data = $response->get_data();
    echo "<p><strong>Response Type:</strong> " . gettype($data) . "</p>";
}

// Test 9.6: Test direct API method call
echo "<h2>9.6. Direct API Method Test</h2>";
echo "<p>Testing direct API method call...</p>";

// Get the API instance and test directly
$api = new WP_MM_Slash_Jira_API();
$request = new WP_REST_Request('GET', '/jira/mattermost/slash/logs/1');
$request->set_param('id', 1);

$response = $api->get_log_details($request);

if (is_wp_error($response)) {
    echo "<p><strong>Direct API Method Test:</strong> ❌ Error - " . $response->get_error_message() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>";
} else {
    echo "<p><strong>Direct API Method Test:</strong> ✅ Success - Status: " . $response->get_status() . "</p>";
}

// Test 9.7: Test our permission checking function
echo "<h2>9.7. Permission Function Test</h2>";
echo "<p>Testing our custom permission checking function...</p>";

// Get the API instance and test the permission function
$api = new WP_MM_Slash_Jira_API();

// Use reflection to access the private method
$reflection = new ReflectionClass($api);
$method = $reflection->getMethod('check_admin_permissions');
$method->setAccessible(true);

$has_permission = $method->invoke($api);

echo "<p><strong>Permission Function Result:</strong> " . ($has_permission ? '✅ TRUE' : '❌ FALSE') . "</p>";

if (!$has_permission) {
    echo "<p><strong>Debug Information:</strong></p>";
    echo "<ul>";
    echo "<li>User logged in: " . (is_user_logged_in() ? 'YES' : 'NO') . "</li>";
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        echo "<li>User roles: " . implode(', ', $current_user->roles) . "</li>";
        echo "<li>Has administrator role: " . (in_array('administrator', $current_user->roles) ? 'YES' : 'NO') . "</li>";
    }
    echo "<li>Can manage options: " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</li>";
    echo "<li>Is super admin: " . (is_super_admin() ? 'YES' : 'NO') . "</li>";
    echo "</ul>";
}

// Test 10: Manual JavaScript test
echo "<h2>10. JavaScript Test</h2>";
echo "<p>Open browser console and run this JavaScript:</p>";
echo "<pre>";
echo "console.log('Testing user permissions...');\n";
echo "console.log('Current user ID:', " . get_current_user_id() . ");\n";
echo "console.log('Can manage options:', " . (current_user_can('manage_options') ? 'true' : 'false') . ");\n";
echo "console.log('REST URL:', '" . rest_url('jira/mattermost/slash/') . "');\n";
echo "fetch('" . rest_url('jira/mattermost/slash/') . "mappings')\n";
echo "  .then(response => {\n";
echo "    console.log('Response status:', response.status);\n";
echo "    return response.json();\n";
echo "  })\n";
echo "  .then(data => console.log('Data:', data))\n";
echo "  .catch(error => console.error('Error:', error));\n";
echo "</pre>";

echo "<h2>11. Troubleshooting</h2>";
echo "<p>If you're still getting permission errors:</p>";
echo "<ol>";
echo "<li>Make sure you're logged in as an administrator</li>";
echo "<li>Check if your user has the 'manage_options' capability</li>";
echo "<li>Try logging out and logging back in</li>";
echo "<li>Check if there are any security plugins blocking REST API access</li>";
echo "<li>Verify that the plugin is properly activated</li>";
echo "</ol>";
?> 