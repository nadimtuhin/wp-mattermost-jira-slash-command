<?php
/**
 * Admin interface class
 */
class WP_MM_Slash_Jira_Admin {
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_create_mm_jira_tables', array($this, 'ajax_create_tables'));
        add_action('wp_ajax_clear_mm_jira_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_export_mm_jira_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_import_mm_jira_settings', array($this, 'ajax_import_settings'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Mattermost Jira Integration',
            'MM Jira Integration',
            'manage_options',
            'wp-mm-slash-jira',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('wp_mm_slash_jira_options', 'wp_mm_slash_jira_jira_domain');
        register_setting('wp_mm_slash_jira_options', 'wp_mm_slash_jira_api_user_email');
        register_setting('wp_mm_slash_jira_options', 'wp_mm_slash_jira_api_key');
        register_setting('wp_mm_slash_jira_options', 'wp_mm_slash_jira_webhook_token');
        register_setting('wp_mm_slash_jira_options', 'wp_mm_slash_jira_enable_logging');
        register_setting('wp_mm_slash_jira_options', 'wp_mm_slash_jira_email_domain');
    }
    
    /**
     * Create database tables manually
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create mappings table
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
        
        return array(
            'mappings_table' => $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name,
            'logs_table' => $wpdb->get_var("SHOW TABLES LIKE '$logs_table_name'") === $logs_table_name
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_wp-mm-slash-jira') {
            return;
        }
        
        wp_enqueue_script('wp-mm-slash-jira-admin', WP_MM_SLASH_JIRA_PLUGIN_URL . 'assets/admin.js', array('jquery'), WP_MM_SLASH_JIRA_VERSION, true);
        wp_enqueue_style('wp-mm-slash-jira-admin', WP_MM_SLASH_JIRA_PLUGIN_URL . 'assets/admin.css', array(), WP_MM_SLASH_JIRA_VERSION);
        
        wp_localize_script('wp-mm-slash-jira-admin', 'wp_mm_slash_jira', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('jira/mattermost/slash/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajax_nonce' => wp_create_nonce('wp_mm_slash_jira_nonce')
        ));
    }
    
    public function admin_page() {
        // Get plugin settings for use in the page
        $jira_domain = get_option('wp_mm_slash_jira_jira_domain');
        $webhook_token = get_option('wp_mm_slash_jira_webhook_token');
        $enable_logging = get_option('wp_mm_slash_jira_enable_logging');
        
        // Get channel mappings for use in testing tab
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        $mappings = $wpdb->get_results("SELECT * FROM $table_name ORDER BY channel_name ASC");
        
        ?>
        <div class="wrap">
            <h1>Mattermost Jira Integration</h1>
            
            <div class="nav-tab-wrapper">
                <a href="#settings" class="nav-tab nav-tab-active">Settings</a>
                <a href="#mappings" class="nav-tab">Channel Mappings</a>
                <a href="#logs" class="nav-tab">Invocation Logs</a>
                <a href="#webhook" class="nav-tab">Webhook Info</a>
                <a href="#testing" class="nav-tab">Testing</a>
            </div>
            
            <div id="settings" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields('wp_mm_slash_jira_options'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Jira Domain</th>
                            <td>
                                <input type="text" name="wp_mm_slash_jira_jira_domain" 
                                       value="<?php echo esc_attr(get_option('wp_mm_slash_jira_jira_domain')); ?>" 
                                       class="regular-text" placeholder="your-domain.atlassian.net" />
                                <p class="description">Your Jira domain (e.g., your-domain.atlassian.net). <strong>Do not include https:// or http://</strong></p>
                                <p class="description"><strong>Examples:</strong> company.atlassian.net, myproject.atlassian.net</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Jira API User Email</th>
                            <td>
                                <input type="email" name="wp_mm_slash_jira_api_user_email" 
                                       value="<?php echo esc_attr(get_option('wp_mm_slash_jira_api_user_email')); ?>" 
                                       class="regular-text" placeholder="user@example.com" />
                                <p class="description">Your Jira account email address</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Jira API Key</th>
                            <td>
                                <input type="password" name="wp_mm_slash_jira_api_key" 
                                       value="<?php echo esc_attr(get_option('wp_mm_slash_jira_api_key')); ?>" 
                                       class="regular-text" id="jira_api_key" />
                                <p class="description">Your Jira API token (not the email:token format)</p>
                                <button type="button" class="button button-small" onclick="togglePasswordVisibility('jira_api_key')">Show/Hide</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Webhook Token</th>
                            <td>
                                <input type="password" name="wp_mm_slash_jira_webhook_token" 
                                       value="<?php echo esc_attr(get_option('wp_mm_slash_jira_webhook_token')); ?>" 
                                       class="regular-text" id="webhook_token" />
                                <p class="description">Token to verify webhook requests from Mattermost</p>
                                <button type="button" class="button button-small" onclick="togglePasswordVisibility('webhook_token')">Show/Hide</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Email Domain</th>
                            <td>
                                <input type="text" name="wp_mm_slash_jira_email_domain" 
                                       value="<?php echo esc_attr(get_option('wp_mm_slash_jira_email_domain')); ?>" 
                                       class="regular-text" placeholder="company.com" />
                                <p class="description">Your company's email domain for automatic reporter assignment</p>
                                <p class="description"><strong>Example:</strong> If set to "company.com", issues created by "john" will automatically assign reporter as "john@company.com"</p>
                                <p class="description"><strong>Note:</strong> This will automatically find the Jira user and set them as the reporter when creating issues</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enable Logging</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="wp_mm_slash_jira_enable_logging" 
                                           value="1" <?php checked(get_option('wp_mm_slash_jira_enable_logging'), '1'); ?> />
                                    Store invocation logs for debugging and monitoring
                                </label>
                                <p class="description">When enabled, all webhook requests and responses will be logged with full payload details.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
                
                <div class="settings-import-export">
                    <h3>Backup & Restore Settings</h3>
                    <p>Export your current settings to a file or import settings from a previously exported file.</p>
                    
                    <div class="export-section">
                        <h4>Export Settings</h4>
                        <p>Download a backup of your current configuration including Jira settings and channel mappings.</p>
                        <button type="button" id="export-settings" class="button button-secondary">📥 Export Settings</button>
                        <div id="export-result"></div>
                    </div>
                    
                    <div class="import-section">
                        <h4>Import Settings</h4>
                        <p>Restore settings from a previously exported backup file.</p>
                        <input type="file" id="import-file" accept=".json" style="display: none;" />
                        <button type="button" id="import-settings" class="button button-secondary">📤 Import Settings</button>
                        <div id="import-result"></div>
                    </div>
                    
                    <div class="import-export-notes">
                        <h4>Important Notes:</h4>
                        <ul>
                            <li><strong>Export includes:</strong> Jira domain, API credentials, webhook token, logging settings, and all channel mappings</li>
                            <li><strong>Security:</strong> API credentials are included in the export file - keep it secure!</li>
                            <li><strong>Backup:</strong> Importing will overwrite existing settings - export first if you want to keep current settings</li>
                            <li><strong>Format:</strong> Settings are exported as a JSON file</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div id="mappings" class="tab-content" style="display: none;">
                <h2>Channel to Jira Project Mappings</h2>
                <p>Map Mattermost channels to Jira projects for automatic issue creation.</p>
                
                <div class="add-mapping-form">
                    <h3>Add New Mapping</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Channel ID</th>
                            <td>
                                <input type="text" id="channel_id" class="regular-text" placeholder="Channel ID from Mattermost" />
                                <p class="description">The channel ID from Mattermost (e.g., fukxanjgjbnp7ng383at53k1sy)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Channel Name</th>
                            <td>
                                <input type="text" id="channel_name" class="regular-text" placeholder="Channel name" />
                                <p class="description">The channel name for display purposes</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Jira Project Key</th>
                            <td>
                                <input type="text" id="jira_project_key" class="regular-text" placeholder="PROJ" />
                                <p class="description">The Jira project key (e.g., PROJ, DEV, BUG)</p>
                            </td>
                        </tr>
                    </table>
                    <button type="button" id="add-mapping" class="button button-primary">Add Mapping</button>
                </div>
                
                <div class="mappings-list">
                    <h3>Current Mappings</h3>
                    <div id="mappings-table-container">
                        <p>Loading mappings...</p>
                    </div>
                </div>
                
                <div class="troubleshooting-section">
                    <h3>Troubleshooting</h3>
                    <p>If you're seeing "Error loading mappings: Forbidden" or other database errors, try the following:</p>
                    <ul>
                        <li>Make sure you're logged in as an administrator</li>
                        <li>Check if the database tables exist</li>
                        <li>Try creating the tables manually if they're missing</li>
                    </ul>
                    <button type="button" id="create-tables" class="button button-secondary">Create Database Tables</button>
                    <div id="create-tables-result"></div>
                </div>
            </div>
            
            <div id="logs" class="tab-content" style="display: none;">
                <h2>Invocation Logs</h2>
                <p>View and monitor all webhook invocations. Logging must be enabled in Settings to capture logs.</p>
                
                <?php
                $logger = new WP_MM_Slash_Jira_Logger();
                $page = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
                $filters = array();
                
                if (isset($_GET['filter_channel']) && !empty($_GET['filter_channel'])) {
                    $filters['channel_id'] = sanitize_text_field($_GET['filter_channel']);
                }
                if (isset($_GET['filter_user']) && !empty($_GET['filter_user'])) {
                    $filters['user_name'] = sanitize_text_field($_GET['filter_user']);
                }
                if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
                    $filters['status'] = sanitize_text_field($_GET['filter_status']);
                }
                
                $logs_data = $logger->get_logs($page, 20, $filters);
                ?>
                
                <div class="logs-filters">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="wp-mm-slash-jira">
                        <input type="hidden" name="log_page" value="1">
                        
                        <label>Channel ID: <input type="text" name="filter_channel" value="<?php echo esc_attr($_GET['filter_channel'] ?? ''); ?>" /></label>
                        <label>User: <input type="text" name="filter_user" value="<?php echo esc_attr($_GET['filter_user'] ?? ''); ?>" /></label>
                        <label>Status: 
                            <select name="filter_status">
                                <option value="">All</option>
                                <option value="success" <?php selected($_GET['filter_status'] ?? '', 'success'); ?>>Success</option>
                                <option value="error" <?php selected($_GET['filter_status'] ?? '', 'error'); ?>>Error</option>
                            </select>
                        </label>
                        <button type="submit" class="button">Filter</button>
                        <a href="?page=wp-mm-slash-jira#logs" class="button">Clear Filters</a>
                    </form>
                </div>
                
                <div class="logs-actions">
                    <h3>Log Management</h3>
                    <div class="clear-logs-section">
                        <p>Clear logs to free up database space and improve performance:</p>
                        
                        <div class="clear-logs-options">
                            <div class="clear-option">
                                <label>
                                    <input type="radio" name="clear_type" value="all" checked> 
                                    Clear all logs
                                </label>
                                <span class="description">Removes all log entries from the database</span>
                            </div>
                            
                            <div class="clear-option">
                                <label>
                                    <input type="radio" name="clear_type" value="old"> 
                                    Clear logs older than
                                </label>
                                <select id="clear_days" disabled>
                                    <option value="7">7 days</option>
                                    <option value="14">14 days</option>
                                    <option value="30" selected>30 days</option>
                                    <option value="60">60 days</option>
                                    <option value="90">90 days</option>
                                    <option value="180">180 days</option>
                                </select>
                                <span class="description">Removes logs older than the specified number of days</span>
                            </div>
                        </div>
                        
                        <div class="clear-logs-stats">
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'mm_jira_logs';
                            $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                            $old_logs_30 = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $table_name WHERE timestamp < %s",
                                date('Y-m-d H:i:s', strtotime('-30 days'))
                            ));
                            $old_logs_7 = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $table_name WHERE timestamp < %s",
                                date('Y-m-d H:i:s', strtotime('-7 days'))
                            ));
                            ?>
                            <p><strong>Current statistics:</strong></p>
                            <ul>
                                <li>Total logs: <strong><?php echo number_format($total_logs); ?></strong></li>
                                <li>Logs older than 7 days: <strong><?php echo number_format($old_logs_7); ?></strong></li>
                                <li>Logs older than 30 days: <strong><?php echo number_format($old_logs_30); ?></strong></li>
                            </ul>
                        </div>
                        
                        <button type="button" id="clear-logs-btn" class="button button-secondary">
                            🗑️ Clear Logs
                        </button>
                        <div id="clear-logs-result"></div>
                    </div>
                </div>
                
                <div class="logs-table-container">
                    <?php if (empty($logs_data['logs'])): ?>
                        <p>No logs found. <?php if (!$logger->is_logging_enabled()): ?>Enable logging in Settings to start capturing logs.<?php endif; ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Type</th>
                                    <th>Channel</th>
                                    <th>User</th>
                                    <th>Command</th>
                                    <th>Status</th>
                                    <th>Time (ms)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs_data['logs'] as $log): ?>
                                    <?php 
                                    // Check if this is a curl payload log
                                    $is_curl_log = false;
                                    $log_type = 'Webhook';
                                    try {
                                        $curl_payload = json_decode($log->request_payload, true);
                                        if ($curl_payload && isset($curl_payload['method']) && isset($curl_payload['url'])) {
                                            $is_curl_log = true;
                                            $log_type = 'Jira API';
                                        }
                                    } catch (Exception $e) {
                                        // Not a curl payload
                                    }
                                    ?>
                                    <tr class="<?php echo $is_curl_log ? 'curl-log' : ''; ?>">
                                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->timestamp))); ?></td>
                                        <td>
                                            <span class="log-type-<?php echo strtolower(str_replace(' ', '-', $log_type)); ?>">
                                                <?php echo esc_html($log_type); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($log->channel_name); ?></td>
                                        <td><?php echo esc_html($log->user_name); ?></td>
                                        <td><?php echo esc_html($log->command); ?></td>
                                        <td>
                                            <span class="status-<?php echo esc_attr($log->status); ?>">
                                                <?php echo esc_html(ucfirst($log->status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(round($log->execution_time * 1000, 2)); ?></td>
                                        <td>
                                            <button type="button" class="button button-small view-log" data-id="<?php echo esc_attr($log->id); ?>">View Details</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if ($logs_data['pages'] > 1): ?>
                            <div class="tablenav">
                                <div class="tablenav-pages">
                                    <?php
                                    $current_url = add_query_arg(array('page' => 'wp-mm-slash-jira', 'log_page' => $page), admin_url('options-general.php'));
                                    if ($page > 1): ?>
                                        <a href="<?php echo esc_url(add_query_arg('log_page', $page - 1, $current_url)); ?>" class="prev page-numbers">&laquo;</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $logs_data['pages']; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span class="page-numbers current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url(add_query_arg('log_page', $i, $current_url)); ?>" class="page-numbers"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $logs_data['pages']): ?>
                                        <a href="<?php echo esc_url(add_query_arg('log_page', $page + 1, $current_url)); ?>" class="next page-numbers">&raquo;</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Log Details Modal -->
                <div id="log-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3>Log Details</h3>
                        <div id="log-details"></div>
                    </div>
                </div>
            </div>
            
            <div id="webhook" class="tab-content" style="display: none;">
                <h2>Webhook Configuration</h2>
                <p>Configure this URL in your Mattermost slash command:</p>
                
                <div class="webhook-info">
                    <h3>Webhook URL</h3>
                    <code><?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?></code>
                    
                    <h3>Required Parameters</h3>
                    <p>The webhook expects the following parameters from Mattermost:</p>
                    <ul>
                        <li><strong>token</strong> - Webhook verification token</li>
                        <li><strong>channel_id</strong> - Mattermost channel ID</li>
                        <li><strong>channel_name</strong> - Mattermost channel name</li>
                        <li><strong>text</strong> - Command text (e.g., "create Fix login bug")</li>
                        <li><strong>user_name</strong> - Username who executed the command</li>
                    </ul>
                    
                    <h3>Usage Examples</h3>
                    <ul>
                        <li><code>/jira create Fix login bug</code> - Creates issue in mapped project</li>
                        <li><code>/jira bug Fix login issue</code> - Creates bug issue (shortcut)</li>
                        <li><code>/jira task Update documentation</code> - Creates task issue (shortcut)</li>
                        <li><code>/jira story Add new feature</code> - Creates story issue (shortcut)</li>
                        <li><code>/jira view PROJ-123</code> - View detailed issue information</li>
                        <li><code>/jira create Bug:Fix login bug</code> - Creates bug issue with specific type</li>
                        <li><code>/jira create PROJ Story:Add new feature</code> - Creates story issue with specific project</li>
                        <li><code>/jira create Task:Update documentation</code> - Creates task issue with specific type</li>
                        <li><code>/jira create PROJ-123 Add new feature</code> - Creates issue with specific project</li>
                        <li><code>/jira assign PROJ-123 developer@company.com</code> - Assigns issue to user by email</li>
                        <li><code>/jira find developer@company.com</code> - Search for a user by email address</li>
                        <li><code>/jira bind PROJ</code> - Binds current channel to PROJ project</li>
                        <li><code>/jira status</code> - Shows current project binding and statistics</li>
                        <li><code>/jira link</code> - Get links for creating new tasks</li>
                        <li><code>/jira board</code> - Get links to Jira boards and backlogs</li>
                        <li><code>/jira projects</code> - List all available Jira projects</li>
                        <li><code>/jira help</code> - Shows help message</li>
                    </ul>
                    
                    <h3>Quick Commands (Shortcuts)</h3>
                    <p>Use these shortcuts for faster issue creation:</p>
                    <ul>
                        <li><code>/jira bug Title</code> - Creates a bug issue in mapped project</li>
                        <li><code>/jira bug PROJECT-KEY Title</code> - Creates a bug issue in specific project</li>
                        <li><code>/jira task Title</code> - Creates a task issue in mapped project</li>
                        <li><code>/jira task PROJECT-KEY Title</code> - Creates a task issue in specific project</li>
                        <li><code>/jira story Title</code> - Creates a story issue in mapped project</li>
                        <li><code>/jira story PROJECT-KEY Title</code> - Creates a story issue in specific project</li>
                    </ul>
                    
                    <h3>View Issue Details</h3>
                    <p>View comprehensive information about any Jira issue:</p>
                    <ul>
                        <li><code>/jira view PROJ-123</code> - View issue details including status, description, comments, and more</li>
                    </ul>
                    <p><strong>Information displayed:</strong></p>
                    <ul>
                        <li>Summary, Type, Status, Priority</li>
                        <li>Assignee and Reporter</li>
                        <li>Story Points (if available)</li>
                        <li>Labels and Components</li>
                        <li>Description</li>
                        <li>Recent Comments (last 5)</li>
                        <li>Direct link to Jira</li>
                    </ul>
                    
                    <h3>Available Issue Types</h3>
                    <p>You can specify the issue type using the format <code>TYPE:Title</code>:</p>
                    <ul>
                        <li><strong>Task</strong> - General tasks and work items</li>
                        <li><strong>Bug</strong> - Software defects and issues</li>
                        <li><strong>Story</strong> - User stories and features</li>
                        <li><strong>Epic</strong> - Large initiatives and projects</li>
                        <li><strong>Subtask</strong> - Smaller tasks within larger issues</li>
                        <li><strong>Improvement</strong> - Enhancements and improvements</li>
                        <li><strong>New Feature</strong> - New functionality and features</li>
                    </ul>
                    
                    <h3>Automatic Reporter Assignment</h3>
                    <p>When the <strong>Email Domain</strong> setting is configured, the plugin will automatically:</p>
                    <ul>
                        <li>Search for Jira users using the pattern <code>username@emaildomain</code></li>
                        <li>Set the found user as the reporter when creating issues</li>
                        <li>Display the reporter information in the response</li>
                        <li>Fall back gracefully if the user is not found in Jira</li>
                    </ul>
                    <p><strong>Example:</strong> If email domain is set to "company.com" and user "john" creates an issue, the plugin will search for "john@company.com" in Jira and set that user as the reporter.</p>
                </div>
            </div>
            
            <div id="testing" class="tab-content" style="display: none;">
                <h2>Testing Tools</h2>
                <p>Test your Jira integration with these tools to ensure everything is working correctly.</p>
                
                <div class="testing-section">
                    <h3>🚀 Web-Based Test Interface</h3>
                    <p>Interactive chat-like interface to test slash commands in real-time:</p>
                    
                    <div class="test-interface-info">
                        <p><strong>Features:</strong></p>
                        <ul>
                            <li>🎯 Chat-like interface simulating Mattermost experience</li>
                            <li>⚡ Quick command buttons for common operations</li>
                            <li>🔧 Configurable channel settings</li>
                            <li>📊 Real-time status indicators</li>
                            <li>📝 Detailed response information</li>
                            <li>📱 Responsive design for all devices</li>
                        </ul>
                        
                        <div class="test-interface-link">
                            <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-interface.php'); ?>" target="_blank" class="button button-primary">
                                🧪 Open Test Interface
                            </a>
                            <p class="description">Interactive web-based testing interface. Opens in a new tab.</p>
                        </div>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>📋 Command Line Testing</h3>
                    <p>Use curl commands to test the webhook directly:</p>
                    
                    <div class="curl-examples">
                        <h4>Basic Issue Creation</h4>
                        <div class="code-block">
                            <code>curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Fix login bug" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"</code>
                        </div>
                        
                        <h4>Channel Binding</h4>
                        <div class="code-block">
                            <code>curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=bind PROJ" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"</code>
                        </div>
                        
                        <h4>Status Check</h4>
                        <div class="code-block">
                            <code>curl -X POST "<?php echo esc_url(rest_url('jira/mattermost/slash/jira')); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<?php echo esc_attr($webhook_token); ?>" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=status" \
  -d "user_name=<?php echo esc_attr(wp_get_current_user()->user_login); ?>"</code>
                        </div>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>🔍 Database Testing</h3>
                    <p>Check if your database tables are properly set up:</p>
                    
                    <div class="db-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-db.php'); ?>" target="_blank" class="button button-secondary">
                            🔍 Run Database Tests
                        </a>
                        <p class="description">Comprehensive database and configuration tests. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>🌐 Domain Format Testing</h3>
                    <p>Verify your Jira domain format is correct:</p>
                    
                    <div class="domain-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-domain.php'); ?>" target="_blank" class="button button-secondary">
                            🌐 Test Domain Format
                        </a>
                        <p class="description">Check if your Jira domain is in the correct format. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>📝 Log Details Testing</h3>
                    <p>Debug issues with log details loading:</p>
                    
                    <div class="logs-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-logs.php'); ?>" target="_blank" class="button button-secondary">
                            📝 Test Log Details
                        </a>
                        <p class="description">Debug log details endpoint and permissions. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>🔐 Nonce Testing</h3>
                    <p>Debug nonce generation and verification issues:</p>
                    
                    <div class="nonce-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-nonce.php'); ?>" target="_blank" class="button button-secondary">
                            🔐 Test Nonce Functionality
                        </a>
                        <p class="description">Test nonce generation, verification, and API authentication. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>🔌 REST API Testing</h3>
                    <p>Test REST API endpoints and authentication:</p>
                    
                    <div class="rest-api-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-rest-api.php'); ?>" target="_blank" class="button button-secondary">
                            🔌 Test REST API Endpoints
                        </a>
                        <p class="description">Test all REST API endpoints and authentication. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>👤 User Permissions Testing</h3>
                    <p>Test user permissions and authentication:</p>
                    
                    <div class="permissions-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-permissions.php'); ?>" target="_blank" class="button button-secondary">
                            👤 Test User Permissions
                        </a>
                        <p class="description">Test user authentication, roles, and capabilities. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>🔐 Authentication Testing</h3>
                    <p>Test authentication for API calls from admin interface:</p>
                    
                    <div class="auth-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-authentication.php'); ?>" target="_blank" class="button button-secondary">
                            🔐 Test Authentication
                        </a>
                        <p class="description">Test nonce generation, REST API authentication, and AJAX calls. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>🔧 Curl Commands Testing</h3>
                    <p>Test the new curl command functionality in log details:</p>
                    
                    <div class="curl-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-curl-commands.php'); ?>" target="_blank" class="button button-secondary">
                            🔧 Test Curl Commands
                        </a>
                        <p class="description">Demonstrate curl command generation and copy functionality. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>🎯 Task Type Functionality Testing</h3>
                    <p>Test the new task type feature in slash commands:</p>
                    
                    <div class="task-type-test-link">
                        <a href="<?php echo esc_url(plugin_dir_url(__FILE__) . '../tests/test-task-type.php'); ?>" target="_blank" class="button button-secondary">
                            🎯 Test Task Type Feature
                        </a>
                        <p class="description">Test the new task type functionality with various issue types. Opens in a new tab.</p>
                    </div>
                </div>
                
                <div class="testing-section">
                    <h3>📝 Testing Checklist</h3>
                    <p>Before testing, ensure all requirements are met:</p>
                    
                    <div class="testing-checklist">
                        <ul>
                            <li class="<?php echo $jira_domain ? 'check-ok' : 'check-error'; ?>">
                                ✅ Jira domain configured: <?php echo $jira_domain ? $jira_domain : 'Not set'; ?>
                            </li>
                            <li class="<?php echo $webhook_token ? 'check-ok' : 'check-error'; ?>">
                                ✅ Webhook token configured: <?php echo $webhook_token ? 'Set' : 'Not set'; ?>
                            </li>
                            <li class="<?php echo $enable_logging ? 'check-ok' : 'check-warning'; ?>">
                                ✅ Logging enabled: <?php echo $enable_logging ? 'Yes' : 'No (recommended for debugging)'; ?>
                            </li>
                            <li class="<?php echo !empty($mappings) ? 'check-ok' : 'check-warning'; ?>">
                                ✅ Channel mappings: <?php echo !empty($mappings) ? count($mappings) . ' configured' : 'None (can use specific project keys)'; ?>
                            </li>
                            <li class="check-ok">
                                ✅ User permissions: Administrator (<?php echo wp_get_current_user()->user_login; ?>)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $($(this).attr('href')).show();
                
                if ($(this).attr('href') === '#mappings') {
                    loadMappings();
                }
            });
            
            // Load mappings on page load if mappings tab is active
            if (window.location.hash === '#mappings') {
                $('.nav-tab[href="#mappings"]').click();
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for creating tables
     */
    public function ajax_create_tables() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_mm_slash_jira_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $result = $this->create_tables();
        
        wp_send_json($result);
    }
    
    /**
     * AJAX handler for clearing logs
     */
    public function ajax_clear_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_mm_slash_jira_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $clear_type = sanitize_text_field($_POST['clear_type']);
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_jira_logs';
        
        // Get count before clearing
        $count_before = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($clear_type === 'all') {
            // Clear all logs
            $result = $wpdb->query("DELETE FROM $table_name");
            $message = "All logs cleared successfully. Removed $count_before log entries.";
        } else {
            // Clear old logs
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $result = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE timestamp < %s", $cutoff_date));
            $count_after = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $removed_count = $count_before - $count_after;
            $message = "Logs older than $days days cleared successfully. Removed $removed_count log entries.";
        }
        
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        } else {
            wp_send_json_success($message);
        }
    }
    
    /**
     * AJAX handler for exporting settings
     */
    public function ajax_export_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_mm_slash_jira_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Collect all settings
        $settings = array(
            'version' => '1.0',
            'export_date' => current_time('mysql'),
            'wordpress_site' => get_site_url(),
            'plugin_version' => WP_MM_SLASH_JIRA_VERSION,
            'settings' => array(
                'jira_domain' => get_option('wp_mm_slash_jira_jira_domain'),
                'api_user_email' => get_option('wp_mm_slash_jira_api_user_email'),
                'api_key' => get_option('wp_mm_slash_jira_api_key'),
                'webhook_token' => get_option('wp_mm_slash_jira_webhook_token'),
                'enable_logging' => get_option('wp_mm_slash_jira_enable_logging')
            )
        );
        
        // Get channel mappings
        global $wpdb;
        $table_name = $wpdb->prefix . 'mm_jira_mappings';
        $mappings = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at ASC", ARRAY_A);
        $settings['mappings'] = $mappings;
        
        // Generate filename
        $filename = 'mm-jira-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        // Set headers for file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen(json_encode($settings)));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output the JSON
        echo json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * AJAX handler for importing settings
     */
    public function ajax_import_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_mm_slash_jira_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error occurred');
        }
        
        $file = $_FILES['import_file'];
        
        // Validate file type
        if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            wp_send_json_error('Invalid file type. Please upload a JSON file.');
        }
        
        // Read and decode the file
        $json_content = file_get_contents($file['tmp_name']);
        if (!$json_content) {
            wp_send_json_error('Could not read the uploaded file');
        }
        
        $import_data = json_decode($json_content, true);
        if (!$import_data || !is_array($import_data)) {
            wp_send_json_error('Invalid JSON format in the uploaded file');
        }
        
        // Validate required fields
        if (!isset($import_data['version']) || !isset($import_data['settings'])) {
            wp_send_json_error('Invalid import file format. Missing required fields.');
        }
        
        // Import settings
        $imported_count = 0;
        $errors = array();
        
        try {
            // Import WordPress options
            if (isset($import_data['settings'])) {
                $settings = $import_data['settings'];
                
                if (isset($settings['jira_domain'])) {
                    update_option('wp_mm_slash_jira_jira_domain', sanitize_text_field($settings['jira_domain']));
                    $imported_count++;
                }
                
                if (isset($settings['api_user_email'])) {
                    update_option('wp_mm_slash_jira_api_user_email', sanitize_email($settings['api_user_email']));
                    $imported_count++;
                }
                
                if (isset($settings['api_key'])) {
                    update_option('wp_mm_slash_jira_api_key', sanitize_text_field($settings['api_key']));
                    $imported_count++;
                }
                
                if (isset($settings['webhook_token'])) {
                    update_option('wp_mm_slash_jira_webhook_token', sanitize_text_field($settings['webhook_token']));
                    $imported_count++;
                }
                
                if (isset($settings['enable_logging'])) {
                    update_option('wp_mm_slash_jira_enable_logging', sanitize_text_field($settings['enable_logging']));
                    $imported_count++;
                }
            }
            
            // Import channel mappings
            if (isset($import_data['mappings']) && is_array($import_data['mappings'])) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'mm_jira_mappings';
                
                // Clear existing mappings
                $wpdb->query("DELETE FROM $table_name");
                
                // Import new mappings
                foreach ($import_data['mappings'] as $mapping) {
                    if (isset($mapping['channel_id']) && isset($mapping['channel_name']) && isset($mapping['jira_project_key'])) {
                        $result = $wpdb->insert(
                            $table_name,
                            array(
                                'channel_id' => sanitize_text_field($mapping['channel_id']),
                                'channel_name' => sanitize_text_field($mapping['channel_name']),
                                'jira_project_key' => sanitize_text_field($mapping['jira_project_key']),
                                'created_at' => isset($mapping['created_at']) ? $mapping['created_at'] : current_time('mysql')
                            ),
                            array('%s', '%s', '%s', '%s')
                        );
                        
                        if ($result !== false) {
                            $imported_count++;
                        } else {
                            $errors[] = "Failed to import mapping for channel: " . $mapping['channel_name'];
                        }
                    }
                }
            }
            
            $message = "Settings imported successfully! Imported $imported_count items.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }
            
            wp_send_json_success($message);
            
        } catch (Exception $e) {
            wp_send_json_error('Import failed: ' . $e->getMessage());
        }
    }
} 