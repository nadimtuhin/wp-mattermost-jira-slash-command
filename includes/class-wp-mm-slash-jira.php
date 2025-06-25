<?php
/**
 * Main plugin class
 */
class WP_MM_Slash_Jira {
    
    private $admin;
    private $api;
    
    public function init() {
        // Initialize admin interface
        if (is_admin()) {
            $this->admin = new WP_MM_Slash_Jira_Admin();
            $this->admin->init();
        }
        
        // Initialize API handler
        $this->api = new WP_MM_Slash_Jira_API();
        $this->api->init();
        
        // Add REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    public function register_rest_routes() {
        register_rest_route('jira/mattermost/slash', '/jira', array(
            'methods' => 'POST',
            'callback' => array($this->api, 'handle_slash_command'),
            'permission_callback' => array($this->api, 'verify_webhook'),
        ));
        
        register_rest_route('jira/mattermost/slash', '/mappings', array(
            'methods' => 'GET',
            'callback' => array($this->api, 'get_mappings'),
            'permission_callback' => array($this->api, 'check_admin_permissions'),
        ));
        
        register_rest_route('jira/mattermost/slash', '/mappings', array(
            'methods' => 'POST',
            'callback' => array($this->api, 'create_mapping'),
            'permission_callback' => array($this->api, 'check_admin_permissions'),
        ));
        
        register_rest_route('jira/mattermost/slash', '/mappings/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this->api, 'delete_mapping'),
            'permission_callback' => array($this->api, 'check_admin_permissions'),
        ));
        
        register_rest_route('jira/mattermost/slash', '/logs/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this->api, 'get_log_details'),
            'permission_callback' => array($this->api, 'check_admin_permissions'),
        ));
    }
}
