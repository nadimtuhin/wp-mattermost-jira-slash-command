<?php
/**
 * Test Task Type Functionality
 * 
 * This test file verifies that the new task type feature works correctly
 * in the slash command.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. You must be logged in as an administrator to run this test.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Type Functionality Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .curl-command {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        .button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .button:hover {
            background: #2980b9;
        }
        .button.danger {
            background: #e74c3c;
        }
        .button.danger:hover {
            background: #c0392b;
        }
        .button.success {
            background: #27ae60;
        }
        .button.success:hover {
            background: #229954;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Task Type Functionality Test</h1>
        <p>This test verifies that the new task type feature works correctly in the slash command.</p>

        <?php
        // Get configuration
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        $webhook_token = get_option('wp_mm_slash_jira_webhook_token');
        $api_key = get_option('wp_mm_slash_jira_api_key');
        $enable_logging = get_option('wp_mm_slash_jira_enable_logging');
        ?>

        <div class="test-section">
            <h2>📋 Configuration Check</h2>
            <div class="test-result info">
                <strong>Jira Domain:</strong> <?php echo $jira_domain ? $jira_domain : 'Not configured'; ?><br>
                <strong>Webhook Token:</strong> <?php echo $webhook_token ? 'Configured' : 'Not configured'; ?><br>
                <strong>API Key:</strong> <?php echo $api_key ? 'Configured' : 'Not configured'; ?><br>
                <strong>Logging Enabled:</strong> <?php echo $enable_logging ? 'Yes' : 'No'; ?>
            </div>
        </div>

        <div class="test-section">
            <h2>🎯 Task Type Test Commands</h2>
            <p>Test the new task type functionality with these curl commands:</p>

            <h3>1. Test Basic Task Type</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Bug:Fix login issue" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>2. Test Shortcut Commands</h3>
            <div class="curl-command">
# Test bug shortcut
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=bug Fix login issue" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"

# Test task shortcut
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=task Update documentation" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"

# Test story shortcut
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=story Add new feature" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>3. Test Task Type with Project</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create PROJ Story:Add new feature" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>4. Test Shortcut with Project</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=bug PROJ Fix login issue" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>5. Test Different Task Types</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Task:Update documentation" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>6. Test Epic Type</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Epic:Major system overhaul" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>7. Test Improvement Type</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Improvement:Enhance user interface" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>
        </div>

        <div class="test-section">
            <h2>📝 Expected Behavior</h2>
            <div class="test-result info">
                <strong>Valid Task Types:</strong> Task, Bug, Story, Epic, Subtask, Improvement, New Feature<br><br>
                <strong>Command Format:</strong> /jira create [PROJECT-KEY] [TYPE:]Title<br><br>
                <strong>Shortcut Commands:</strong><br>
                • /jira bug Title<br>
                • /jira task Title<br>
                • /jira story Title<br>
                • /jira bug PROJECT-KEY Title<br>
                • /jira task PROJECT-KEY Title<br>
                • /jira story PROJECT-KEY Title<br><br>
                <strong>View Command:</strong><br>
                • /jira view ISSUE-KEY<br><br>
                <strong>Examples:</strong><br>
                • /jira create Bug:Fix login issue<br>
                • /jira bug Fix login issue<br>
                • /jira create PROJ Story:Add new feature<br>
                • /jira story PROJ Add new feature<br>
                • /jira create Task:Update documentation<br>
                • /jira task Update documentation<br>
                • /jira view PROJ-123<br>
                • /jira create Epic:Major system overhaul<br><br>
                <strong>View Command Response:</strong> Should display detailed issue information including status, description, comments, story points, etc.<br><br>
                <strong>Fallback:</strong> If no type specified, uses channel name detection or defaults to 'Task'
            </div>
        </div>

        <div class="test-section">
            <h2>🔍 Validation Tests</h2>
            <p>Test edge cases and validation:</p>

            <h3>1. Test Invalid Task Type</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create InvalidType:This should not work" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>2. Test Empty Title After Type</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Bug:" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>3. Test Help Command</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=help" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>
        </div>

        <div class="test-section">
            <h2>📊 Test Results</h2>
            <p>Run the commands above and check the responses. Expected results:</p>
            
            <div class="test-result success">
                <strong>✅ Success Case (Issue Creation):</strong><br>
                Response should include the issue type:<br>
                "✅ Issue created successfully!<br>
                **Issue:** PROJ-123<br>
                **Title:** Fix login issue<br>
                **Created by:** @username<br>
                **Project:** PROJ<br>
                <strong>**Type:** Bug</strong><br>
                [View in Jira](...)"
            </div>

            <div class="test-result success">
                <strong>✅ Success Case (View Issue):</strong><br>
                Response should display detailed issue information:<br>
                "📋 **Issue Details: PROJ-123**<br><br>
                **Summary:** Fix login issue<br>
                **Type:** Bug<br>
                **Status:** In Progress<br>
                **Priority:** High<br>
                **Assignee:** John Doe<br>
                **Reporter:** Jane Smith<br>
                **Story Points:** 5<br>
                **Labels:** frontend, critical<br>
                **Components:** Authentication<br><br>
                **Description:**<br>
                The login functionality is broken...<br><br>
                **Comments (3):**<br>
                • **John Doe** (Dec 15, 2023 2:30 PM): Working on this...<br>
                • **Jane Smith** (Dec 15, 2023 1:45 PM): Please prioritize...<br><br>
                [View in Jira](...)"
            </div>

            <div class="test-result error">
                <strong>❌ Error Case (Invalid Type):</strong><br>
                Should treat "InvalidType:Title" as regular title, not as a type
            </div>

            <div class="test-result error">
                <strong>❌ Error Case (Empty Title):</strong><br>
                Should show error: "Please provide a title for the issue"
            </div>

            <div class="test-result error">
                <strong>❌ Error Case (Invalid Issue Key):</strong><br>
                Should show error: "Invalid issue key format. Please use format: PROJECT-123"
            </div>

            <div class="test-result error">
                <strong>❌ Error Case (Issue Not Found):</strong><br>
                Should show error: "Failed to get issue details: Issue not found"
            </div>
        </div>

        <div class="test-section">
            <h2>👁️ View Issue Details Test Commands</h2>
            <p>Test the new view command functionality with these curl commands:</p>

            <h3>1. Test View Issue Details</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=view PROJ-123" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>2. Test View Bug Issue</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=view BUG-456" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>3. Test View Story Issue</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=view STORY-789" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>

            <h3>4. Test View Invalid Issue Key</h3>
            <div class="curl-command">
curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=view INVALID-123" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"
            </div>
        </div>

        <div class="test-section">
            <h2>🔗 Quick Links</h2>
            <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'test-interface.php'); ?>" class="button">🧪 Test Interface</a>
            <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'test-curl-commands.php'); ?>" class="button">🔧 Curl Commands</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-mm-slash-jira')); ?>" class="button">⚙️ Plugin Settings</a>
            <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../README.md'); ?>" class="button">📖 Documentation</a>
        </div>

        <div class="test-section">
            <h2>📝 Notes</h2>
            <div class="test-result info">
                <strong>Implementation Details:</strong><br>
                • Task type parsing happens in the create_jira_issue() method<br>
                • Valid types are: Task, Bug, Story, Epic, Subtask, Improvement, New Feature<br>
                • Format: TYPE:Title (case-sensitive)<br>
                • If no type specified, falls back to channel-based detection<br>
                • Response includes the issue type when specified<br><br>
                
                <strong>Testing Tips:</strong><br>
                • Use the web-based test interface for interactive testing<br>
                • Check the logs table for detailed request/response data<br>
                • Verify Jira API credentials are working<br>
                • Test with different project keys and channel mappings
            </div>
        </div>
    </div>
</body>
</html> 