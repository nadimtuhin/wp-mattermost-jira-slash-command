<?php
class Wpjra_Api {

    private $base_url;
    private $api_user_email;
    private $api_token;
    private $project_key;
    private $issue_type;
    private $default_labels;
    private $custom_field_vertical_id;
    private $custom_field_impact_id;


    public function __construct() {
        $this->base_url = rtrim(wpjra_get_option('jira_base_url'), '/');
        $this->api_user_email = wpjra_get_option('jira_api_user_email');
        $this->api_token = wpjra_get_option('jira_api_token');
        $this->project_key = wpjra_get_option('jira_project_key');
        $this->issue_type = wpjra_get_option('jira_issue_type', 'Bug');
        $this->default_labels = array_map('trim', explode(',', wpjra_get_option('jira_default_labels', 'public-submitted')));
        $this->custom_field_vertical_id = wpjra_get_option('jira_custom_field_vertical');
        $this->custom_field_impact_id = wpjra_get_option('jira_custom_field_impact');

        if (empty($this->base_url) || empty($this->api_user_email) || empty($this->api_token) || empty($this->project_key)) {
            // Potentially log this or handle it more gracefully.
            // For now, methods will check for these and return errors.
        }
    }

    private function get_auth_header() {
        if (empty($this->api_user_email) || empty($this->api_token)) {
            return false;
        }
        // Fixed: Use the same format as the working example - api_key: (with colon at end)
        return ($this->api_token);
    }

    private function make_request($endpoint, $method = 'GET', $body = [], $is_file_upload = false) {
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return new WP_Error('config_error', __('Jira API credentials not configured.', 'wp-public-jira-reporter'));
        }

        $url = $this->base_url . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => $auth_header,
                'Accept'        => 'application/json',
            ],
            'timeout' => 30, // 30 seconds timeout
        ];

        if (!$is_file_upload) {
             $args['headers']['Content-Type'] = 'application/json';
        }


        if (!empty($body)) {
            if ($method === 'POST' || $method === 'PUT') {
                $args['body'] = $is_file_upload ? $body : wp_json_encode($body);
            } elseif ($method === 'GET' && is_array($body)) {
                 $url = add_query_arg($body, $url);
            }
        }
        
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($response_body, true);

        if ($response_code >= 200 && $response_code < 300) {
            return $decoded_body;
        } else {
            $error_message = __('Jira API Error', 'wp-public-jira-reporter');
            if (isset($decoded_body['errorMessages']) && !empty($decoded_body['errorMessages'])) {
                $error_message = implode(', ', $decoded_body['errorMessages']);
            } elseif (isset($decoded_body['errors']) && is_array($decoded_body['errors'])) {
                $messages = [];
                foreach ($decoded_body['errors'] as $field => $message) {
                    $messages[] = $field . ': ' . $message;
                }
                $error_message = implode(', ', $messages);
            } else if (!empty($response_body) && strlen($response_body) < 200) { // Jira sometimes returns plain text errors for auth etc.
                 $error_message = strip_tags($response_body);
            }
            return new WP_Error('api_error', $error_message, ['status' => $response_code, 'body' => $decoded_body]);
        }
    }

    public function get_available_issue_types() {
        if (empty($this->project_key)) {
            return new WP_Error('missing_project_key', __('Project key is not configured.', 'wp-public-jira-reporter'));
        }
        
        // Try multiple endpoints in order of preference
        $endpoints = [
            "/rest/api/3/issue/createmeta/{$this->project_key}/issuetypes",
            "/rest/api/2/issue/createmeta/{$this->project_key}/issuetypes",
            "/rest/api/3/project/{$this->project_key}",
        ];
        
        $result = null;
        $last_error = null;
        
        foreach ($endpoints as $endpoint) {
            $result = $this->make_request($endpoint);
            
            if (!is_wp_error($result)) {
                break; // Success, exit loop
            }
            
            $last_error = $result;
        }
        
        if (is_wp_error($result)) {
            // Provide more specific error messages
            $error_message = $result->get_error_message();
            if (strpos($error_message, '404') !== false) {
                return new WP_Error('project_not_found', sprintf(
                    __('Project "%s" not found. Please check the project key.', 'wp-public-jira-reporter'),
                    $this->project_key
                ));
            } elseif (strpos($error_message, '403') !== false) {
                return new WP_Error('permission_denied', sprintf(
                    __('Access denied to project "%s". Please check user permissions.', 'wp-public-jira-reporter'),
                    $this->project_key
                ));
            } elseif (strpos($error_message, '401') !== false) {
                return new WP_Error('authentication_failed', __('Authentication failed. Please check your API credentials.', 'wp-public-jira-reporter'));
            }
            return $result;
        }
        
        $issue_types = [];
        
        // Handle different response formats
        if (isset($result['values']) && is_array($result['values'])) {
            // Standard issue types response
            foreach ($result['values'] as $issue_type) {
                $issue_types[] = [
                    'id' => $issue_type['id'],
                    'name' => $issue_type['name'],
                    'description' => $issue_type['description'] ?? '',
                    'iconUrl' => $issue_type['iconUrl'] ?? ''
                ];
            }
        } elseif (isset($result['issueTypes']) && is_array($result['issueTypes'])) {
            // Project info response with issue types
            foreach ($result['issueTypes'] as $issue_type) {
                $issue_types[] = [
                    'id' => $issue_type['id'],
                    'name' => $issue_type['name'],
                    'description' => $issue_type['description'] ?? '',
                    'iconUrl' => $issue_type['iconUrl'] ?? ''
                ];
            }
        }
        
        return $issue_types;
    }

    public function validate_issue_type($issue_type_name) {
        $available_types = $this->get_available_issue_types();
        
        if (is_wp_error($available_types)) {
            return $available_types;
        }
        
        foreach ($available_types as $type) {
            if (strcasecmp($type['name'], $issue_type_name) === 0) {
                return $type;
            }
        }
        
        return new WP_Error('invalid_issue_type', sprintf(
            __('Issue type "%s" not found in project. Available types: %s', 'wp-public-jira-reporter'),
            $issue_type_name,
            implode(', ', array_column($available_types, 'name'))
        ));
    }

    public function create_issue($data) {
        if (empty($this->project_key) || empty($this->issue_type)) {
             return new WP_Error('config_error', __('Default project key or issue type not configured.', 'wp-public-jira-reporter'));
        }

        // Build description with all the information
        $description = $data['description'];
        
        // Add submitter details to description if enabled
        if (wpjra_get_option('store_submitter_info', true) && (!empty($data['name']) || !empty($data['email']))) {
            $description .= "\n\n--- Submitted By ---";
            if (!empty($data['name'])) {
                $description .= "\nName: " . $data['name'];
            }
            if (!empty($data['email'])) {
                $description .= "\nEmail: " . $data['email'];
            }
        }

        // Add vertical and impact info to description
        if (!empty($data['vertical'])) {
            $description .= "\nVertical: " . $data['vertical'];
        }
        
        if (!empty($data['impact'])) {
            $impact_options = wpjra_get_impact_options();
            $impact_display = isset($impact_options[$data['impact']]) ? $impact_options[$data['impact']] : ucfirst(str_replace('_', ' ', $data['impact']));
            $description .= "\nImpact: " . $impact_display;
        }

        // Fixed: Use simple payload structure like the working example
        $payload = [
            'fields' => [
                'project'     => ['key' => $this->project_key],
                'summary'     => $data['title'],
                'description' => $description, // Use simple text instead of structured format
                'issuetype'   => ['name' => $this->issue_type], // Use name instead of ID for better compatibility
                'labels'      => array_merge($this->default_labels, $data['labels'] ?? []),
            ],
        ];
        
        // Try to add custom fields if configured
        if (!empty($this->custom_field_vertical_id) && !empty($data['vertical'])) {
            $payload['fields'][$this->custom_field_vertical_id] = $data['vertical'];
        }

        if (!empty($this->custom_field_impact_id) && !empty($data['impact'])) {
            $impact_options = wpjra_get_impact_options();
            $impact_display = isset($impact_options[$data['impact']]) ? $impact_options[$data['impact']] : ucfirst(str_replace('_', ' ', $data['impact']));
            $payload['fields'][$this->custom_field_impact_id] = $impact_display;
        }
        
        // Fixed: Use API v2 endpoint like the working example for better compatibility
        $result = $this->make_request('/rest/api/2/issue', 'POST', $payload);
        
        // If we get custom field errors, try again without the custom fields
        if (is_wp_error($result) && strpos($result->get_error_message(), 'customfield_') !== false) {
            // Remove custom fields and add the info to description instead
            $payload_without_custom_fields = $payload;
            
            // Remove any custom fields from the payload
            foreach ($payload_without_custom_fields['fields'] as $field_key => $field_value) {
                if (strpos($field_key, 'customfield_') === 0) {
                    unset($payload_without_custom_fields['fields'][$field_key]);
                }
            }
            
            // Add vertical and impact info to description
            $additional_info = '';
            if (!empty($data['vertical'])) {
                $additional_info .= "\nVertical: " . $data['vertical'];
            }
            if (!empty($data['impact'])) {
                $impact_options = wpjra_get_impact_options();
                $impact_display = isset($impact_options[$data['impact']]) ? $impact_options[$data['impact']] : ucfirst(str_replace('_', ' ', $data['impact']));
                $additional_info .= "\nImpact: " . $impact_display;
            }
            
            if (!empty($additional_info)) {
                $payload_without_custom_fields['fields']['description'] .= $additional_info;
            }
            
            // Try again without custom fields
            $result = $this->make_request('/rest/api/2/issue', 'POST', $payload_without_custom_fields);
        }
        
        return $result;
    }

    public function get_issue($issue_key) {
        // Example: expand=renderedFields,attachment,comment
        // 'renderedFields' to get HTML for description
        // 'attachment' for screenshots
        // 'comment.comments' for comments
        $endpoint = "/rest/api/3/issue/{$issue_key}?expand=renderedFields,attachment,comment";
        return $this->make_request($endpoint);
    }

    public function search_issues($jql, $max_results = 5) {
        $payload = [
            'jql'        => $jql,
            'maxResults' => $max_results,
            'fields'     => ['summary', 'key'] // We only need these for duplicate check display
        ];
        return $this->make_request('/rest/api/3/search', 'POST', $payload);
    }

    public function upload_attachment($issue_key, $file_path, $file_name) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Attachment file not found.', 'wp-public-jira-reporter'));
        }

        $endpoint = "/rest/api/3/issue/{$issue_key}/attachments";
        $file_data = file_get_contents($file_path);

        $boundary = '----WebKitFormBoundary' . wp_generate_password(24, false); // Random boundary

        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file_name) . '"' . "\r\n";
        // Mime type detection can be improved
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        $body .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
        $body .= $file_data . "\r\n";
        $body .= '--' . $boundary . '--' . "\r\n";
        
        $auth_header = $this->get_auth_header();
         if (!$auth_header) {
            return new WP_Error('config_error', __('Jira API credentials not configured.', 'wp-public-jira-reporter'));
        }

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization'   => $auth_header,
                'X-Atlassian-Token' => 'no-check', // Required for multipart/form-data
                'Content-Type'    => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body'    => $body,
            'timeout' => 60, // Longer timeout for file uploads
        ];
        
        $response = wp_remote_request($this->base_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code >= 200 && $response_code < 300) {
            return json_decode($response_body, true);
        } else {
            $decoded_body = json_decode($response_body, true);
            $error_message = __('Jira API Error during attachment upload', 'wp-public-jira-reporter');
            if (isset($decoded_body['errorMessages']) && !empty($decoded_body['errorMessages'])) {
                $error_message = implode(', ', $decoded_body['errorMessages']);
            }
            return new WP_Error('api_error_attachment', $error_message, ['status' => $response_code, 'body' => $decoded_body]);
        }
    }

    public function get_base_url() {
        return $this->base_url;
    }
} 