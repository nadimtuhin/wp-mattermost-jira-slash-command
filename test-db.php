<?php
/**
 * Test script to check database tables and permissions
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>Database Test Results</h1>";

global $wpdb;

// Test 1: Check if mappings table exists
$table_name = $wpdb->prefix . 'mm_jira_mappings';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo "<h2>Test 1: Mappings Table</h2>";
if ($table_exists) {
    echo "<p style='color: green;'>✅ Mappings table exists</p>";
    
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<h3>Table Structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column->Field} - {$column->Type}</li>";
    }
    echo "</ul>";
    
    // Check if table has data
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Number of mappings: $count</p>";
    
} else {
    echo "<p style='color: red;'>❌ Mappings table does not exist</p>";
    echo "<p>Please deactivate and reactivate the plugin to create the table.</p>";
}

// Test 2: Check if logs table exists
$logs_table_name = $wpdb->prefix . 'mm_jira_logs';
$logs_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table_name'") === $logs_table_name;

echo "<h2>Test 2: Logs Table</h2>";
if ($logs_table_exists) {
    echo "<p style='color: green;'>✅ Logs table exists</p>";
    
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $logs_table_name");
    echo "<h3>Table Structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column->Field} - {$column->Type}</li>";
    }
    echo "</ul>";
    
    // Check if table has data
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table_name");
    echo "<p>Number of logs: $count</p>";
    
} else {
    echo "<p style='color: red;'>❌ Logs table does not exist</p>";
    echo "<p>Please deactivate and reactivate the plugin to create the table.</p>";
}

// Test 3: Check REST API endpoint
echo "<h2>Test 3: REST API Endpoint</h2>";
$rest_url = rest_url('jira/mattermost/slash/mappings');
echo "<p>REST URL: <code>$rest_url</code></p>";

// Test 4: Check user permissions
echo "<h2>Test 4: User Permissions</h2>";
echo "<p>Current user: " . wp_get_current_user()->user_login . "</p>";
echo "<p>Can manage options: " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</p>";

// Test 5: Check plugin options
echo "<h2>Test 5: Plugin Options</h2>";
$options = array(
    'wp_mm_slash_jira_jira_domain',
    'wp_mm_slash_jira_api_key',
    'wp_mm_slash_jira_webhook_token',
    'wp_mm_slash_jira_enable_logging'
);

foreach ($options as $option) {
    $value = get_option($option);
    echo "<p><strong>$option:</strong> " . ($value ? 'Set' : 'Not set') . "</p>";
}

echo "<h2>Recommendations</h2>";
if (!$table_exists || !$logs_table_exists) {
    echo "<p style='color: red;'>⚠️ Please deactivate and reactivate the plugin to create missing database tables.</p>";
}

if (!current_user_can('manage_options')) {
    echo "<p style='color: red;'>⚠️ You need administrator permissions to access the plugin settings.</p>";
}
?> 