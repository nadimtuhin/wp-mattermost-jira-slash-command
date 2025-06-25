<?php
/**
 * Test Email Domain Feature
 * 
 * This page tests the email domain feature and automatic reporter assignment
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
$email_domain = get_option('wp_mm_slash_jira_email_domain');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Domain Feature - WP Mattermost Jira Integration</title>
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
        
        .input-field {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #0052cc;
        }
        
        .workflow-diagram {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            padding: 20px;
            margin: 15px 0;
        }
        
        .workflow-step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #fff;
            border-radius: 4px;
            border-left: 4px solid #0052cc;
        }
        
        .workflow-step:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            background: #0052cc;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .step-description {
            color: #6b778c;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Test Email Domain Feature</h1>
            <p>Test the automatic reporter assignment feature using email domain configuration</p>
            
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
                <div class="status-item <?php echo $email_domain ? 'status-ok' : 'status-warning'; ?>">
                    <strong>Email Domain:</strong><br>
                    <?php echo $email_domain ? $email_domain : 'Not configured'; ?>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>📧 Email Domain Feature Overview</h2>
            <p>The Email Domain feature automatically assigns reporters to Jira issues based on the Mattermost username:</p>
            
            <div class="workflow-diagram">
                <div class="workflow-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">User Creates Issue</div>
                        <div class="step-description">User "john" runs `/jira create Fix login bug`</div>
                    </div>
                </div>
                
                <div class="workflow-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Email Domain Lookup</div>
                        <div class="step-description">Plugin constructs email: "john@company.com"</div>
                    </div>
                </div>
                
                <div class="workflow-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">Jira User Search</div>
                        <div class="step-description">Plugin searches Jira for user with that email</div>
                    </div>
                </div>
                
                <div class="workflow-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <div class="step-title">Issue Creation</div>
                        <div class="step-description">Issue created with found user as reporter</div>
                    </div>
                </div>
                
                <div class="workflow-step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <div class="step-title">Response</div>
                        <div class="step-description">Response shows reporter information</div>
                    </div>
                </div>
            </div>
            
            <ul class="feature-list">
                <li><strong>Automatic Reporter Assignment:</strong> Sets reporter based on username + email domain</li>
                <li><strong>Jira User Search:</strong> Uses Jira API to find users by email</li>
                <li><strong>Graceful Fallback:</strong> Creates issue normally if user not found</li>
                <li><strong>Response Enhancement:</strong> Shows reporter information in response</li>
                <li><strong>Account ID Usage:</strong> Uses Jira account ID for proper assignment</li>
                <li><strong>Error Handling:</strong> Handles API errors gracefully</li>
                <li><strong>Logging:</strong> Logs all user search attempts</li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>🚀 Test Issue Creation with Reporter Assignment</h2>
            <p>Test creating issues with automatic reporter assignment:</p>
            
            <div class="test-form">
                <div class="form-group">
                    <label for="username-input">Username:</label>
                    <input type="text" id="username-input" class="input-field" placeholder="john" value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>">
                </div>
                <div class="form-group">
                    <label for="title-input">Issue Title:</label>
                    <input type="text" id="title-input" class="input-field" placeholder="Fix login bug" value="Test issue with reporter assignment">
                </div>
                <button class="test-button" onclick="testIssueCreation()">Test Issue Creation</button>
            </div>
            
            <div style="margin-bottom: 15px;">
                <button class="test-button" onclick="testWithCurrentUser()">Test with Current User</button>
                <button class="test-button" onclick="testWithDifferentUser()">Test with Different User</button>
                <button class="test-button" onclick="testWithoutEmailDomain()">Test without Email Domain</button>
                <button class="test-button" onclick="clearResults()">Clear Results</button>
            </div>
            
            <div id="loading" class="loading" style="display: none;">
                ⏳ Creating issue with reporter assignment...
            </div>
            
            <div id="result" class="result-area" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h2>🔧 Manual Testing with cURL</h2>
            <p>You can also test the feature manually using cURL:</p>
            
            <div class="curl-example">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=test-channel-123" \
  -d "channel_name=test-channel" \
  -d "text=create Test issue with reporter" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>
            
            <p><strong>Expected Response (with Email Domain configured):</strong></p>
            <div class="curl-example">
✅ Issue created successfully!

**Issue:** PROJ-123
**Title:** Test issue with reporter
**Created by:** @john
**Project:** PROJ
**Type:** Task
**Reporter:** John Developer (john@company.com)

[View in Jira](https://your-domain.atlassian.net/browse/PROJ-123)
            </div>
            
            <p><strong>Expected Response (without Email Domain):</strong></p>
            <div class="curl-example">
✅ Issue created successfully!

**Issue:** PROJ-123
**Title:** Test issue with reporter
**Created by:** @john
**Project:** PROJ
**Type:** Task

[View in Jira](https://your-domain.atlassian.net/browse/PROJ-123)
            </div>
        </div>
        
        <div class="test-section">
            <h2>🔍 Test User Search API</h2>
            <p>Test the user search functionality directly:</p>
            
            <div class="test-form">
                <div class="form-group">
                    <label for="search-username">Username:</label>
                    <input type="text" id="search-username" class="input-field" placeholder="john" value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>">
                </div>
                <button class="test-button" onclick="testUserSearch()">Test User Search</button>
            </div>
            
            <div id="search-loading" class="loading" style="display: none;">
                ⏳ Searching for user...
            </div>
            
            <div id="search-result" class="result-area" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h2>📊 Configuration Status</h2>
            <p>Current configuration status for the email domain feature:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <div class="status-item <?php echo $email_domain ? 'status-ok' : 'status-warning'; ?>">
                    <h4>Email Domain Setting</h4>
                    <p><strong>Status:</strong> <?php echo $email_domain ? 'Configured' : 'Not configured'; ?></p>
                    <p><strong>Value:</strong> <?php echo $email_domain ? $email_domain : 'None'; ?></p>
                    <?php if ($email_domain): ?>
                        <p><strong>Test Email:</strong> <?php echo esc_attr(wp_get_current_user()->user_login); ?>@<?php echo $email_domain; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="status-item <?php echo $jira_domain && $api_key ? 'status-ok' : 'status-error'; ?>">
                    <h4>Jira API Access</h4>
                    <p><strong>Status:</strong> <?php echo $jira_domain && $api_key ? 'Available' : 'Not available'; ?></p>
                    <p><strong>Domain:</strong> <?php echo $jira_domain ? $jira_domain : 'Not set'; ?></p>
                    <p><strong>API Key:</strong> <?php echo $api_key ? 'Configured' : 'Not configured'; ?></p>
                </div>
                
                <div class="status-item <?php echo $webhook_token ? 'status-ok' : 'status-error'; ?>">
                    <h4>Webhook Configuration</h4>
                    <p><strong>Status:</strong> <?php echo $webhook_token ? 'Configured' : 'Not configured'; ?></p>
                    <p><strong>Token:</strong> <?php echo $webhook_token ? 'Set' : 'Not set'; ?></p>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>❌ Error Scenarios</h2>
            <p>Test various error conditions:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <div>
                    <h4>User Not Found in Jira</h4>
                    <button class="test-button" onclick="testUserNotFound()">Test User Not Found</button>
                    <p>Should create issue without reporter assignment</p>
                </div>
                
                <div>
                    <h4>Invalid Email Domain</h4>
                    <button class="test-button" onclick="testInvalidEmailDomain()">Test Invalid Domain</button>
                    <p>Should handle gracefully</p>
                </div>
                
                <div>
                    <h4>API Error</h4>
                    <button class="test-button" onclick="testAPIError()">Test API Error</button>
                    <p>Should fall back to normal issue creation</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testIssueCreation() {
            const username = document.getElementById('username-input').value.trim();
            const title = document.getElementById('title-input').value.trim();
            
            if (!username || !title) {
                alert('Please enter both username and title');
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
            formData.append('text', 'create ' + title);
            formData.append('user_name', username);
            
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
        
        function testWithCurrentUser() {
            document.getElementById('username-input').value = '<?php echo esc_js(wp_get_current_user()->user_login); ?>';
            testIssueCreation();
        }
        
        function testWithDifferentUser() {
            document.getElementById('username-input').value = 'testuser';
            testIssueCreation();
        }
        
        function testWithoutEmailDomain() {
            // This would require temporarily disabling the email domain setting
            alert('This test would require temporarily disabling the email domain setting in the admin interface');
        }
        
        function testUserSearch() {
            const username = document.getElementById('search-username').value.trim();
            if (!username) {
                alert('Please enter a username');
                return;
            }
            
            const loading = document.getElementById('search-loading');
            const result = document.getElementById('search-result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            const formData = new FormData();
            formData.append('token', '<?php echo esc_js($webhook_token); ?>');
            formData.append('channel_id', 'test-channel-123');
            formData.append('channel_name', 'test-channel');
            formData.append('text', 'find ' + username + '@<?php echo esc_js($email_domain); ?>');
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
                    result.innerHTML = '<span class="success">✅ User Search Result:</span>\n\n' + data.text;
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
        
        function testUserNotFound() {
            document.getElementById('username-input').value = 'nonexistentuser';
            testIssueCreation();
        }
        
        function testInvalidEmailDomain() {
            alert('This test would require temporarily setting an invalid email domain in the admin interface');
        }
        
        function testAPIError() {
            alert('This test would require temporarily breaking the Jira API configuration');
        }
        
        function clearResults() {
            document.getElementById('result').style.display = 'none';
            document.getElementById('search-result').style.display = 'none';
        }
        
        // Allow Enter key to trigger tests
        document.getElementById('username-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testIssueCreation();
            }
        });
        
        document.getElementById('title-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testIssueCreation();
            }
        });
        
        document.getElementById('search-username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testUserSearch();
            }
        });
    </script>
</body>
</html> 