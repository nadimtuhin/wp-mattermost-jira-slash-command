<?php
/**
 * Test script to check REST API route registration
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>REST API Route Registration Test</h1>";

// Test 1: Check if REST API is available
echo "<h2>1. REST API Availability</h2>";
$rest_url = rest_url();
echo "<p><strong>REST API Base URL:</strong> <code>$rest_url</code></p>";

// Test 2: Check if our routes are registered
echo "<h2>2. Registered Routes</h2>";
$server = rest_get_server();
$routes = $server->get_routes();

$our_routes = array();
foreach ($routes as $route => $handlers) {
    if (strpos($route, 'jira/mattermost/slash') !== false) {
        $our_routes[$route] = $handlers;
    }
}

if (empty($our_routes)) {
    echo "<p style='color: red;'>❌ No jira/mattermost/slash routes found!</p>";
    echo "<p>This means the plugin routes are not registered properly.</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($our_routes) . " jira/mattermost/slash routes:</p>";
    echo "<ul>";
    foreach ($our_routes as $route => $handlers) {
        echo "<li><strong>$route</strong></li>";
        foreach ($handlers as $method => $handler) {
            echo "<ul><li>$method: " . (is_array($handler['callback']) ? get_class($handler['callback'][0]) . '::' . $handler['callback'][1] : 'function') . "</li></ul>";
        }
    }
    echo "</ul>";
}

// Test 3: Check specific route for logs
echo "<h2>3. Log Details Route Check</h2>";
$logs_route = '/jira/mattermost/slash/logs/(?P<id>\d+)';
if (isset($routes[$logs_route])) {
    echo "<p style='color: green;'>✅ Log details route is registered</p>";
    $handlers = $routes[$logs_route];
    foreach ($handlers as $method => $handler) {
        echo "<p><strong>$method:</strong> " . (is_array($handler['callback']) ? get_class($handler['callback'][0]) . '::' . $handler['callback'][1] : 'function') . "</p>";
        echo "<p><strong>Permission Callback:</strong> " . (is_array($handler['permission_callback']) ? get_class($handler['permission_callback'][0]) . '::' . $handler['permission_callback'][1] : 'function') . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Log details route is NOT registered</p>";
}

// Test 4: Check mappings route
echo "<h2>4. Mappings Route Check</h2>";
$mappings_route = '/jira/mattermost/slash/mappings';
if (isset($routes[$mappings_route])) {
    echo "<p style='color: green;'>✅ Mappings route is registered</p>";
    $handlers = $routes[$mappings_route];
    foreach ($handlers as $method => $handler) {
        echo "<p><strong>$method:</strong> " . (is_array($handler['callback']) ? get_class($handler['callback'][0]) . '::' . $handler['callback'][1] : 'function') . "</p>";
        echo "<p><strong>Permission Callback:</strong> " . (is_array($handler['permission_callback']) ? get_class($handler['permission_callback'][0]) . '::' . $handler['permission_callback'][1] : 'function') . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Mappings route is NOT registered</p>";
}

// Test 5: Test route discovery
echo "<h2>5. Route Discovery Test</h2>";
$discovery_url = rest_url('jira/mattermost/slash/');
echo "<p><strong>Discovery URL:</strong> <code>$discovery_url</code></p>";

$request = new WP_REST_Request('GET', '/jira/mattermost/slash/');
$response = $server->dispatch($request);

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Route discovery failed: " . $response->get_error_message() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Route discovery successful</p>";
    echo "<p><strong>Response Status:</strong> " . $response->get_status() . "</p>";
}

// Test 6: Check if plugin is active
echo "<h2>6. Plugin Status</h2>";
if (is_plugin_active('wp-mm-slash-jira/wp-mm-slash-jira.php')) {
    echo "<p style='color: green;'>✅ Plugin is active</p>";
} else {
    echo "<p style='color: red;'>❌ Plugin is NOT active</p>";
}

// Test 7: Check if our classes exist
echo "<h2>7. Class Availability</h2>";
$classes = array(
    'WP_MM_Slash_Jira',
    'WP_MM_Slash_Jira_API',
    'WP_MM_Slash_Jira_Admin',
    'WP_MM_Slash_Jira_Logger'
);

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<p style='color: green;'>✅ $class exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $class does NOT exist</p>";
    }
}

// Test 8: Check if REST API init hook was called
echo "<h2>8. REST API Initialization</h2>";
global $wp_filter;
if (isset($wp_filter['rest_api_init'])) {
    echo "<p style='color: green;'>✅ rest_api_init hook is registered</p>";
    $callbacks = $wp_filter['rest_api_init']->callbacks;
    $our_callbacks = array();
    foreach ($callbacks as $priority => $priority_callbacks) {
        foreach ($priority_callbacks as $callback) {
            if (is_array($callback['function']) && is_object($callback['function'][0])) {
                $class_name = get_class($callback['function'][0]);
                if (strpos($class_name, 'WP_MM_Slash_Jira') !== false) {
                    $our_callbacks[] = $class_name . '::' . $callback['function'][1];
                }
            }
        }
    }
    if (!empty($our_callbacks)) {
        echo "<p>Our plugin callbacks:</p><ul>";
        foreach ($our_callbacks as $callback) {
            echo "<li>$callback</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No plugin callbacks found in rest_api_init</p>";
    }
} else {
    echo "<p style='color: red;'>❌ rest_api_init hook is NOT registered</p>";
}

echo "<h2>9. Recommendations</h2>";
if (empty($our_routes)) {
    echo "<p style='color: red;'>❌ <strong>CRITICAL:</strong> Routes are not registered. Try:</p>";
    echo "<ul>";
    echo "<li>Deactivate and reactivate the plugin</li>";
    echo "<li>Check if there are any PHP errors in the WordPress debug log</li>";
    echo "<li>Verify that all plugin files are present and readable</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ Routes are registered. The issue might be with:</p>";
    echo "<ul>";
    echo "<li>Nonce expiration or invalidity</li>";
    echo "<li>User permissions</li>";
    echo "<li>Log ID not existing in database</li>";
    echo "<li>WordPress configuration issues</li>";
    echo "</ul>";
}

echo "<h2>10. Debug Information</h2>";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Site URL:</strong> " . get_site_url() . "</p>";
echo "<p><strong>Home URL:</strong> " . get_home_url() . "</p>";
echo "<p><strong>REST API URL:</strong> " . rest_url() . "</p>";
echo "<p><strong>Current User:</strong> " . wp_get_current_user()->user_login . "</p>";
echo "<p><strong>User Capabilities:</strong> " . implode(', ', array_keys(wp_get_current_user()->allcaps)) . "</p>"; 