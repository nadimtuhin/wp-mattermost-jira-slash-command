<?php
/**
 * Plugin Name: WP Mattermost Jira Slash Command
 * Plugin URI: https://github.com/nadimtuhin/wp-mattermost-jira-slash-command
 * Description: WordPress plugin to handle Mattermost slash commands for Jira integration
 * Version: 1.1.0
 * Author: Nadim Tuhin
 * License: GPL v2 or later
 * Text Domain: wp-mm-slash-jira
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_MM_SLASH_JIRA_VERSION', '1.0.0');
define('WP_MM_SLASH_JIRA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_MM_SLASH_JIRA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_MM_SLASH_JIRA_PLUGIN_DIR . 'includes/class-wp-mm-slash-jira.php';
require_once WP_MM_SLASH_JIRA_PLUGIN_DIR . 'includes/class-wp-mm-slash-jira-admin.php';
require_once WP_MM_SLASH_JIRA_PLUGIN_DIR . 'includes/class-wp-mm-slash-jira-api.php';
require_once WP_MM_SLASH_JIRA_PLUGIN_DIR . 'includes/class-wp-mm-slash-jira-logger.php';

// Initialize the plugin
function wp_mm_slash_jira_init() {
    $plugin = new WP_MM_Slash_Jira();
    $plugin->init();
}
add_action('plugins_loaded', 'wp_mm_slash_jira_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_mm_slash_jira_activate');
function wp_mm_slash_jira_activate() {
    // Create database tables
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_name = $wpdb->prefix . 'mm_jira_mappings';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        channel_id varchar(255) NOT NULL,
        channel_name varchar(255) NOT NULL,
        jira_project_key varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY channel_id (channel_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add default options
    add_option('wp_mm_slash_jira_jira_domain', '');
    add_option('wp_mm_slash_jira_api_key', '');
    add_option('wp_mm_slash_jira_webhook_token', '');
    add_option('wp_mm_slash_jira_enable_logging', '0');
    add_option('wp_mm_slash_jira_email_domain', '');
    
    // Create logs table
    $logs_table_name = $wpdb->prefix . 'mm_jira_logs';
    
    $logs_sql = "CREATE TABLE $logs_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        channel_id varchar(255) NOT NULL,
        channel_name varchar(255) NOT NULL,
        user_name varchar(255) NOT NULL,
        command text NOT NULL,
        request_payload longtext NOT NULL,
        response_payload longtext,
        response_code int(3),
        execution_time float,
        status varchar(50) DEFAULT 'success',
        error_message text,
        PRIMARY KEY (id),
        KEY timestamp (timestamp),
        KEY channel_id (channel_id),
        KEY status (status)
    ) $charset_collate;";
    
    dbDelta($logs_sql);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_mm_slash_jira_deactivate');
function wp_mm_slash_jira_deactivate() {
    // Clean up if needed
} 