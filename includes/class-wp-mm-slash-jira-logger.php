<?php
/**
 * Logging class for Jira integration
 */
class WP_MM_Slash_Jira_Logger {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mm_jira_logs';
    }
    
    /**
     * Check if logging is enabled
     */
    public function is_logging_enabled() {
        return get_option('wp_mm_slash_jira_enable_logging', '0') === '1';
    }
    
    /**
     * Log a webhook invocation
     */
    public function log_invocation($channel_id, $channel_name, $user_name, $command, $request_payload, $response_payload = null, $response_code = null, $execution_time = null, $status = 'success', $error_message = null) {
        if (!$this->is_logging_enabled()) {
            return;
        }
        
        global $wpdb;
        
        $data = array(
            'channel_id' => $channel_id,
            'channel_name' => $channel_name,
            'user_name' => $user_name,
            'command' => $command,
            'request_payload' => is_array($request_payload) ? json_encode($request_payload, JSON_PRETTY_PRINT) : $request_payload,
            'response_payload' => is_array($response_payload) ? json_encode($response_payload, JSON_PRETTY_PRINT) : $response_payload,
            'response_code' => $response_code,
            'execution_time' => $execution_time,
            'status' => $status,
            'error_message' => $error_message
        );
        
        $wpdb->insert($this->table_name, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs with pagination
     */
    public function get_logs($page = 1, $per_page = 20, $filters = array()) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $where_clauses = array();
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['channel_id'])) {
            $where_clauses[] = 'channel_id = %s';
            $where_values[] = $filters['channel_id'];
        }
        
        if (!empty($filters['user_name'])) {
            $where_clauses[] = 'user_name LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($filters['user_name']) . '%';
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total = $wpdb->get_var($count_sql);
        
        // Get logs
        $sql = "SELECT * FROM {$this->table_name} {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        $sql = $wpdb->prepare($sql, $where_values);
        
        $logs = $wpdb->get_results($sql);
        
        return array(
            'logs' => $logs,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        );
    }
    
    /**
     * Get a single log entry
     */
    public function get_log($id) {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            return false;
        }
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
    }
    
    /**
     * Delete old logs
     */
    public function cleanup_old_logs($days = 30) {
        global $wpdb;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_name} WHERE timestamp < %s", $cutoff_date));
    }
    
    /**
     * Get log statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total logs
        $stats['total_logs'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Logs by status
        $stats['by_status'] = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status");
        
        // Logs by channel
        $stats['by_channel'] = $wpdb->get_results("SELECT channel_name, COUNT(*) as count FROM {$this->table_name} GROUP BY channel_name ORDER BY count DESC LIMIT 10");
        
        // Logs by user
        $stats['by_user'] = $wpdb->get_results("SELECT user_name, COUNT(*) as count FROM {$this->table_name} GROUP BY user_name ORDER BY count DESC LIMIT 10");
        
        // Recent activity (last 7 days)
        $stats['recent_activity'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE timestamp >= %s",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        return $stats;
    }
    
    /**
     * Format payload for display
     */
    public function format_payload($payload) {
        if (empty($payload)) {
            return '';
        }
        
        $decoded = json_decode($payload, true);
        if ($decoded !== null) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        
        return $payload;
    }
    
    /**
     * Log a Jira API curl request and response
     */
    public function log_jira_curl($method, $url, $request_headers, $request_body, $response_code, $response_headers, $response_body, $execution_time = null, $status = 'success', $error_message = null) {
        if (!$this->is_logging_enabled()) {
            return;
        }
        
        global $wpdb;
        
        // Create a structured payload for logging
        $curl_payload = array(
            'method' => $method,
            'url' => $url,
            'request' => array(
                'headers' => $request_headers,
                'body' => $request_body
            ),
            'response' => array(
                'code' => $response_code,
                'headers' => $response_headers,
                'body' => $response_body
            ),
            'execution_time' => $execution_time,
            'status' => $status,
            'error_message' => $error_message
        );
        
        $data = array(
            'channel_id' => 'jira-api',
            'channel_name' => 'Jira API',
            'user_name' => 'system',
            'command' => "Jira {$method} Request",
            'request_payload' => json_encode($curl_payload, JSON_PRETTY_PRINT),
            'response_payload' => null,
            'response_code' => $response_code,
            'execution_time' => $execution_time,
            'status' => $status,
            'error_message' => $error_message
        );
        
        $wpdb->insert($this->table_name, $data);
        
        return $wpdb->insert_id;
    }
} 