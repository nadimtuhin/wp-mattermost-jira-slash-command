<?php
/**
 * API handler class for Jira integration
 */
class WP_MM_Slash_Jira_API {
    
    private $logger;
    
    /**
     * Check if current user has admin permissions using multiple methods
     */
    public function check_admin_permissions() {
        // Method 1: Check if user is logged in and has admin role
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if (in_array('administrator', $current_user->roles)) {
                return true;
            }
        }
        
        // Method 2: Check manage_options capability
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Method 3: Check if user is super admin
        if (is_super_admin()) {
            return true;
        }
        
        return false;
    }
    
    public function init() {
        $this->logger = new WP_MM_Slash_Jira_Logger();
    }
    
    /**
     * Verify the webhook token
     */
    public function verify_webhook($request) {
        $token = $request->get_param('token');
        $expected_token = get_option('wp_mm_slash_jira_webhook_token');
        
        if (empty($expected_token)) {
            return false;
        }
        
        return $token === $expected_token;
    }
    
    /**
     * Handle the slash command from Mattermost
     */
    public function handle_slash_command($request) {
        $start_time = microtime(true);
        
        $channel_id = $request->get_param('channel_id');
        $channel_name = $request->get_param('channel_name');
        $text = $request->get_param('text');
        $user_name = $request->get_param('user_name');
        
        // Capture all request parameters for logging
        $request_payload = $request->get_params();
        
        // Parse the command text
        $parts = explode(' ', trim($text));
        $command = strtolower($parts[0]);
        
        $response = null;
        $status = 'success';
        $error_message = null;
        
        try {
            switch ($command) {
                case 'create':
                    $response = $this->create_jira_issue($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'assign':
                    $response = $this->assign_jira_issue($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'bind':
                    $response = $this->bind_channel_project($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'status':
                    $response = $this->check_channel_status($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'link':
                    $response = $this->get_jira_links($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'board':
                    $response = $this->get_jira_board_links($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'help':
                    $response = $this->show_help();
                    break;
                default:
                    $response = $this->show_help();
                    break;
            }
        } catch (Exception $e) {
            $status = 'error';
            $error_message = $e->getMessage();
            $response = array(
                'response_type' => 'ephemeral',
                'text' => "❌ An error occurred: " . $e->getMessage()
            );
        }
        
        $execution_time = microtime(true) - $start_time;
        
        // Log the invocation
        $this->logger->log_invocation(
            $channel_id,
            $channel_name,
            $user_name,
            $text,
            $request_payload,
            $response,
            200, // HTTP response code
            $execution_time,
            $status,
            $error_message
        );
        
        return $response;
    }
    
    /**
     * Create a Jira issue
     */
    private function create_jira_issue($channel_id, $channel_name, $text, $user_name) {
        global $wpdb;
        
        // Get project key from channel mapping or from command
        $parts = explode(' ', trim($text));
        $project_key = null;
        
        // Check if project key is provided in command (e.g., "create PROJ-123 title")
        if (count($parts) >= 2 && preg_match('/^[A-Z]+-\d+$/', $parts[1])) {
            $project_key = explode('-', $parts[1])[0];
            $issue_key = $parts[1];
            $title = implode(' ', array_slice($parts, 2));
        } else {
            // Get project key from channel mapping
            $table_name = $wpdb->prefix . 'mm_jira_mappings';
            $mapping = $wpdb->get_row($wpdb->prepare(
                "SELECT jira_project_key FROM $table_name WHERE channel_id = %s",
                $channel_id
            ));
            
            if (!$mapping) {
                return array(
                    'response_type' => 'ephemeral',
                    'text' => "❌ No Jira project mapped to this channel. Please contact an administrator to set up the mapping or specify a project key in your command (e.g., `/jira create PROJ-123 Task title`)."
                );
            }
            
            $project_key = $mapping->jira_project_key;
            $title = implode(' ', array_slice($parts, 1));
        }
        
        if (empty($title)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Please provide a title for the issue. Usage: `/jira create [PROJECT-KEY-ISSUE-NUMBER] Title` or `/jira create Title`"
            );
        }
        
        // Create the issue in Jira
        $result = $this->create_issue_in_jira($project_key, $title, $user_name, $channel_name);
        
        if ($result['success']) {
            return array(
                'response_type' => 'in_channel',
                'text' => "✅ Issue created successfully!\n\n**Issue:** {$result['issue_key']}\n**Title:** {$title}\n**Created by:** @{$user_name}\n**Project:** {$project_key}\n\n[View in Jira]({$result['url']})"
            );
        } else {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Failed to create issue: {$result['error']}"
            );
        }
    }
    
    /**
     * Assign a Jira issue to a user by email
     */
    private function assign_jira_issue($channel_id, $channel_name, $text, $user_name) {
        $parts = explode(' ', trim($text));
        
        // Check if we have enough parameters: assign ISSUE-KEY email@example.com
        if (count($parts) < 3) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Please provide an issue key and email address. Usage: `/jira assign PROJ-123 user@example.com`"
            );
        }
        
        $issue_key = $parts[1];
        $email = $parts[2];
        
        // Validate issue key format
        if (!preg_match('/^[A-Z]+-\d+$/', $issue_key)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Invalid issue key format. Please use format: PROJECT-123"
            );
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Invalid email format. Please provide a valid email address."
            );
        }
        
        // Assign the issue in Jira
        $result = $this->assign_issue_in_jira($issue_key, $email, $user_name);
        
        if ($result['success']) {
            return array(
                'response_type' => 'in_channel',
                'text' => "✅ Issue assigned successfully!\n\n**Issue:** {$issue_key}\n**Assigned to:** {$email}\n**Assigned by:** @{$user_name}\n\n[View in Jira]({$result['url']})"
            );
        } else {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Failed to assign issue: {$result['error']}"
            );
        }
    }
    
    /**
     * Assign issue in Jira via API
     */
    private function assign_issue_in_jira($issue_key, $email, $user_name) {
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        $api_key = get_option('wp_mm_slash_jira_api_key');
        
        if (empty($jira_domain) || empty($api_key)) {
            return array('success' => false, 'error' => 'Jira configuration not set up');
        }
        
        // Clean and validate the Jira domain
        $jira_domain = $this->clean_jira_domain($jira_domain);
        
        if (empty($jira_domain)) {
            return array('success' => false, 'error' => 'Invalid Jira domain format. Please use format: your-domain.atlassian.net');
        }
        
        // First, try to find the user account ID by email
        $account_id = $this->get_user_account_id_by_email($email);
        
        if (!$account_id) {
            return array('success' => false, 'error' => "User with email {$email} not found in Jira");
        }
        
        $data = array(
            'accountId' => $account_id
        );
        
        $url = "https://{$jira_domain}/rest/api/2/issue/{$issue_key}/assignee";
        
        $response = wp_remote_put($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 204) {
            return array(
                'success' => true,
                'url' => "https://{$jira_domain}/browse/{$issue_key}"
            );
        } else {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            $error = isset($result['errorMessages']) ? implode(', ', $result['errorMessages']) : 'Unknown error';
            return array('success' => false, 'error' => $error);
        }
    }
    
    /**
     * Get user account ID by email
     */
    private function get_user_account_id_by_email($email) {
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        $api_key = get_option('wp_mm_slash_jira_api_key');
        
        // Clean and validate the Jira domain
        $jira_domain = $this->clean_jira_domain($jira_domain);
        
        if (empty($jira_domain)) {
            return false;
        }
        
        $url = "https://{$jira_domain}/rest/api/2/user/search?query=" . urlencode($email);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) === 200 && is_array($result) && !empty($result)) {
            // Find the user with matching email
            foreach ($result as $user) {
                if (isset($user['emailAddress']) && strtolower($user['emailAddress']) === strtolower($email)) {
                    return $user['accountId'];
                }
            }
        }
        
        return false;
    }
    
    /**
     * Bind a channel to a Jira project key
     */
    private function bind_channel_project($channel_id, $channel_name, $text, $user_name) {
        global $wpdb;
        
        $parts = explode(' ', trim($text));
        
        // Check if we have enough parameters: bind PROJECT-KEY
        if (count($parts) < 2) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Please provide a project key. Usage: `/jira bind PROJECT-KEY`\n\n**Examples:**\n• `/jira bind PROJ`\n• `/jira bind DEV`\n• `/jira bind BUG`"
            );
        }
        
        $project_key = strtoupper(trim($parts[1]));
        
        // Validate project key format (basic validation)
        if (!preg_match('/^[A-Z0-9]+$/', $project_key)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Invalid project key format. Project keys should contain only uppercase letters and numbers (e.g., PROJ, DEV, BUG123)."
            );
        }
        
        // Check if project key is too long (Jira typically limits to 10 characters)
        if (strlen($project_key) > 10) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Project key is too long. Jira project keys are typically limited to 10 characters."
            );
        }
        
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        
        // Check if mapping already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE channel_id = %s",
            $channel_id
        ));
        
        if ($existing) {
            // Update existing mapping
            $result = $wpdb->update(
                $table_name,
                array(
                    'jira_project_key' => $project_key,
                    'channel_name' => $channel_name
                ),
                array('channel_id' => $channel_id),
                array('%s', '%s'),
                array('%s')
            );
            
            if ($result !== false) {
                return array(
                    'response_type' => 'in_channel',
                    'text' => "✅ Channel mapping updated successfully!\n\n**Channel:** #{$channel_name}\n**Project Key:** {$project_key}\n**Updated by:** @{$user_name}\n\n**Previous mapping:** {$existing->jira_project_key} → **New mapping:** {$project_key}"
                );
            } else {
                return array(
                    'response_type' => 'ephemeral',
                    'text' => "❌ Failed to update channel mapping. Please try again or contact an administrator."
                );
            }
        } else {
            // Create new mapping
            $result = $wpdb->insert(
                $table_name,
                array(
                    'channel_id' => $channel_id,
                    'channel_name' => $channel_name,
                    'jira_project_key' => $project_key
                ),
                array('%s', '%s', '%s')
            );
            
            if ($result !== false) {
                return array(
                    'response_type' => 'in_channel',
                    'text' => "✅ Channel mapping created successfully!\n\n**Channel:** #{$channel_name}\n**Project Key:** {$project_key}\n**Created by:** @{$user_name}\n\nNow you can use `/jira create Title` to create issues in this project."
                );
            } else {
                return array(
                    'response_type' => 'ephemeral',
                    'text' => "❌ Failed to create channel mapping. Please try again or contact an administrator."
                );
            }
        }
    }
    
    /**
     * Check channel binding status
     */
    private function check_channel_status($channel_id, $channel_name, $text, $user_name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        
        // Get current mapping for this channel
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE channel_id = %s",
            $channel_id
        ));
        
        if (!$mapping) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "📋 **Channel Status: #{$channel_name}**\n\n" .
                         "❌ **No project binding found**\n\n" .
                         "This channel is not currently mapped to any Jira project.\n\n" .
                         "**To bind this channel to a project:**\n" .
                         "• `/jira bind PROJECT-KEY` - Bind to a specific project\n\n" .
                         "**To create issues without binding:**\n" .
                         "• `/jira create PROJ-123 Title` - Specify project in command\n\n" .
                         "**Examples:**\n" .
                         "• `/jira bind PROJ` - Bind to PROJ project\n" .
                         "• `/jira create PROJ-456 Fix login bug` - Create with specific project"
            );
        }
        
        // Get additional statistics for this mapping
        $total_issues = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mm_jira_logs WHERE channel_id = %s AND command LIKE 'create%' AND status = 'success'",
            $channel_id
        ));
        
        $recent_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mm_jira_logs WHERE channel_id = %s AND timestamp >= %s",
            $channel_id,
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        $last_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT timestamp FROM {$wpdb->prefix}mm_jira_logs WHERE channel_id = %s ORDER BY timestamp DESC LIMIT 1",
            $channel_id
        ));
        
        $status_text = "📋 **Channel Status: #{$channel_name}**\n\n" .
                      "✅ **Project Binding:** {$mapping->jira_project_key}\n" .
                      "📅 **Bound since:** " . date('M j, Y', strtotime($mapping->created_at)) . "\n\n";
        
        if ($total_issues > 0) {
            $status_text .= "📊 **Statistics:**\n" .
                           "• Total issues created: {$total_issues}\n" .
                           "• Activity (7 days): {$recent_activity} commands\n";
            
            if ($last_activity) {
                $status_text .= "• Last activity: " . date('M j, Y g:i A', strtotime($last_activity)) . "\n";
            }
            $status_text .= "\n";
        }
        
        $status_text .= "**Available commands:**\n" .
                       "• `/jira create Title` - Create issue in {$mapping->jira_project_key}\n" .
                       "• `/jira bind NEWPROJ` - Change to different project\n" .
                       "• `/jira assign PROJ-123 user@example.com` - Assign issue\n" .
                       "• `/jira help` - Show all commands\n\n" .
                       "**Quick examples:**\n" .
                       "• `/jira create Fix login bug`\n" .
                       "• `/jira create {$mapping->jira_project_key}-456 Add new feature`";
        
        return array(
            'response_type' => 'in_channel',
            'text' => $status_text
        );
    }
    
    /**
     * Get Jira links for creating new tasks
     */
    private function get_jira_links($channel_id, $channel_name, $text, $user_name) {
        global $wpdb;
        
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        
        if (empty($jira_domain)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Jira domain not configured. Please contact an administrator."
            );
        }
        
        // Clean and validate the Jira domain
        $jira_domain = $this->clean_jira_domain($jira_domain);
        
        if (empty($jira_domain)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Invalid Jira domain format. Please use format: your-domain.atlassian.net"
            );
        }
        
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT jira_project_key FROM $table_name WHERE channel_id = %s",
            $channel_id
        ));
        
        $links_text = "🔗 **Jira Links for #{$channel_name}**\n\n";
        
        if ($mapping) {
            $project_key = $mapping->jira_project_key;
            $links_text .= "**Current Project:** {$project_key}\n\n";
            $links_text .= "**Quick Links:**\n";
            $links_text .= "• [Create Issue in {$project_key}](https://{$jira_domain}/secure/CreateIssue.jspa?pid={$project_key})\n";
            $links_text .= "• [View {$project_key} Project](https://{$jira_domain}/browse/{$project_key})\n";
            $links_text .= "• [{$project_key} Backlog](https://{$jira_domain}/secure/RapidBoard.jspa?rapidView={$project_key})\n\n";
        } else {
            $links_text .= "❌ **No project binding found**\n\n";
            $links_text .= "**General Jira Links:**\n";
            $links_text .= "• [Create Issue](https://{$jira_domain}/secure/CreateIssue.jspa)\n";
            $links_text .= "• [View All Projects](https://{$jira_domain}/browse)\n";
            $links_text .= "• [Dashboard](https://{$jira_domain}/secure/Dashboard.jspa)\n\n";
            $links_text .= "**To bind this channel to a project:**\n";
            $links_text .= "• `/jira bind PROJECT-KEY` - Then use `/jira link` again\n";
        }
        
        $links_text .= "**Other Commands:**\n";
        $links_text .= "• `/jira create Title` - Create issue via chat\n";
        $links_text .= "• `/jira status` - Check current binding\n";
        $links_text .= "• `/jira board` - Get board links\n";
        
        return array(
            'response_type' => 'in_channel',
            'text' => $links_text
        );
    }
    
    /**
     * Get Jira board links
     */
    private function get_jira_board_links($channel_id, $channel_name, $text, $user_name) {
        global $wpdb;
        
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        
        if (empty($jira_domain)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Jira domain not configured. Please contact an administrator."
            );
        }
        
        // Clean and validate the Jira domain
        $jira_domain = $this->clean_jira_domain($jira_domain);
        
        if (empty($jira_domain)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Invalid Jira domain format. Please use format: your-domain.atlassian.net"
            );
        }
        
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT jira_project_key FROM $table_name WHERE channel_id = %s",
            $channel_id
        ));
        
        $board_text = "📋 **Jira Board Links for #{$channel_name}**\n\n";
        
        if ($mapping) {
            $project_key = $mapping->jira_project_key;
            $board_text .= "**Current Project:** {$project_key}\n\n";
            $board_text .= "**Board Links:**\n";
            $board_text .= "• [{$project_key} Kanban Board](https://{$jira_domain}/secure/RapidBoard.jspa?rapidView={$project_key})\n";
            $board_text .= "• [{$project_key} Scrum Board](https://{$jira_domain}/secure/RapidBoard.jspa?rapidView={$project_key}&view=planning)\n";
            $board_text .= "• [{$project_key} Backlog](https://{$jira_domain}/secure/RapidBoard.jspa?rapidView={$project_key}&view=backlog)\n";
            $board_text .= "• [{$project_key} Active Sprints](https://{$jira_domain}/secure/RapidBoard.jspa?rapidView={$project_key}&view=reporting)\n\n";
        } else {
            $board_text .= "❌ **No project binding found**\n\n";
            $board_text .= "**General Board Links:**\n";
            $board_text .= "• [All Boards](https://{$jira_domain}/secure/RapidBoard.jspa)\n";
            $board_text .= "• [Project Boards](https://{$jira_domain}/secure/BrowseProjects.jspa)\n";
            $board_text .= "• [Dashboard](https://{$jira_domain}/secure/Dashboard.jspa)\n\n";
            $board_text .= "**To bind this channel to a project:**\n";
            $board_text .= "• `/jira bind PROJECT-KEY` - Then use `/jira board` again\n";
        }
        
        $board_text .= "**Other Commands:**\n";
        $board_text .= "• `/jira link` - Get issue creation links\n";
        $board_text .= "• `/jira status` - Check current binding\n";
        $board_text .= "• `/jira create Title` - Create issue via chat\n";
        
        return array(
            'response_type' => 'in_channel',
            'text' => $board_text
        );
    }
    
    /**
     * Create issue in Jira via API
     */
    private function create_issue_in_jira($project_key, $title, $user_name, $channel_name) {
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        $api_key = get_option('wp_mm_slash_jira_api_key');
        
        if (empty($jira_domain) || empty($api_key)) {
            return array('success' => false, 'error' => 'Jira configuration not set up');
        }
        
        // Clean and validate the Jira domain
        $jira_domain = $this->clean_jira_domain($jira_domain);
        
        if (empty($jira_domain)) {
            return array('success' => false, 'error' => 'Invalid Jira domain format. Please use format: your-domain.atlassian.net');
        }
        
        // Determine issue type based on channel name or default to Task
        $issue_type = 'Task';
        if (stripos($channel_name, 'bug') !== false) {
            $issue_type = 'Bug';
        } elseif (stripos($channel_name, 'feature') !== false || stripos($channel_name, 'enhancement') !== false) {
            $issue_type = 'Story';
        }
        
        $data = array(
            'fields' => array(
                'project' => array(
                    'key' => $project_key
                ),
                'summary' => $title,
                'description' => "Issue created via Mattermost slash command by @{$user_name} from channel #{$channel_name}",
                'issuetype' => array(
                    'name' => $issue_type
                )
            )
        );
        
        $url = "https://{$jira_domain}/rest/api/2/issue";
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) === 201 && isset($result['key'])) {
            return array(
                'success' => true,
                'issue_key' => $result['key'],
                'url' => "https://{$jira_domain}/browse/{$result['key']}"
            );
        } else {
            $error = isset($result['errorMessages']) ? implode(', ', $result['errorMessages']) : 'Unknown error';
            return array('success' => false, 'error' => $error);
        }
    }
    
    /**
     * Clean and validate Jira domain
     */
    private function clean_jira_domain($domain) {
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
    
    /**
     * Show help message
     */
    private function show_help() {
        return array(
            'response_type' => 'ephemeral',
            'text' => "**Jira Slash Command Help**\n\n" .
                     "**Create an issue:**\n" .
                     "• `/jira create Title` - Creates issue in mapped project\n" .
                     "• `/jira create PROJ-123 Title` - Creates issue with specific project key\n\n" .
                     "**Assign an issue:**\n" .
                     "• `/jira assign PROJ-123 user@example.com` - Assigns issue to user by email\n\n" .
                     "**Bind channel to project:**\n" .
                     "• `/jira bind PROJECT-KEY` - Binds current channel to Jira project\n\n" .
                     "**Check channel status:**\n" .
                     "• `/jira status` - Shows current project binding and statistics\n\n" .
                     "**Get Jira links:**\n" .
                     "• `/jira link` - Get links for creating new tasks\n" .
                     "• `/jira board` - Get links to Jira boards and backlogs\n\n" .
                     "**Examples:**\n" .
                     "• `/jira create Fix login bug`\n" .
                     "• `/jira create PROJ-456 Add new feature`\n" .
                     "• `/jira assign PROJ-123 developer@company.com`\n" .
                     "• `/jira bind PROJ` - Bind channel to PROJ project\n" .
                     "• `/jira status` - Check current binding status\n" .
                     "• `/jira link` - Get task creation links\n" .
                     "• `/jira board` - Get board links\n\n" .
                     "**Note:** If no project key is specified, the issue will be created in the project mapped to this channel."
        );
    }
    
    /**
     * Get all channel-project mappings
     */
    public function get_mappings($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            return new WP_Error('table_not_found', 'Mappings table does not exist. Please deactivate and reactivate the plugin.', array('status' => 500));
        }
        
        $mappings = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        if ($mappings === null) {
            return new WP_Error('db_error', 'Database error: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return new WP_REST_Response($mappings, 200);
    }
    
    /**
     * Create a new channel-project mapping
     */
    public function create_mapping($request) {
        $channel_id = sanitize_text_field($request->get_param('channel_id'));
        $channel_name = sanitize_text_field($request->get_param('channel_name'));
        $jira_project_key = sanitize_text_field($request->get_param('jira_project_key'));
        
        if (empty($channel_id) || empty($channel_name) || empty($jira_project_key)) {
            return new WP_Error('missing_fields', 'All fields are required', array('status' => 400));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        
        // Check if mapping already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE channel_id = %s",
            $channel_id
        ));
        
        if ($existing) {
            return new WP_Error('duplicate', 'Mapping for this channel already exists', array('status' => 409));
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'channel_id' => $channel_id,
                'channel_name' => $channel_name,
                'jira_project_key' => $jira_project_key,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Database error: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return new WP_REST_Response(array(
            'id' => $wpdb->insert_id,
            'message' => 'Mapping created successfully'
        ), 201);
    }
    
    /**
     * Delete a channel-project mapping
     */
    public function delete_mapping($request) {
        $id = $request->get_param('id');
        
        if (empty($id) || !is_numeric($id)) {
            return new WP_Error('invalid_id', 'Invalid mapping ID', array('status' => 400));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        
        $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('db_error', 'Database error: ' . $wpdb->last_error, array('status' => 500));
        }
        
        if ($result === 0) {
            return new WP_Error('not_found', 'Mapping not found', array('status' => 404));
        }
        
        return new WP_REST_Response(array('message' => 'Mapping deleted successfully'), 200);
    }
    
    /**
     * Get log details by ID
     */
    public function get_log_details($request) {
        // Ensure logger is available
        if (!$this->logger) {
            $this->logger = new WP_MM_Slash_Jira_Logger();
        }
        
        $id = $request->get_param('id');
        
        if (empty($id) || !is_numeric($id)) {
            return new WP_Error('invalid_id', 'Invalid log ID', array('status' => 400));
        }
        
        $log = $this->logger->get_log($id);
        
        if (!$log) {
            return new WP_Error('not_found', 'Log entry not found', array('status' => 404));
        }
        
        // Format the payloads for display
        $log->request_payload_formatted = $this->logger->format_payload($log->request_payload);
        $log->response_payload_formatted = $this->logger->format_payload($log->response_payload);
        
        return new WP_REST_Response($log, 200);
    }
}
