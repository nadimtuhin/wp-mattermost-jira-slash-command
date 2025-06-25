<?php
/**
 * Test script to verify Jira domain format
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>Jira Domain Format Test</h1>";

// Function to clean and validate Jira domain (same as in the plugin)
function clean_jira_domain($domain) {
    // Remove any protocol (http:// or https://)
    $domain = preg_replace('/^https?:\/\//', '', $domain);
    
    // Remove trailing slash
    $domain = rtrim($domain, '/');
    
    // Remove any path after domain
    $domain = parse_url('http://' . $domain, PHP_URL_HOST);
    
    // Validate domain format (should be like your-domain.atlassian.net)
    if (!preg_match('/^[a-zA-Z0-9\-\.]+\.atlassian\.net$/', $domain)) {
        return '';
    }
    
    return $domain;
}

// Get current domain setting
$current_domain = get_option('wp_mm_slash_jira_jira_domain');

echo "<h2>Current Domain Setting</h2>";
echo "<p><strong>Raw value:</strong> " . ($current_domain ?: 'Not set') . "</p>";

if ($current_domain) {
    $cleaned_domain = clean_jira_domain($current_domain);
    
    if ($cleaned_domain) {
        echo "<p style='color: green;'>✅ <strong>Valid domain:</strong> {$cleaned_domain}</p>";
        echo "<p><strong>Full URL:</strong> https://{$cleaned_domain}</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Invalid domain format</strong></p>";
        echo "<p>The domain should be in format: <code>your-domain.atlassian.net</code></p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No domain configured</p>";
}

echo "<h2>Test Different Domain Formats</h2>";

$test_domains = array(
    'company.atlassian.net',
    'https://company.atlassian.net',
    'https://company.atlassian.net/',
    'http://company.atlassian.net',
    'company.atlassian.net/some/path',
    'invalid-domain.com',
    'company.atlassian.com',
    'https://',
    'just-text'
);

echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>Input</th><th>Cleaned Result</th><th>Status</th></tr>";

foreach ($test_domains as $test_domain) {
    $cleaned = clean_jira_domain($test_domain);
    $status = $cleaned ? '✅ Valid' : '❌ Invalid';
    $status_color = $cleaned ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($test_domain) . "</code></td>";
    echo "<td><code>" . htmlspecialchars($cleaned ?: 'N/A') . "</code></td>";
    echo "<td style='color: {$status_color};'>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li>✅ Use format: <code>your-domain.atlassian.net</code></li>";
echo "<li>❌ Don't include <code>https://</code> or <code>http://</code></li>";
echo "<li>❌ Don't include trailing slashes or paths</li>";
echo "<li>✅ Must end with <code>.atlassian.net</code></li>";
echo "</ul>";

echo "<h2>How to Fix</h2>";
echo "<p>If your domain is invalid:</p>";
echo "<ol>";
echo "<li>Go to Settings > MM Jira Integration</li>";
echo "<li>Update the Jira Domain field</li>";
echo "<li>Use only the domain part (e.g., <code>company.atlassian.net</code>)</li>";
echo "<li>Save the settings</li>";
echo "<li>Test again</li>";
echo "</ol>";

echo "<p><a href='" . admin_url('options-general.php?page=wp-mm-slash-jira') . "'>← Back to Plugin Settings</a></p>";
?> 