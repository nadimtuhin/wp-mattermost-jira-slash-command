<?php
/**
 * Uninstall script for WP Mattermost Jira Slash Command
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('wp_mm_slash_jira_jira_domain');
delete_option('wp_mm_slash_jira_api_key');
delete_option('wp_mm_slash_jira_webhook_token');
delete_option('wp_mm_slash_jira_enable_logging');

// Drop custom tables
global $wpdb;
$table_name = $wpdb->prefix . 'mm_jira_mappings';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$logs_table_name = $wpdb->prefix . 'mm_jira_logs';
$wpdb->query("DROP TABLE IF EXISTS $logs_table_name"); 