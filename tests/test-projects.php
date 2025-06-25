<?php
/**
 * Test Projects Command
 * 
 * This page tests the /jira projects command functionality
 */

// Load WordPress
require_once('../../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

// Get plugin settings
$jira_domain = get_option('wp_mm_slash_jira_jira_domain');
$api_user_email = get_option('wp_mm_slash_jira_api_user_email');
$api_key = get_option('wp_mm_slash_jira_api_key');
$webhook_token = get_option('wp_mm_slash_jira_webhook_token');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Projects Command - WP Mattermost Jira Integration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f5f7;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #0052cc;
            margin-bottom: 10px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .status-item {
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .status-ok {
            background: #e3fcef;
            border-left-color: #36b37e;
        }
        
        .status-warning {
            background: #fff7e6;
            border-left-color: #ff8b00;
        }
        
        .status-error {
            background: #ffebe6;
            border-left-color: #de350b;
        }
        
        .test-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .test-section h2 {
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #e1e5e9;
            padding-bottom: 10px;
        }
        
        .test-button {
            background: #0052cc;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin: 5px;
            transition: background 0.2s;
        }
        
        .test-button:hover {
            background: #0047b3;
        }
        
        .test-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .result-area {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e1e5e9;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #6b778c;
        }
        
        .success {
            color: #36b37e;
        }
        
        .error {
            color: #de350b;
        }
        
        .warning {
            color: #ff8b00;
        }
        
        .info {
            color: #0052cc;
        }
        
        .curl-example {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
            margin: 10px 0;
            overflow-x: auto;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li:before {
            content: "✅ ";
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Projects Command</h1>
            <p>Test the <code>/jira projects</code> command functionality and Jira API integration</p>
            
            <div class="status-grid">
                <div class="status-item <?php echo $jira_domain ? 'status-ok' : 'status-error'; ?>">
                    <strong>Jira Domain:</strong><br>
                    <?php echo $jira_domain ? $jira_domain : 'Not configured'; ?>
                </div>
                <div class="status-item <?php echo $api_user_email ? 'status-ok' : 'status-error'; ?>">
                    <strong>API User Email:</strong><br>
                    <?php echo $api_user_email ? $api_user_email : 'Not configured'; ?>
                </div>
                <div class="status-item <?php echo $api_key ? 'status-ok' : 'status-error'; ?>">
                    <strong>API Key:</strong><br>
                    <?php echo $api_key ? 'Configured' : 'Not configured'; ?>
                </div>
                <div class="status-item <?php echo $webhook_token ? 'status-ok' : 'status-error'; ?>">
                    <strong>Webhook Token:</strong><br>
                    <?php echo $webhook_token ? 'Configured' : 'Not configured'; ?>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>📋 Projects Command Features</h2>
            <p>The <code>/jira projects</code> command provides the following functionality:</p>
            
            <ul class="feature-list">
                <li><strong>List All Projects:</strong> Fetches all available Jira projects from your instance</li>
                <li><strong>Alphabetical Organization:</strong> Groups projects by first letter for easy browsing</li>
                <li><strong>Project Details:</strong> Shows project key, name, and direct link to Jira</li>
                <li><strong>Usage Instructions:</strong> Provides helpful commands for binding and creating issues</li>
                <li><strong>Error Handling:</strong> Graceful error messages for configuration issues</li>
                <li><strong>API Logging:</strong> Logs all API calls for debugging and monitoring</li>
                <li><strong>Authentication:</strong> Uses configured Jira API credentials</li>
                <li><strong>Response Format:</strong> Clean, formatted output suitable for Mattermost</li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>🚀 Test Projects Command</h2>
            <p>Test the projects command through the webhook endpoint:</p>
            
            <button class="test-button" onclick="testProjectsCommand()">Test /jira projects</button>
            <button class="test-button" onclick="testProjectsWithError()">Test with Invalid Token</button>
            <button class="test-button" onclick="clearResults()">Clear Results</button>
            
            <div id="loading" class="loading" style="display: none;">
                ⏳ Testing projects command...
            </div>
            
            <div id="result" class="result-area" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h2>🔧 Manual Testing with cURL</h2>
            <p>You can also test the command manually using cURL:</p>
            
            <div class="curl-example">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=test-channel-123" \
  -d "channel_name=test-channel" \
  -d "text=projects" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>
            
            <p><strong>Expected Response:</strong></p>
            <ul>
                <li>List of all available Jira projects grouped alphabetically</li>
                <li>Project keys, names, and direct links to Jira</li>
                <li>Instructions for binding channels and creating issues</li>
                <li>Total project count</li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>🔍 Jira API Testing</h2>
            <p>Test the Jira API directly to verify credentials and connectivity:</p>
            
            <button class="test-button" onclick="testJiraAPI()">Test Jira API Directly</button>
            <button class="test-button" onclick="testJiraAuth()">Test Jira Authentication</button>
            
            <div id="api-loading" class="loading" style="display: none;">
                ⏳ Testing Jira API...
            </div>
            
            <div id="api-result" class="result-area" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h2>📊 Expected Output Format</h2>
            <p>The projects command should return a formatted response like this:</p>
            
            <div class="curl-example">
📋 **Available Jira Projects**

**Total Projects:** 15

**A**
• **API** - [API Development](https://your-domain.atlassian.net/browse/API)
• **APP** - [Application Development](https://your-domain.atlassian.net/browse/APP)

**B**
• **BUG** - [Bug Tracking](https://your-domain.atlassian.net/browse/BUG)

**D**
• **DEV** - [Development](https://your-domain.atlassian.net/browse/DEV)

**T**
• **TEST** - [Testing](https://your-domain.atlassian.net/browse/TEST)

**To bind this channel to a project:**
• `/jira bind PROJECT-KEY` - Replace PROJECT-KEY with one of the keys above

**To create issues in a specific project:**
• `/jira create PROJECT-KEY Title` - Create issue in specific project
• `/jira bug PROJECT-KEY Title` - Create bug in specific project
• `/jira task PROJECT-KEY Title` - Create task in specific project
• `/jira story PROJECT-KEY Title` - Create story in specific project

**Other Commands:**
• `/jira status` - Check current project binding
• `/jira board` - Get board links for current project
• `/jira link` - Get issue creation links
            </div>
        </div>
    </div>

    <script>
        function testProjectsCommand() {
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            const formData = new FormData();
            formData.append('token', '<?php echo esc_js($webhook_token); ?>');
            formData.append('channel_id', 'test-channel-123');
            formData.append('channel_name', 'test-channel');
            formData.append('text', 'projects');
            formData.append('user_name', '<?php echo esc_js(wp_get_current_user()->user_login); ?>');
            
            fetch('<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.text) {
                    result.innerHTML = '<span class="success">✅ Success!</span>\n\n' + data.text;
                } else {
                    result.innerHTML = '<span class="error">❌ Error: No response text</span>\n\n' + JSON.stringify(data, null, 2);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.innerHTML = '<span class="error">❌ Error: ' + error.message + '</span>';
            });
        }
        
        function testProjectsWithError() {
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            const formData = new FormData();
            formData.append('token', 'invalid-token');
            formData.append('channel_id', 'test-channel-123');
            formData.append('channel_name', 'test-channel');
            formData.append('text', 'projects');
            formData.append('user_name', '<?php echo esc_js(wp_get_current_user()->user_login); ?>');
            
            fetch('<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.text) {
                    result.innerHTML = '<span class="warning">⚠️ Expected Error (Invalid Token):</span>\n\n' + data.text;
                } else {
                    result.innerHTML = '<span class="error">❌ Unexpected Response:</span>\n\n' + JSON.stringify(data, null, 2);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.innerHTML = '<span class="error">❌ Error: ' + error.message + '</span>';
            });
        }
        
        function testJiraAPI() {
            const loading = document.getElementById('api-loading');
            const result = document.getElementById('api-result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            fetch('<?php echo esc_url(rest_url('jira/mattermost/slash/test-jira-api')); ?>', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.success) {
                    result.innerHTML = '<span class="success">✅ Jira API Test Successful!</span>\n\n' + JSON.stringify(data, null, 2);
                } else {
                    result.innerHTML = '<span class="error">❌ Jira API Test Failed:</span>\n\n' + JSON.stringify(data, null, 2);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.innerHTML = '<span class="error">❌ Error: ' + error.message + '</span>';
            });
        }
        
        function testJiraAuth() {
            const loading = document.getElementById('api-loading');
            const result = document.getElementById('api-result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            fetch('<?php echo esc_url(rest_url('jira/mattermost/slash/test-jira-auth')); ?>', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.success) {
                    result.innerHTML = '<span class="success">✅ Jira Authentication Successful!</span>\n\n' + JSON.stringify(data, null, 2);
                } else {
                    result.innerHTML = '<span class="error">❌ Jira Authentication Failed:</span>\n\n' + JSON.stringify(data, null, 2);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.innerHTML = '<span class="error">❌ Error: ' + error.message + '</span>';
            });
        }
        
        function clearResults() {
            document.getElementById('result').style.display = 'none';
            document.getElementById('api-result').style.display = 'none';
        }
    </script>
</body>
</html> 