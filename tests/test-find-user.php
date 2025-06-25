<?php
/**
 * Test Find User Command
 * 
 * This page tests the /jira find command functionality
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
    <title>Test Find User Command - WP Mattermost Jira Integration</title>
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
        
        .email-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .email-input:focus {
            outline: none;
            border-color: #0052cc;
        }
        
        .test-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Test Find User Command</h1>
            <p>Test the <code>/jira find</code> command functionality and Jira user search integration</p>
            
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
            <h2>🔍 Find User Command Features</h2>
            <p>The <code>/jira find</code> command provides the following functionality:</p>
            
            <ul class="feature-list">
                <li><strong>Email Search:</strong> Search for Jira users by email address</li>
                <li><strong>Exact Matching:</strong> Find users with exact email matches</li>
                <li><strong>Partial Matching:</strong> Find users with partial email or name matches</li>
                <li><strong>User Details:</strong> Display comprehensive user information</li>
                <li><strong>Account Status:</strong> Show if user account is active or inactive</li>
                <li><strong>Profile Links:</strong> Direct links to user profiles in Jira</li>
                <li><strong>Assignment Help:</strong> Quick commands for assigning issues</li>
                <li><strong>Error Handling:</strong> Clear error messages for invalid emails</li>
                <li><strong>API Logging:</strong> Logs all API calls for debugging</li>
                <li><strong>Email Validation:</strong> Validates email format before searching</li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>🚀 Test Find User Command</h2>
            <p>Test the find user command with different email addresses:</p>
            
            <div class="test-form">
                <div class="form-group">
                    <label for="email-input">Email Address:</label>
                    <input type="email" id="email-input" class="email-input" placeholder="user@example.com" value="developer@company.com">
                </div>
                <button class="test-button" onclick="testFindUser()">Test /jira find</button>
            </div>
            
            <div style="margin-bottom: 15px;">
                <button class="test-button" onclick="testWithCurrentEmail()">Test with Current Email</button>
                <button class="test-button" onclick="testInvalidEmail()">Test Invalid Email</button>
                <button class="test-button" onclick="testEmptyEmail()">Test Empty Email</button>
                <button class="test-button" onclick="clearResults()">Clear Results</button>
            </div>
            
            <div id="loading" class="loading" style="display: none;">
                ⏳ Searching for user...
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
  -d "text=find developer@company.com" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>
            
            <p><strong>Expected Response:</strong></p>
            <ul>
                <li>User search results with exact and partial matches</li>
                <li>User details including name, email, account ID, status, and timezone</li>
                <li>Direct links to user profiles in Jira</li>
                <li>Instructions for assigning issues to found users</li>
                <li>Clear error messages for invalid or non-existent users</li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>🔍 Jira User Search API Testing</h2>
            <p>Test the Jira User Search API directly:</p>
            
            <div class="test-form">
                <div class="form-group">
                    <label for="api-email-input">Email for API Test:</label>
                    <input type="email" id="api-email-input" class="email-input" placeholder="user@example.com" value="developer@company.com">
                </div>
                <button class="test-button" onclick="testJiraUserAPI()">Test Jira User API</button>
            </div>
            
            <div id="api-loading" class="loading" style="display: none;">
                ⏳ Testing Jira User API...
            </div>
            
            <div id="api-result" class="result-area" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h2>📊 Expected Output Format</h2>
            <p>The find command should return a formatted response like this:</p>
            
            <div class="curl-example">
🔍 **User Search Results for: developer@company.com**

✅ **Exact Email Matches:**
• **John Developer**
  📧 Email: `developer@company.com`
  🆔 Account ID: `5d1234567890abcdef123456`
  📊 Status: Active
  🌍 Timezone: America/New_York
  🔗 [View Profile](https://your-domain.atlassian.net/secure/ViewProfile.jspa?name=5d1234567890abcdef123456)

🔍 **Partial Matches:**
• **Jane Developer**
  📧 Email: `jane.developer@company.com`
  🆔 Account ID: `5d1234567890abcdef123457`
  📊 Status: Active
  🌍 Timezone: America/Los_Angeles
  🔗 [View Profile](https://your-domain.atlassian.net/secure/ViewProfile.jspa?name=5d1234567890abcdef123457)

**To assign issues to a user:**
• `/jira assign PROJ-123 developer@company.com` - Assign issue to user

**Other Commands:**
• `/jira view PROJ-123` - View issue details
• `/jira create Title` - Create new issue
• `/jira help` - Show all commands
            </div>
        </div>
        
        <div class="test-section">
            <h2>❌ Error Scenarios</h2>
            <p>Test various error conditions:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <div>
                    <h4>Invalid Email Format</h4>
                    <button class="test-button" onclick="testInvalidEmailFormat()">Test Invalid Format</button>
                    <p>Should show email validation error</p>
                </div>
                
                <div>
                    <h4>User Not Found</h4>
                    <button class="test-button" onclick="testUserNotFound()">Test Not Found</button>
                    <p>Should show "no users found" message</p>
                </div>
                
                <div>
                    <h4>Missing Email</h4>
                    <button class="test-button" onclick="testMissingEmail()">Test Missing Email</button>
                    <p>Should show usage instructions</p>
                </div>
                
                <div>
                    <h4>API Error</h4>
                    <button class="test-button" onclick="testAPIError()">Test API Error</button>
                    <p>Should show API error message</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testFindUser() {
            const email = document.getElementById('email-input').value.trim();
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            const formData = new FormData();
            formData.append('token', '<?php echo esc_js($webhook_token); ?>');
            formData.append('channel_id', 'test-channel-123');
            formData.append('channel_name', 'test-channel');
            formData.append('text', 'find ' + email);
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
        
        function testWithCurrentEmail() {
            document.getElementById('email-input').value = '<?php echo esc_js($api_user_email); ?>';
            testFindUser();
        }
        
        function testInvalidEmail() {
            document.getElementById('email-input').value = 'invalid-email';
            testFindUser();
        }
        
        function testEmptyEmail() {
            document.getElementById('email-input').value = '';
            testFindUser();
        }
        
        function testJiraUserAPI() {
            const email = document.getElementById('api-email-input').value.trim();
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            const loading = document.getElementById('api-loading');
            const result = document.getElementById('api-result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            fetch('<?php echo esc_url(rest_url('jira/mattermost/slash/test-jira-user-api')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.success) {
                    result.innerHTML = '<span class="success">✅ Jira User API Test Successful!</span>\n\n' + JSON.stringify(data, null, 2);
                } else {
                    result.innerHTML = '<span class="error">❌ Jira User API Test Failed:</span>\n\n' + JSON.stringify(data, null, 2);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.innerHTML = '<span class="error">❌ Error: ' + error.message + '</span>';
            });
        }
        
        function testInvalidEmailFormat() {
            document.getElementById('email-input').value = 'not-an-email';
            testFindUser();
        }
        
        function testUserNotFound() {
            document.getElementById('email-input').value = 'nonexistent@company.com';
            testFindUser();
        }
        
        function testMissingEmail() {
            document.getElementById('email-input').value = '';
            testFindUser();
        }
        
        function testAPIError() {
            // This would require a test endpoint that simulates API errors
            alert('API error testing would require additional test endpoints');
        }
        
        function clearResults() {
            document.getElementById('result').style.display = 'none';
            document.getElementById('api-result').style.display = 'none';
        }
        
        // Allow Enter key to trigger search
        document.getElementById('email-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testFindUser();
            }
        });
        
        document.getElementById('api-email-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testJiraUserAPI();
            }
        });
    </script>
</body>
</html> 