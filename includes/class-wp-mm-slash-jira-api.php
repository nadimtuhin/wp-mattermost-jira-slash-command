<?php
/**
 * API handler class for Jira integration
 */

// Ensure WordPress is loaded
if (!function_exists('wp_remote_put')) {
    // If WordPress HTTP API is not available, try to load it
    if (function_exists('require_once')) {
        // Try to load WordPress HTTP API functions
        if (file_exists(ABSPATH . 'wp-includes/http.php')) {
            require_once(ABSPATH . 'wp-includes/http.php');
        }
    }
}

class WP_MM_Slash_Jira_API {
    
    private $logger;
    
    /**
     * Helper method to make HTTP requests with fallback to cURL
     */
    private function make_http_request($url, $args = array()) {
        // Try WordPress HTTP API first
        if (function_exists('wp_remote_request')) {
            return wp_remote_request($url, $args);
        }
        
        // Fallback to cURL
        return $this->curl_request($url, $args);
    }
    
    /**
     * Fallback cURL implementation
     */
    private function curl_request($url, $args = array()) {
        if (!function_exists('curl_init')) {
            return new WP_Error('curl_not_available', 'cURL is not available');
        }
        
        $method = isset($args['method']) ? strtoupper($args['method']) : 'GET';
        $headers = isset($args['headers']) ? $args['headers'] : array();
        $body = isset($args['body']) ? $args['body'] : '';
        $timeout = isset($args['timeout']) ? $args['timeout'] : 30;
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        // Set method
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        // Set headers
        if (!empty($headers)) {
            $header_lines = array();
            foreach ($headers as $key => $value) {
                $header_lines[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header_lines);
        }
        
        // Set body
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        $response_body = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return new WP_Error('curl_error', $error);
        }
        
        // Create a response object similar to wp_remote_request
        $response = array(
            'body' => $response_body,
            'response' => array(
                'code' => $response_code,
                'message' => 'OK'
            ),
            'headers' => array()
        );
        
        return $response;
    }
    
    /**
     * Get authentication header for Jira API calls
     */
    private function get_auth_header() {
        $api_user_email = get_option('wp_mm_slash_jira_api_user_email');
        $api_key = get_option('wp_mm_slash_jira_api_key');
        
        if (empty($api_user_email) || empty($api_key)) {
            return false;
        }
        return 'Basic ' . base64_encode($api_user_email . ':' . $api_key);
    }
    
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
                case 'bug':
                    $response = $this->create_jira_issue_with_type($channel_id, $channel_name, $text, $user_name, 'Bug');
                    break;
                case 'task':
                    $response = $this->create_jira_issue_with_type($channel_id, $channel_name, $text, $user_name, 'Task');
                    break;
                case 'story':
                    $response = $this->create_jira_issue_with_type($channel_id, $channel_name, $text, $user_name, 'Story');
                    break;
                case 'view':
                    $response = $this->view_jira_issue($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'assign':
                    $response = $this->assign_jira_issue($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'bind':
                    $response = $this->bind_channel_project($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'unbind':
                    $response = $this->unbind_channel_project($channel_id, $channel_name, $text, $user_name);
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
                case 'projects':
                    $response = $this->list_jira_projects($channel_id, $channel_name, $text, $user_name);
                    break;
                case 'find':
                    $response = $this->find_jira_user($channel_id, $channel_name, $text, $user_name);
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
        $issue_type = null;
        
        // Check if project key is provided in command
        if (count($parts) >= 2) {
            $second_part = $parts[1];
            
            // Check if it's a full issue key format (e.g., "PROJ-123")
            if (preg_match('/^[A-Z]+-\d+$/', $second_part)) {
                $project_key = explode('-', $second_part)[0];
                $issue_key = $second_part;
                $title = implode(' ', array_slice($parts, 2));
            }
            // Check if it's just a project key (e.g., "TPFIJB")
            elseif (preg_match('/^[A-Z0-9]+$/', $second_part) && strlen($second_part) <= 10) {
                $project_key = $second_part;
                $title = implode(' ', array_slice($parts, 2));
            }
        }
        
        // If no project key found in command, get from channel mapping
        if (!$project_key) {
            $table_name = $wpdb->prefix . 'mm_jira_mappings';
            $mapping = $wpdb->get_row($wpdb->prepare(
                "SELECT jira_project_key FROM $table_name WHERE channel_id = %s",
                $channel_id
            ));
            
            if (!$mapping) {
                return array(
                    'response_type' => 'ephemeral',
                    'text' => "❌ No Jira project mapped to this channel. Please contact an administrator to set up the mapping or specify a project key in your command (e.g., `/jira create TPFIJB Add new feature` or `/jira create PROJ-123 Task title`)."
                );
            }
            
            $project_key = $mapping->jira_project_key;
            $title = implode(' ', array_slice($parts, 1));
        }
        
        // Parse task type from command if specified
        // Format: /jira create [PROJECT-KEY] [TYPE:]Title
        // Examples: /jira create Bug:Fix login issue
        //          /jira create PROJ Story:Add new feature
        //          /jira create Task:Update documentation
        if (!empty($title)) {
            $title_parts = explode(':', $title, 2);
            if (count($title_parts) === 2) {
                $potential_type = trim($title_parts[0]);
                $actual_title = trim($title_parts[1]);
                
                // Validate if the first part looks like a valid issue type
                $valid_types = array('Task', 'Bug', 'Story', 'Epic', 'Subtask', 'Improvement', 'New Feature');
                if (in_array($potential_type, $valid_types) && !empty($actual_title)) {
                    $issue_type = $potential_type;
                    $title = $actual_title;
                }
            }
        }
        
        if (empty($title)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Please provide a title for the issue. Usage: `/jira create [PROJECT-KEY] Title` or `/jira create [PROJECT-KEY] [TYPE:]Title` or `/jira create Title`\n\n**Examples:**\n• `/jira create Fix login bug`\n• `/jira create Bug:Fix login bug`\n• `/jira create PROJ Story:Add new feature`\n• `/jira create Task:Update documentation`"
            );
        }
        
        // Create the issue in Jira
        $result = $this->create_issue_in_jira($project_key, $title, $user_name, $channel_name, $issue_type);
        
        if ($result['success']) {
            $response_text = "✅ Issue created successfully!\n\n**Issue:** {$result['issue_key']}\n**Title:** {$title}\n**Created by:** @{$user_name}\n**Project:** {$project_key}";
            
            if (!empty($issue_type)) {
                $response_text .= "\n**Type:** {$issue_type}";
            }
            
            // Add reporter information if it was automatically set
            if (isset($result['reporter_set']) && $result['reporter_set']) {
                $response_text .= "\n**Reporter:** {$result['reporter_name']} ({$result['reporter_email']})";
            }
            
            $response_text .= "\n\n[View in Jira]({$result['url']})";
            
            return array(
                'response_type' => 'in_channel',
                'text' => $response_text
            );
        } else {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Failed to create issue: {$result['error']}"
            );
        }
    }
    
    /**
     * Create a Jira issue with a specific type
     */
    private function create_jira_issue_with_type($channel_id, $channel_name, $text, $user_name, $type) {
        // Remove the command part and reconstruct the text with the type prefix
        $parts = explode(' ', trim($text));
        $title_parts = array_slice($parts, 1); // Remove the command (bug/task/story)
        
        if (empty($title_parts)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Please provide a title for the issue. Usage: `/jira {$type} Title` or `/jira {$type} PROJECT-KEY Title`\n\n**Examples:**\n• `/jira {$type} Fix login issue`\n• `/jira {$type} PROJ Add new feature`"
            );
        }
        
        // Reconstruct the text with the type prefix
        $reconstructed_text = "create " . $type . ":" . implode(' ', $title_parts);
        
        // Use the existing create_jira_issue method
        return $this->create_jira_issue($channel_id, $channel_name, $reconstructed_text, $user_name);
    }
    
    /**
     * View detailed Jira issue information
     */
    private function view_jira_issue($channel_id, $channel_name, $text, $user_name) {
        $parts = explode(' ', trim($text));
        
        // Check if we have an issue key: view ISSUE-KEY
        if (count($parts) < 2) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Please provide an issue key. Usage: `/jira view PROJ-123`\n\n**Examples:**\n• `/jira view PROJ-123`\n• `/jira view BUG-456`\n• `/jira view STORY-789`"
            );
        }
        
        $issue_key = $parts[1];
        
        // Validate issue key format
        if (!preg_match('/^[A-Z]+-\d+$/', $issue_key)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Invalid issue key format. Please use format: PROJECT-123"
            );
        }
        
        // Get detailed issue information from Jira
        $result = $this->get_issue_details_from_jira($issue_key);
        
        if ($result['success']) {
            $issue = $result['issue'];
            
            // Build the response text
            $response_text = "📋 **Issue Details: {$issue_key}**\n\n";
            
            // Basic information
            $response_text .= "**Summary:** {$issue['summary']}\n";
            $response_text .= "**Type:** {$issue['issuetype']}\n";
            $response_text .= "**Status:** {$issue['status']}\n";
            $response_text .= "**Priority:** {$issue['priority']}\n";
            
            // Assignee
            if (!empty($issue['assignee'])) {
                $response_text .= "**Assignee:** {$issue['assignee']}\n";
            } else {
                $response_text .= "**Assignee:** Unassigned\n";
            }
            
            // Reporter
            if (!empty($issue['reporter'])) {
                $response_text .= "**Reporter:** {$issue['reporter']}\n";
            }
            
            // Story points
            if (!empty($issue['story_points'])) {
                $response_text .= "**Story Points:** {$issue['story_points']}\n";
            }
            
            // Labels
            if (!empty($issue['labels'])) {
                $response_text .= "**Labels:** " . implode(', ', $issue['labels']) . "\n";
            }
            
            // Components
            if (!empty($issue['components'])) {
                $response_text .= "**Components:** " . implode(', ', $issue['components']) . "\n";
            }
            
            // Description
            if (!empty($issue['description'])) {
                $response_text .= "\n**Description:**\n{$issue['description']}\n";
            }
            
            // Comments
            if (!empty($issue['comments'])) {
                $response_text .= "\n**Comments ({$issue['comment_count']}):**\n";
                foreach ($issue['comments'] as $comment) {
                    $response_text .= "• **{$comment['author']}** ({$comment['date']}): {$comment['body']}\n";
                }
            }
            
            // Links
            $response_text .= "\n[View in Jira]({$issue['url']})";
            
            return array(
                'response_type' => 'in_channel',
                'text' => $response_text
            );
        } else {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Failed to get issue details: {$result['error']}"
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
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return array('success' => false, 'error' => 'Jira API credentials not configured');
        }
        
        $request_headers = array(
            'Authorization' => $auth_header,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
        $request_body = json_encode($data);
        
        $start_time = microtime(true);
        
        $response = $this->make_http_request($url, array(
            'method' => 'PUT',
            'headers' => $request_headers,
            'body' => $request_body,
            'timeout' => 30
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->log_jira_curl(
                'PUT',
                $url,
                $request_headers,
                $request_body,
                0,
                array(),
                '',
                $execution_time,
                'error',
                $error_message
            );
            return array('success' => false, 'error' => $error_message);
        }
        
        $response_code = isset($response['response']['code']) ? $response['response']['code'] : 0;
        $response_headers = isset($response['headers']) ? $response['headers'] : array();
        $response_body = isset($response['body']) ? $response['body'] : '';
        
        // Log the curl payload
        $status = ($response_code === 204) ? 'success' : 'error';
        $error_message = null;
        if ($status === 'error') {
            $result = json_decode($response_body, true);
            $error_message = isset($result['errorMessages']) ? implode(', ', $result['errorMessages']) : 'Unknown error';
        }
        
        $this->logger->log_jira_curl(
            'PUT',
            $url,
            $request_headers,
            $request_body,
            $response_code,
            $response_headers,
            $response_body,
            $execution_time,
            $status,
            $error_message
        );
        
        if ($response_code === 204) {
            return array(
                'success' => true,
                'url' => "https://{$jira_domain}/browse/{$issue_key}"
            );
        } else {
            return array('success' => false, 'error' => $error_message);
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
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return false;
        }
        
        $request_headers = array(
            'Authorization' => $auth_header,
            'Accept' => 'application/json'
        );
        
        $start_time = microtime(true);
        
        $response = $this->make_http_request($url, array(
            'method' => 'GET',
            'headers' => $request_headers,
            'timeout' => 30
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $this->logger->log_jira_curl(
                'GET',
                $url,
                $request_headers,
                '',
                0,
                array(),
                '',
                $execution_time,
                'error',
                $response->get_error_message()
            );
            return false;
        }
        
        $response_code = isset($response['response']['code']) ? $response['response']['code'] : 0;
        $response_headers = isset($response['headers']) ? $response['headers'] : array();
        $response_body = isset($response['body']) ? $response['body'] : '';
        $result = json_decode($response_body, true);
        
        // Log the curl payload
        $status = ($response_code === 200 && is_array($result) && !empty($result)) ? 'success' : 'error';
        $error_message = null;
        if ($status === 'error') {
            $error_message = 'User not found or API error';
        }
        
        $this->logger->log_jira_curl(
            'GET',
            $url,
            $request_headers,
            '',
            $response_code,
            $response_headers,
            $response_body,
            $execution_time,
            $status,
            $error_message
        );
        
        if ($response_code === 200 && is_array($result) && !empty($result)) {
            // Find the user with matching email
            foreach ($result as $user) {
                if (isset($user['emailAddress']) && strtolower($user['emailAddress']) === strtolower($email)) {
                    return $user['accountId'];
                }
            }

            // If no exact match, return the first user
            if (count($result) > 0) {
                return $result[0]['accountId'];
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
     * Unbind a channel from a Jira project key
     */
    private function unbind_channel_project($channel_id, $channel_name, $text, $user_name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        
        // Check if mapping exists for this channel
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE channel_id = %s",
            $channel_id
        ));
        
        if (!$existing) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ No project binding found for this channel.\n\n**Channel:** #{$channel_name}\n\nThis channel is not currently mapped to any Jira project."
            );
        }
        
        $project_key = $existing->jira_project_key;
        
        // Delete the mapping
        $result = $wpdb->delete(
            $table_name,
            array('channel_id' => $channel_id),
            array('%s')
        );
        
        if ($result !== false && $result > 0) {
            return array(
                'response_type' => 'in_channel',
                'text' => "✅ Channel binding removed successfully!\n\n**Channel:** #{$channel_name}\n**Removed Project:** {$project_key}\n**Removed by:** @{$user_name}\n\n**What this means:**\n• You can no longer use `/jira create Title` without specifying a project\n• You must specify project keys in commands: `/jira create PROJ Title`\n• Use `/jira bind PROJECT-KEY` to bind to a different project\n• Use `/jira projects` to see available projects"
            );
        } else {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Failed to remove channel binding. Please try again or contact an administrator."
            );
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
                       "• `/jira unbind` - Remove current project binding\n" .
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
     * List all available Jira projects
     */
    private function list_jira_projects($channel_id, $channel_name, $text, $user_name) {
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
        
        $url = "https://{$jira_domain}/rest/api/2/project";
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Jira API credentials not configured. Please contact an administrator."
            );
        }
        
        $request_headers = array(
            'Authorization' => $auth_header,
            'Accept' => 'application/json'
        );
        
        $start_time = microtime(true);
        
        $response = $this->make_http_request($url, array(
            'method' => 'GET',
            'headers' => $request_headers,
            'timeout' => 30
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->log_jira_curl(
                'GET',
                $url,
                $request_headers,
                '',
                0,
                array(),
                '',
                $execution_time,
                'error',
                $error_message
            );
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Error fetching projects: " . $error_message
            );
        }
        
        $response_code = isset($response['response']['code']) ? $response['response']['code'] : 0;
        $response_headers = isset($response['headers']) ? $response['headers'] : array();
        $response_body = isset($response['body']) ? $response['body'] : '';
        $projects = json_decode($response_body, true);
        
        // Log the curl payload
        $status = ($response_code === 200 && is_array($projects)) ? 'success' : 'error';
        $error_message = null;
        if ($status === 'error') {
            $error_message = isset($projects['errorMessages']) ? implode(', ', $projects['errorMessages']) : 'Unknown error';
        }
        
        $this->logger->log_jira_curl(
            'GET',
            $url,
            $request_headers,
            '',
            $response_code,
            $response_headers,
            $response_body,
            $execution_time,
            $status,
            $error_message
        );
        
        if ($response_code !== 200 || !is_array($projects)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Error fetching projects: " . $error_message
            );
        }
        
        // Sort projects by name
        usort($projects, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        
        $projects_text = "📋 **Available Jira Projects**\n\n";
        $projects_text .= "**Total Projects:** " . count($projects) . "\n\n";
        
        // Group projects by first letter for better organization
        $grouped_projects = array();
        foreach ($projects as $project) {
            $first_letter = strtoupper(substr($project['name'], 0, 1));
            if (!isset($grouped_projects[$first_letter])) {
                $grouped_projects[$first_letter] = array();
            }
            $grouped_projects[$first_letter][] = $project;
        }
        
        ksort($grouped_projects);
        
        foreach ($grouped_projects as $letter => $letter_projects) {
            $projects_text .= "**{$letter}**\n";
            foreach ($letter_projects as $project) {
                $project_key = $project['key'];
                $project_name = $project['name'];
                $project_url = "https://{$jira_domain}/browse/{$project_key}";
                
                $projects_text .= "• **{$project_key}** - [{$project_name}]({$project_url})\n";
            }
            $projects_text .= "\n";
        }
        
        $projects_text .= "**To bind this channel to a project:**\n";
        $projects_text .= "• `/jira bind PROJECT-KEY` - Replace PROJECT-KEY with one of the keys above\n\n";
        $projects_text .= "**To create issues in a specific project:**\n";
        $projects_text .= "• `/jira create PROJECT-KEY Title` - Create issue in specific project\n";
        $projects_text .= "• `/jira bug PROJECT-KEY Title` - Create bug in specific project\n";
        $projects_text .= "• `/jira task PROJECT-KEY Title` - Create task in specific project\n";
        $projects_text .= "• `/jira story PROJECT-KEY Title` - Create story in specific project\n\n";
        $projects_text .= "**Other Commands:**\n";
        $projects_text .= "• `/jira status` - Check current project binding\n";
        $projects_text .= "• `/jira board` - Get board links for current project\n";
        $projects_text .= "• `/jira link` - Get issue creation links\n";
        
        return array(
            'response_type' => 'in_channel',
            'text' => $projects_text
        );
    }
    
    /**
     * Find Jira user by email
     */
    private function find_jira_user($channel_id, $channel_name, $text, $user_name) {
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
        
        // Parse the email from the command
        $parts = explode(' ', trim($text));
        if (count($parts) < 2) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Please provide an email address to search for. Usage: `/jira find user@example.com`\n\n**Examples:**\n• `/jira find developer@company.com`\n• `/jira find john.doe@example.com`"
            );
        }
        
        $email = trim($parts[1]);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Invalid email format. Please provide a valid email address.\n\n**Example:** `/jira find user@example.com`"
            );
        }
        
        $url = "https://{$jira_domain}/rest/api/2/user/search?query=" . urlencode($email);
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Jira API credentials not configured. Please contact an administrator."
            );
        }
        
        $request_headers = array(
            'Authorization' => $auth_header,
            'Accept' => 'application/json'
        );
        
        $start_time = microtime(true);
        
        $response = $this->make_http_request($url, array(
            'method' => 'GET',
            'headers' => $request_headers,
            'timeout' => 30
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->log_jira_curl(
                'GET',
                $url,
                $request_headers,
                '',
                0,
                array(),
                '',
                $execution_time,
                'error',
                $error_message
            );
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Error searching for user: " . $error_message
            );
        }
        
        $response_code = isset($response['response']['code']) ? $response['response']['code'] : 0;
        $response_headers = isset($response['headers']) ? $response['headers'] : array();
        $response_body = isset($response['body']) ? $response['body'] : '';
        $users = json_decode($response_body, true);
        
        // Log the curl payload
        $status = ($response_code === 200 && is_array($users)) ? 'success' : 'error';
        $error_message = null;
        if ($status === 'error') {
            $error_message = isset($users['errorMessages']) ? implode(', ', $users['errorMessages']) : 'Unknown error';
        }
        
        $this->logger->log_jira_curl(
            'GET',
            $url,
            $request_headers,
            '',
            $response_code,
            $response_headers,
            $response_body,
            $execution_time,
            $status,
            $error_message
        );
        
        if ($response_code !== 200 || !is_array($users)) {
            return array(
                'response_type' => 'ephemeral',
                'text' => "❌ Error searching for user: " . $error_message
            );
        }
        
        // Filter users by exact email match
        $exact_matches = array();
        $partial_matches = array();
        
        foreach ($users as $user) {
            if (isset($user['emailAddress']) && strtolower($user['emailAddress']) === strtolower($email)) {
                $exact_matches[] = $user;
            } elseif (isset($user['emailAddress']) && stripos($user['emailAddress'], $email) !== false) {
                $partial_matches[] = $user;
            } elseif (isset($user['displayName']) && stripos($user['displayName'], $email) !== false) {
                $partial_matches[] = $user;
            }
        }
        
        $user_text = "🔍 **User Search Results for: {$email}**\n\n";
        
        if (empty($exact_matches) && empty($partial_matches)) {
            $user_text .= "❌ **No users found** with the email address `{$email}`\n\n";
            $user_text .= "**Possible reasons:**\n";
            $user_text .= "• User doesn't exist in Jira\n";
            $user_text .= "• User email is different\n";
            $user_text .= "• User account is inactive\n";
            $user_text .= "• Insufficient permissions to view user\n\n";
            $user_text .= "**Try:**\n";
            $user_text .= "• Check the email spelling\n";
            $user_text .= "• Use a partial email search\n";
            $user_text .= "• Contact your Jira administrator\n";
        } else {
            // Show exact matches first
            if (!empty($exact_matches)) {
                $user_text .= "✅ **Exact Email Matches:**\n";
                foreach ($exact_matches as $user) {
                    $user_text .= $this->format_user_info($user, $jira_domain);
                }
                $user_text .= "\n";
            }
            
            // Show partial matches
            if (!empty($partial_matches)) {
                $user_text .= "🔍 **Partial Matches:**\n";
                foreach ($partial_matches as $user) {
                    $user_text .= $this->format_user_info($user, $jira_domain);
                }
                $user_text .= "\n";
            }
            
            $user_text .= "**To assign issues to a user:**\n";
            $user_text .= "• `/jira assign PROJ-123 {$email}` - Assign issue to user\n\n";
            $user_text .= "**Other Commands:**\n";
            $user_text .= "• `/jira view PROJ-123` - View issue details\n";
            $user_text .= "• `/jira create Title` - Create new issue\n";
            $user_text .= "• `/jira help` - Show all commands\n";
        }
        
        return array(
            'response_type' => 'in_channel',
            'text' => $user_text
        );
    }
    
    /**
     * Format user information for display
     */
    private function format_user_info($user, $jira_domain) {
        $display_name = isset($user['displayName']) ? $user['displayName'] : 'Unknown';
        $email = isset($user['emailAddress']) ? $user['emailAddress'] : 'No email';
        $account_id = isset($user['accountId']) ? $user['accountId'] : 'No account ID';
        $active = isset($user['active']) ? ($user['active'] ? 'Active' : 'Inactive') : 'Unknown';
        $timezone = isset($user['timeZone']) ? $user['timeZone'] : 'Unknown';
        
        $user_url = "https://{$jira_domain}/secure/ViewProfile.jspa?name=" . urlencode($account_id);
        
        $formatted = "• **{$display_name}**\n";
        $formatted .= "  📧 Email: `{$email}`\n";
        $formatted .= "  🆔 Account ID: `{$account_id}`\n";
        $formatted .= "  📊 Status: {$active}\n";
        $formatted .= "  🌍 Timezone: {$timezone}\n";
        $formatted .= "  🔗 [View Profile]({$user_url})\n\n";
        
        return $formatted;
    }
    
    /**
     * Create issue in Jira via API
     */
    private function create_issue_in_jira($project_key, $title, $user_name, $channel_name, $issue_type = null) {
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
        if ($issue_type === null) {
            $issue_type = 'Task';
            if (stripos($channel_name, 'bug') !== false) {
                $issue_type = 'Bug';
            } elseif (stripos($channel_name, 'feature') !== false || stripos($channel_name, 'enhancement') !== false) {
                $issue_type = 'Story';
            }
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
        
        // Try to automatically set reporter if email domain is configured
        $email_domain = get_option('wp_mm_slash_jira_email_domain');
        if (!empty($email_domain)) {
            $reporter_user = $this->find_user_by_username_and_domain($user_name, $email_domain);
            if ($reporter_user && isset($reporter_user['accountId'])) {
                $data['fields']['reporter'] = array(
                    'accountId' => $reporter_user['accountId']
                );
            }
        }
        
        $url = "https://{$jira_domain}/rest/api/2/issue";
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return array('success' => false, 'error' => 'Jira API credentials not configured');
        }
        
        $request_headers = array(
            'Authorization' => $auth_header,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
        $request_body = json_encode($data);
        
        $start_time = microtime(true);
        
        $response = $this->make_http_request($url, array(
            'method' => 'POST',
            'headers' => $request_headers,
            'body' => $request_body,
            'timeout' => 30
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->log_jira_curl(
                'POST',
                $url,
                $request_headers,
                $request_body,
                0,
                array(),
                '',
                $execution_time,
                'error',
                $error_message
            );
            return array('success' => false, 'error' => $error_message);
        }
        
        $response_code = isset($response['response']['code']) ? $response['response']['code'] : 0;
        $response_headers = isset($response['headers']) ? $response['headers'] : array();
        $response_body = isset($response['body']) ? $response['body'] : '';
        $result = json_decode($response_body, true);
        
        // Log the curl payload
        $status = ($response_code === 201 && isset($result['key'])) ? 'success' : 'error';
        $error_message = null;
        if ($status === 'error') {
            $error_message = isset($result['errorMessages']) ? implode(', ', $result['errorMessages']) : 'Unknown error';
        }
        
        $this->logger->log_jira_curl(
            'POST',
            $url,
            $request_headers,
            $request_body,
            $response_code,
            $response_headers,
            $response_body,
            $execution_time,
            $status,
            $error_message
        );
        
        if ($response_code === 201 && isset($result['key'])) {
            $response_data = array(
                'success' => true,
                'issue_key' => $result['key'],
                'url' => "https://{$jira_domain}/browse/{$result['key']}"
            );
            
            // Add reporter information if it was set
            if (!empty($email_domain) && isset($reporter_user)) {
                $response_data['reporter_set'] = true;
                $response_data['reporter_email'] = $reporter_user['emailAddress'];
                $response_data['reporter_name'] = $reporter_user['displayName'];
            }
            
            return $response_data;
        } else {
            return array('success' => false, 'error' => $error_message);
        }
    }
    
    /**
     * Get detailed issue information from Jira API
     */
    private function get_issue_details_from_jira($issue_key) {
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
        
        $url = "https://{$jira_domain}/rest/api/2/issue/{$issue_key}?expand=comments,renderedFields";
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return array('success' => false, 'error' => 'Jira API credentials not configured');
        }
        
        $request_headers = array(
            'Authorization' => $auth_header,
            'Accept' => 'application/json'
        );
        
        $start_time = microtime(true);
        
        $response = $this->make_http_request($url, array(
            'method' => 'GET',
            'headers' => $request_headers,
            'timeout' => 30
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->log_jira_curl(
                'GET',
                $url,
                $request_headers,
                '',
                0,
                array(),
                '',
                $execution_time,
                'error',
                $error_message
            );
            return array('success' => false, 'error' => $error_message);
        }
        
        $response_code = isset($response['response']['code']) ? $response['response']['code'] : 0;
        $response_headers = isset($response['headers']) ? $response['headers'] : array();
        $response_body = isset($response['body']) ? $response['body'] : '';
        $result = json_decode($response_body, true);
        
        // Log the curl payload
        $status = ($response_code === 200 && isset($result['key'])) ? 'success' : 'error';
        $error_message = null;
        if ($status === 'error') {
            $error_message = isset($result['errorMessages']) ? implode(', ', $result['errorMessages']) : 'Unknown error';
        }
        
        $this->logger->log_jira_curl(
            'GET',
            $url,
            $request_headers,
            '',
            $response_code,
            $response_headers,
            $response_body,
            $execution_time,
            $status,
            $error_message
        );
        
        if ($response_code === 200 && isset($result['key'])) {
            // Parse the issue data
            $issue = array(
                'key' => $result['key'],
                'summary' => isset($result['fields']['summary']) ? $result['fields']['summary'] : 'No summary',
                'issuetype' => isset($result['fields']['issuetype']['name']) ? $result['fields']['issuetype']['name'] : 'Unknown',
                'status' => isset($result['fields']['status']['name']) ? $result['fields']['status']['name'] : 'Unknown',
                'priority' => isset($result['fields']['priority']['name']) ? $result['fields']['priority']['name'] : 'Unset',
                'assignee' => isset($result['fields']['assignee']['displayName']) ? $result['fields']['assignee']['displayName'] : '',
                'reporter' => isset($result['fields']['reporter']['displayName']) ? $result['fields']['reporter']['displayName'] : '',
                'story_points' => isset($result['fields']['customfield_10016']) ? $result['fields']['customfield_10016'] : '', // Common story points field
                'labels' => isset($result['fields']['labels']) ? $result['fields']['labels'] : array(),
                'components' => isset($result['fields']['components']) ? array_map(function($comp) { return $comp['name']; }, $result['fields']['components']) : array(),
                'description' => isset($result['fields']['description']) ? $result['fields']['description'] : '',
                'comments' => array(),
                'comment_count' => 0,
                'url' => "https://{$jira_domain}/browse/{$result['key']}"
            );
            
            // Parse comments
            if (isset($result['fields']['comment']['comments'])) {
                $comments = $result['fields']['comment']['comments'];
                $issue['comment_count'] = count($comments);
                
                // Get the latest 5 comments
                $recent_comments = array_slice($comments, -5);
                foreach ($recent_comments as $comment) {
                    $issue['comments'][] = array(
                        'author' => isset($comment['author']['displayName']) ? $comment['author']['displayName'] : 'Unknown',
                        'date' => isset($comment['created']) ? date('M j, Y g:i A', strtotime($comment['created'])) : '',
                        'body' => isset($comment['body']) ? $this->clean_comment_text($comment['body']) : ''
                    );
                }
            }
            
            // Try alternative story points field names
            if (empty($issue['story_points'])) {
                $story_point_fields = array('customfield_10016', 'customfield_10008', 'customfield_10004', 'customfield_10002');
                foreach ($story_point_fields as $field) {
                    if (isset($result['fields'][$field]) && !empty($result['fields'][$field])) {
                        $issue['story_points'] = $result['fields'][$field];
                        break;
                    }
                }
            }
            
            return array('success' => true, 'issue' => $issue);
        } else {
            return array('success' => false, 'error' => $error_message);
        }
    }
    
    /**
     * Clean comment text for display
     */
    private function clean_comment_text($text) {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Truncate if too long
        if (strlen($text) > 200) {
            $text = substr($text, 0, 200) . '...';
        }
        
        return trim($text);
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
                     "• `/jira create PROJECT-KEY Title` - Creates issue with specific project key\n" .
                     "• `/jira create PROJ-123 Title` - Creates issue with specific project key (legacy format)\n" .
                     "• `/jira create TYPE:Title` - Creates issue with specific type (Task, Bug, Story, Epic, etc.)\n" .
                     "• `/jira create PROJECT-KEY TYPE:Title` - Creates issue with specific project and type\n\n" .
                     "**Quick issue creation (shortcuts):**\n" .
                     "• `/jira bug Title` - Creates a bug issue\n" .
                     "• `/jira bug PROJECT-KEY Title` - Creates a bug issue in specific project\n" .
                     "• `/jira task Title` - Creates a task issue\n" .
                     "• `/jira task PROJECT-KEY Title` - Creates a task issue in specific project\n" .
                     "• `/jira story Title` - Creates a story issue\n" .
                     "• `/jira story PROJECT-KEY Title` - Creates a story issue in specific project\n\n" .
                     "**View issue details:**\n" .
                     "• `/jira view PROJ-123` - View detailed information about an issue\n\n" .
                     "**Assign an issue:**\n" .
                     "• `/jira assign PROJ-123 user@example.com` - Assigns issue to user by email\n\n" .
                     "**Find a user:**\n" .
                     "• `/jira find user@example.com` - Search for a user by email address\n\n" .
                     "**Bind channel to project:**\n" .
                     "• `/jira bind PROJECT-KEY` - Binds current channel to Jira project\n\n" .
                     "**Unbind channel from project:**\n" .
                     "• `/jira unbind` - Removes current channel's project binding\n\n" .
                     "**Check channel status:**\n" .
                     "• `/jira status` - Shows current project binding and statistics\n\n" .
                     "**Get Jira links:**\n" .
                     "• `/jira link` - Get links for creating new tasks\n" .
                     "• `/jira board` - Get links to Jira boards and backlogs\n" .
                     "• `/jira projects` - List all available Jira projects\n\n" .
                     "**Examples:**\n" .
                     "• `/jira create Fix login bug`\n" .
                     "• `/jira bug Fix login issue`\n" .
                     "• `/jira task Update documentation`\n" .
                     "• `/jira story Add new feature`\n" .
                     "• `/jira view PROJ-123` - View issue details\n" .
                     "• `/jira create Bug:Fix login bug`\n" .
                     "• `/jira create PROJ Story:Add new feature`\n" .
                     "• `/jira create Task:Update documentation`\n" .
                     "• `/jira create TPFIJB Add new feature`\n" .
                     "• `/jira create PROJ-456 Add new feature`\n" .
                     "• `/jira assign PROJ-123 developer@company.com`\n" .
                     "• `/jira bind PROJ` - Bind channel to PROJ project\n" .
                     "• `/jira unbind` - Remove channel's project binding\n" .
                     "• `/jira status` - Check current binding status\n" .
                     "• `/jira link` - Get task creation links\n" .
                     "• `/jira board` - Get board links\n\n" .
                     "**Available Issue Types:**\n" .
                     "• Task, Bug, Story, Epic, Subtask, Improvement, New Feature\n\n" .
                     "**Note:** If no project key is specified, the issue will be created in the project mapped to this channel. If no issue type is specified, it will be determined automatically based on the channel name or default to 'Task'."
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
    
    /**
     * Find Jira user by username and email domain
     */
    private function find_user_by_username_and_domain($username, $email_domain) {
        if (empty($email_domain) || empty($username)) {
            return null;
        }
        $email = $username . '@' . $email_domain;
        return $this->find_user_by_email($email);
    }
    
    /**
     * Find Jira user by email (internal method)
     */
    private function find_user_by_email($email) {
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        
        if (empty($jira_domain)) {
            return null;
        }
        
        // Clean and validate the Jira domain
        $jira_domain = $this->clean_jira_domain($jira_domain);
        
        if (empty($jira_domain)) {
            return null;
        }
        
        $url = "https://{$jira_domain}/rest/api/2/user/search?query=" . urlencode($email);
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return null;
        }
        
        $request_headers = array(
            'Authorization' => $auth_header,
            'Accept' => 'application/json'
        );
        
        $start_time = microtime(true);
        
        $response = $this->make_http_request($url, array(
            'method' => 'GET',
            'headers' => $request_headers,
            'timeout' => 30
        ));
        
        $execution_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $response_code = isset($response['response']['code']) ? $response['response']['code'] : 0;
        $response_body = isset($response['body']) ? $response['body'] : '';
        $users = json_decode($response_body, true);
        
        if ($response_code !== 200 || !is_array($users)) {
            return null;
        }
        
        // Find exact email match
        foreach ($users as $user) {
            if (isset($user['emailAddress']) && strtolower($user['emailAddress']) === strtolower($email)) {
                return $user;
            }
        }

        // If no exact match, return the first user
        if (count($users) > 0) {
            return $users[0];
        }

        return null;
    }
}
