<?php
/**
 * Test Interface for Mattermost Jira Slash Commands
 * 
 * This page provides a web-based interface to test slash commands
 * similar to how they would work in Mattermost.
 */

// Load WordPress
require_once('../../../../wp-config.php');

// Check if user is logged in and has admin permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

// Get plugin settings
$jira_domain = get_option('wp_mm_slash_jira_jira_domain');
$webhook_token = get_option('wp_mm_slash_jira_webhook_token');
$enable_logging = get_option('wp_mm_slash_jira_enable_logging');

// Get channel mappings for dropdown
global $wpdb;
$table_name = $wpdb->prefix . 'mm_jira_mappings';
$mappings = $wpdb->get_results("SELECT * FROM $table_name ORDER BY channel_name ASC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mattermost Jira Slash Command Tester</title>
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
        
        .status-indicators {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .status-ok {
            background: #e3fcef;
            color: #006644;
        }
        
        .status-warning {
            background: #fff7e6;
            color: #974f0c;
        }
        
        .status-error {
            background: #ffebe6;
            color: #de350b;
        }
        
        .chat-interface {
            display: flex;
            gap: 20px;
            height: 600px;
        }
        
        .chat-sidebar {
            width: 300px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .chat-main {
            flex: 1;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e1e5e9;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        
        .chat-header h3 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fff;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            max-width: 80%;
        }
        
        .message.user {
            background: #e3fcef;
            margin-left: auto;
            text-align: right;
        }
        
        .message.bot {
            background: #f4f5f7;
            margin-right: auto;
        }
        
        .message-time {
            font-size: 12px;
            color: #6b778c;
            margin-top: 5px;
        }
        
        .message.user .message-time {
            text-align: right;
        }
        
        .chat-input {
            padding: 20px;
            border-top: 1px solid #e1e5e9;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
        }
        
        .command-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }
        
        .command-input:focus {
            outline: none;
            border-color: #0052cc;
        }
        
        .send-button {
            padding: 12px 24px;
            background: #0052cc;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .send-button:hover {
            background: #0047b3;
        }
        
        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .sidebar-section {
            margin-bottom: 25px;
        }
        
        .sidebar-section h4 {
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            color: #6b778c;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0052cc;
        }
        
        .quick-commands {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .quick-command {
            padding: 8px 12px;
            background: #f4f5f7;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .quick-command:hover {
            background: #e1e5e9;
            border-color: #0052cc;
        }
        
        .mappings-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .mapping-item {
            padding: 8px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .mapping-channel {
            font-weight: 600;
            color: #333;
        }
        
        .mapping-project {
            color: #0052cc;
            font-family: monospace;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #6b778c;
        }
        
        .response-details {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .response-toggle {
            background: none;
            border: none;
            color: #0052cc;
            cursor: pointer;
            font-size: 12px;
            text-decoration: underline;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .chat-interface {
                flex-direction: column;
            }
            
            .chat-sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Mattermost Jira Slash Command Tester</h1>
            <p>Test your Jira integration commands in a Mattermost-like interface</p>
            
            <div class="status-indicators">
                <div class="status-item <?php echo $jira_domain ? 'status-ok' : 'status-error'; ?>">
                    <span>🔗</span>
                    <span>Jira Domain: <?php echo $jira_domain ? $jira_domain : 'Not configured'; ?></span>
                </div>
                <div class="status-item <?php echo $webhook_token ? 'status-ok' : 'status-error'; ?>">
                    <span>🔐</span>
                    <span>Webhook Token: <?php echo $webhook_token ? 'Configured' : 'Not configured'; ?></span>
                </div>
                <div class="status-item <?php echo $enable_logging ? 'status-ok' : 'status-warning'; ?>">
                    <span>📝</span>
                    <span>Logging: <?php echo $enable_logging ? 'Enabled' : 'Disabled'; ?></span>
                </div>
                <div class="status-item status-ok">
                    <span>👤</span>
                    <span>User: <?php echo wp_get_current_user()->user_login; ?></span>
                </div>
            </div>
        </div>
        
        <div class="chat-interface">
            <div class="chat-sidebar">
                <div class="sidebar-section">
                    <h4>Channel Settings</h4>
                    <div class="form-group">
                        <label for="channel-id">Channel ID</label>
                        <input type="text" id="channel-id" value="fukxanjgjbnp7ng383at53k1sy" placeholder="Enter channel ID">
                    </div>
                    <div class="form-group">
                        <label for="channel-name">Channel Name</label>
                        <input type="text" id="channel-name" value="general" placeholder="Enter channel name">
                    </div>
                    <div class="form-group">
                        <label for="user-name">Username</label>
                        <input type="text" id="user-name" value="<?php echo wp_get_current_user()->user_login; ?>" placeholder="Enter username">
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h4>Quick Commands</h4>
                    <div class="quick-commands">
                        <div class="quick-command" data-command="create Fix login bug">create Fix login bug</div>
                        <div class="quick-command" data-command="create PROJ-123 Add new feature">create PROJ-123 Add new feature</div>
                        <div class="quick-command" data-command="assign PROJ-123 developer@company.com">assign PROJ-123 developer@company.com</div>
                        <div class="quick-command" data-command="bind PROJ">bind PROJ</div>
                        <div class="quick-command" data-command="status">status</div>
                        <div class="quick-command" data-command="link">link</div>
                        <div class="quick-command" data-command="board">board</div>
                        <div class="quick-command" data-command="help">help</div>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h4>Channel Mappings</h4>
                    <?php if (empty($mappings)): ?>
                        <p style="font-size: 12px; color: #6b778c;">No mappings found. Add mappings in the admin interface.</p>
                    <?php else: ?>
                        <div class="mappings-list">
                            <?php foreach ($mappings as $mapping): ?>
                                <div class="mapping-item">
                                    <div class="mapping-channel">#<?php echo esc_html($mapping->channel_name); ?></div>
                                    <div class="mapping-project"><?php echo esc_html($mapping->jira_project_key); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="chat-main">
                <div class="chat-header">
                    <h3>#<span id="current-channel">general</span></h3>
                    <p>Type <code>/jira</code> followed by your command, or use the quick commands on the left</p>
                </div>
                
                <div class="chat-messages" id="chat-messages">
                    <div class="message bot">
                        <div>👋 Welcome to the Jira Slash Command Tester!</div>
                        <div>Try typing a command like <code>/jira create Fix login bug</code> or use the quick commands on the left.</div>
                        <div class="message-time"><?php echo date('H:i'); ?></div>
                    </div>
                </div>
                
                <div class="loading" id="loading">
                    <div>⏳ Processing command...</div>
                </div>
                
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" class="command-input" id="command-input" placeholder="/jira create Fix login bug" autocomplete="off">
                        <button class="send-button" id="send-button">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const commandInput = document.getElementById('command-input');
            const sendButton = document.getElementById('send-button');
            const chatMessages = document.getElementById('chat-messages');
            const loading = document.getElementById('loading');
            const currentChannel = document.getElementById('current-channel');
            
            // Update channel name when changed
            document.getElementById('channel-name').addEventListener('input', function() {
                currentChannel.textContent = this.value || 'general';
            });
            
            // Quick command buttons
            document.querySelectorAll('.quick-command').forEach(function(button) {
                button.addEventListener('click', function() {
                    commandInput.value = this.dataset.command;
                    commandInput.focus();
                });
            });
            
            // Send command function
            function sendCommand() {
                const command = commandInput.value.trim();
                if (!command) return;
                
                // Add user message
                addMessage(command, 'user');
                
                // Show loading
                loading.style.display = 'block';
                sendButton.disabled = true;
                
                // Get form data
                const formData = new FormData();
                formData.append('token', '<?php echo esc_js($webhook_token); ?>');
                formData.append('channel_id', document.getElementById('channel-id').value);
                formData.append('channel_name', document.getElementById('channel-name').value);
                formData.append('text', command);
                formData.append('user_name', document.getElementById('user-name').value);
                
                // Send request
                fetch('<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide loading
                    loading.style.display = 'none';
                    sendButton.disabled = false;
                    
                    // Add bot response
                    let responseText = data.text || 'No response received';
                    let responseType = data.response_type || 'ephemeral';
                    
                    addMessage(responseText, 'bot', data);
                    
                    // Clear input
                    commandInput.value = '';
                })
                .catch(error => {
                    // Hide loading
                    loading.style.display = 'none';
                    sendButton.disabled = false;
                    
                    // Add error message
                    addMessage('❌ Error: ' + error.message, 'bot');
                    
                    console.error('Error:', error);
                });
            }
            
            // Add message to chat
            function addMessage(text, type, responseData = null) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${type}`;
                
                // Convert markdown-like formatting
                let formattedText = text
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/`(.*?)`/g, '<code>$1</code>')
                    .replace(/\n/g, '<br>');
                
                messageDiv.innerHTML = formattedText;
                
                // Add timestamp
                const timeDiv = document.createElement('div');
                timeDiv.className = 'message-time';
                timeDiv.textContent = new Date().toLocaleTimeString();
                messageDiv.appendChild(timeDiv);
                
                // Add response details if available
                if (responseData && type === 'bot') {
                    const detailsButton = document.createElement('button');
                    detailsButton.className = 'response-toggle';
                    detailsButton.textContent = 'Show Response Details';
                    detailsButton.onclick = function() {
                        const details = this.nextElementSibling;
                        if (details.style.display === 'none' || !details.style.display) {
                            details.style.display = 'block';
                            this.textContent = 'Hide Response Details';
                        } else {
                            details.style.display = 'none';
                            this.textContent = 'Show Response Details';
                        }
                    };
                    messageDiv.appendChild(detailsButton);
                    
                    const detailsDiv = document.createElement('div');
                    detailsDiv.className = 'response-details';
                    detailsDiv.style.display = 'none';
                    detailsDiv.textContent = JSON.stringify(responseData, null, 2);
                    messageDiv.appendChild(detailsDiv);
                }
                
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Send on button click
            sendButton.addEventListener('click', sendCommand);
            
            // Send on Enter key
            commandInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendCommand();
                }
            });
            
            // Auto-focus input
            commandInput.focus();
        });
    </script>
</body>
</html> 