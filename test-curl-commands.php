<?php
/**
 * Test script to demonstrate curl command functionality in log details
 * Place this file in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>🔧 Curl Command Testing</h1>";
echo "<p>This test demonstrates the new curl command functionality in log details.</p>";

// Test 1: Check if logs table exists and has data
global $wpdb;
$table_name = $wpdb->prefix . 'mm_jira_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo "<h2>Test 1: Database Check</h2>";
if ($table_exists) {
    echo "<p style='color: green;'>✅ Logs table exists</p>";
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Total logs: $count</p>";
    
    if ($count > 0) {
        // Get sample logs
        $webhook_logs = $wpdb->get_results("SELECT * FROM $table_name WHERE request_payload NOT LIKE '%\"method\"%' ORDER BY id DESC LIMIT 3");
        $curl_logs = $wpdb->get_results("SELECT * FROM $table_name WHERE request_payload LIKE '%\"method\"%' ORDER BY id DESC LIMIT 3");
        
        echo "<h3>Sample Webhook Logs:</h3>";
        if (!empty($webhook_logs)) {
            foreach ($webhook_logs as $log) {
                echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>ID:</strong> {$log->id}<br>";
                echo "<strong>Command:</strong> {$log->command}<br>";
                echo "<strong>Status:</strong> {$log->status}<br>";
                echo "<strong>Timestamp:</strong> {$log->timestamp}<br>";
                echo "<a href='#' onclick='showWebhookCurl(\"" . htmlspecialchars($log->request_payload) . "\")'>🔧 Show Curl Command</a>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No webhook logs found</p>";
        }
        
        echo "<h3>Sample Jira API Logs:</h3>";
        if (!empty($curl_logs)) {
            foreach ($curl_logs as $log) {
                echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>ID:</strong> {$log->id}<br>";
                echo "<strong>Command:</strong> {$log->command}<br>";
                echo "<strong>Status:</strong> {$log->status}<br>";
                echo "<strong>Timestamp:</strong> {$log->timestamp}<br>";
                echo "<a href='#' onclick='showApiCurl(\"" . htmlspecialchars($log->request_payload) . "\")'>🔧 Show Curl Command</a>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No Jira API logs found</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No logs found in database</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Logs table does not exist</p>";
}

// Test 2: Generate sample curl commands
echo "<h2>Test 2: Sample Curl Commands</h2>";

echo "<h3>Sample Webhook Curl Command:</h3>";
$webhook_url = rest_url('jira/mattermost/slash/jira');
$webhook_token = get_option('wp_mm_slash_jira_webhook_token');
echo "<div style='background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; font-family: monospace; margin: 10px 0;'>";
echo "curl -X POST \"$webhook_url\" \\<br>";
echo "  -d \"token=$webhook_token\" \\<br>";
echo "  -d \"channel_id=fukxanjgjbnp7ng383at53k1sy\" \\<br>";
echo "  -d \"channel_name=general\" \\<br>";
echo "  -d \"text=create Fix login bug\" \\<br>";
echo "  -d \"user_name=" . wp_get_current_user()->user_login . "\"";
echo "</div>";

echo "<h3>Sample Jira API Curl Command:</h3>";
$jira_domain = get_option('wp_mm_slash_jira_jira_domain');
$api_key = get_option('wp_mm_slash_jira_api_key');
if ($jira_domain && $api_key) {
    echo "<div style='background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; font-family: monospace; margin: 10px 0;'>";
    echo "curl -X POST \"https://$jira_domain/rest/api/3/issue\" \\<br>";
    echo "  -H \"Authorization: Basic " . base64_encode($api_key) . "\" \\<br>";
    echo "  -H \"Content-Type: application/json\" \\<br>";
    echo "  -H \"Accept: application/json\" \\<br>";
    echo "  -d '{<br>";
    echo "    \"fields\": {<br>";
    echo "      \"project\": {<br>";
    echo "        \"key\": \"PROJ\"<br>";
    echo "      },<br>";
    echo "      \"summary\": \"Fix login bug\",<br>";
    echo "      \"description\": \"Issue created from Mattermost\",<br>";
    echo "      \"issuetype\": {<br>";
    echo "        \"name\": \"Bug\"<br>";
    echo "      }<br>";
    echo "    }<br>";
    echo "  }'";
    echo "</div>";
} else {
    echo "<p style='color: orange;'>⚠️ Jira domain or API key not configured</p>";
}

// Test 3: Functionality demonstration
echo "<h2>Test 3: Functionality Features</h2>";
echo "<div style='background: #e3fcef; padding: 15px; border-radius: 5px; border-left: 4px solid #006644;'>";
echo "<h3>✅ New Features Added:</h3>";
echo "<ul>";
echo "<li>🔧 <strong>Curl Command Generation:</strong> Automatically generates curl commands for both webhook and Jira API logs</li>";
echo "<li>📋 <strong>Copy to Clipboard:</strong> One-click copy functionality with visual feedback</li>";
echo "<li>🎨 <strong>Syntax Highlighting:</strong> Dark theme with proper formatting for curl commands</li>";
echo "<li>📱 <strong>Responsive Design:</strong> Works on all device sizes</li>";
echo "<li>🔄 <strong>Smart Parsing:</strong> Automatically detects log type and generates appropriate commands</li>";
echo "<li>🛡️ <strong>Security:</strong> Proper HTML escaping and parameter sanitization</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Test 4: Usage Instructions</h2>";
echo "<div style='background: #fff7e6; padding: 15px; border-radius: 5px; border-left: 4px solid #974f0c;'>";
echo "<h3>📖 How to Use:</h3>";
echo "<ol>";
echo "<li>Go to <strong>Settings > MM Jira Integration > Logs</strong> tab</li>";
echo "<li>Click <strong>View Details</strong> on any log entry</li>";
echo "<li>Scroll down to the <strong>🔧 Curl Command for Testing</strong> section</li>";
echo "<li>Click <strong>📋 Copy Command</strong> to copy the curl command to clipboard</li>";
echo "<li>Paste the command in your terminal to reproduce the exact request</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Test 5: Benefits</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa;'>";
echo "<h3>🚀 Benefits:</h3>";
echo "<ul>";
echo "<li><strong>Debugging:</strong> Easily reproduce issues by running the exact same request</li>";
echo "<li><strong>Testing:</strong> Test API endpoints with real data from your logs</li>";
echo "<li><strong>Documentation:</strong> Generate examples for API documentation</li>";
echo "<li><strong>Support:</strong> Share exact commands with support teams</li>";
echo "<li><strong>Learning:</strong> Understand how the integration works by examining the requests</li>";
echo "</ul>";
echo "</div>";

?>

<script>
function showWebhookCurl(payload) {
    try {
        var data = JSON.parse(payload);
        var curlCmd = 'curl -X POST "' + '<?php echo rest_url("jira/mattermost/slash/jira"); ?>' + '" \\';
        
        if (data.token) curlCmd += '\n  -d "token=' + data.token + '" \\';
        if (data.channel_id) curlCmd += '\n  -d "channel_id=' + data.channel_id + '" \\';
        if (data.channel_name) curlCmd += '\n  -d "channel_name=' + data.channel_name + '" \\';
        if (data.text) curlCmd += '\n  -d "text=' + data.text + '" \\';
        if (data.user_name) curlCmd += '\n  -d "user_name=' + data.user_name + '" \\';
        
        curlCmd = curlCmd.slice(0, -2); // Remove trailing backslash and space
        
        alert('Webhook Curl Command:\n\n' + curlCmd);
    } catch (e) {
        alert('Error parsing webhook payload: ' + e.message);
    }
}

function showApiCurl(payload) {
    try {
        var data = JSON.parse(payload);
        var curlCmd = 'curl -X ' + data.method + ' "' + data.url + '" \\';
        
        if (data.request && data.request.headers) {
            for (var header in data.request.headers) {
                curlCmd += '\n  -H "' + header + ': ' + data.request.headers[header] + '" \\';
            }
        }
        
        if (data.request && data.request.body) {
            curlCmd += '\n  -d \'' + data.request.body.replace(/'/g, "'\"'\"'") + '\'';
        }
        
        alert('Jira API Curl Command:\n\n' + curlCmd);
    } catch (e) {
        alert('Error parsing API payload: ' + e.message);
    }
}
</script>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    margin: 20px;
    background: #f1f1f1;
}

h1, h2, h3 {
    color: #333;
}

h1 {
    border-bottom: 3px solid #0073aa;
    padding-bottom: 10px;
}

h2 {
    border-bottom: 2px solid #ddd;
    padding-bottom: 5px;
    margin-top: 30px;
}

h3 {
    color: #0073aa;
    margin-top: 20px;
}

a {
    color: #0073aa;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

div[style*="background: #f8f9fa"] {
    border: 1px solid #ddd;
}

div[style*="background: #2d3748"] {
    border: 1px solid #4a5568;
    font-size: 12px;
    line-height: 1.4;
}

ul, ol {
    margin: 10px 0;
    padding-left: 20px;
}

li {
    margin: 5px 0;
}
</style> 