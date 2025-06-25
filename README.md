# WP Mattermost Jira Slash Command

A WordPress plugin that integrates Mattermost slash commands with Jira for creating issues directly from chat channels.

## Features

- **Slash Command Integration**: Handle `/jira` commands from Mattermost
- **Channel-Project Mapping**: Automatically map Mattermost channels to Jira projects
- **Flexible Project Keys**: Support both automatic mapping and manual project key specification
- **Admin Interface**: Easy management of settings and channel mappings
- **Webhook Security**: Token-based verification for secure webhook handling
- **Invocation Logging**: Comprehensive logging of all webhook requests and responses for debugging

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-mm-slash-jira/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > MM Jira Integration to configure the plugin

## Configuration

### 1. Jira Settings

1. **Jira Domain**: Your Jira domain (e.g., `your-domain.atlassian.net`)
2. **Jira API Key**: Your Jira API key in format `email:api_token`
3. **Webhook Token**: A secure token to verify webhook requests from Mattermost
4. **Enable Logging**: Check this option to store detailed invocation logs for debugging and monitoring

### 2. Channel Mappings

Map Mattermost channels to Jira projects:

1. Go to the "Channel Mappings" tab
2. Add new mappings with:
   - **Channel ID**: The Mattermost channel ID
   - **Channel Name**: Display name for the channel
   - **Jira Project Key**: The Jira project key (e.g., PROJ, DEV, BUG)

### 3. Invocation Logs

Monitor and debug webhook invocations:

1. Go to the "Invocation Logs" tab
2. View all webhook requests and responses
3. Filter logs by channel, user, or status
4. Click "View Details" to see full request/response payloads
5. Logs include execution time, status, and error messages

### 4. Mattermost Configuration

Configure the slash command in Mattermost:

1. Go to System Console > Integrations > Slash Commands
2. Create a new slash command with:
   - **Command**: `/jira`
   - **Request URL**: `https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira`
   - **Request Method**: POST
   - **Response Username**: Jira Bot
   - **Response Icon**: (Optional) Jira icon URL
   - **Autocomplete**: Enabled
   - **Autocomplete Description**: Create Jira issues from chat
   - **Autocomplete Hint**: `create [PROJECT-KEY-ISSUE-NUMBER] Title`

## Usage

### Basic Commands

- `/jira create Fix login bug` - Creates issue in mapped project
- `/jira bug Fix login issue` - Creates bug issue (shortcut)
- `/jira task Update documentation` - Creates task issue (shortcut)
- `/jira story Add new feature` - Creates story issue (shortcut)
- `/jira view PROJ-123` - View detailed issue information
- `/jira create Bug:Fix login bug` - Creates bug issue with specific type
- `/jira create PROJ Story:Add new feature` - Creates story issue with specific project
- `/jira create Task:Update documentation` - Creates task issue with specific type
- `/jira create PROJ-123 Add new feature` - Creates issue with specific project
- `/jira assign PROJ-123 developer@company.com` - Assigns issue to user by email
- `/jira bind PROJ` - Binds current channel to Jira project
- `/jira status` - Shows current project binding and statistics
- `/jira link` - Get links for creating new tasks
- `/jira board` - Get links to Jira boards and backlogs
- `/jira help` - Shows help message

### Quick Commands (Shortcuts)

Use these shortcuts for faster issue creation:

- `/jira bug Title` - Creates a bug issue in mapped project
- `/jira bug PROJECT-KEY Title` - Creates a bug issue in specific project
- `/jira task Title` - Creates a task issue in mapped project
- `/jira task PROJECT-KEY Title` - Creates a task issue in specific project
- `/jira story Title` - Creates a story issue in mapped project
- `/jira story PROJECT-KEY Title` - Creates a story issue in specific project

### View Issue Details

View comprehensive information about any Jira issue:

- `/jira view PROJ-123` - View issue details including status, description, comments, and more

**Information displayed:**
- Summary, Type, Status, Priority
- Assignee and Reporter
- Story Points (if available)
- Labels and Components
- Description
- Recent Comments (last 5)
- Direct link to Jira

### Command Examples

```
/jira create Fix login bug
/jira bug Fix login issue
/jira task Update documentation
/jira story Add new feature
/jira view PROJ-123
/jira create Bug:Fix login bug
/jira create PROJ Story:Add new feature
/jira create Task:Update documentation
/jira create PROJ-456 Add user authentication
/jira create BUG-789 Database connection timeout
/jira assign PROJ-123 developer@company.com
/jira bind PROJ
/jira status
/jira link
/jira board
/jira help
```

### Issue Types

You can specify the issue type using the format `TYPE:Title`:

- **Task** - General tasks and work items
- **Bug** - Software defects and issues  
- **Story** - User stories and features
- **Epic** - Large initiatives and projects
- **Subtask** - Smaller tasks within larger issues
- **Improvement** - Enhancements and improvements
- **New Feature** - New functionality and features

If no issue type is specified, it will be determined automatically based on the channel name or default to 'Task'.

### Response Format

Successful issue creation returns:
```
✅ Issue created successfully!

**Issue:** PROJ-123
**Title:** Fix login bug
**Created by:** @alan
**Project:** PROJ
**Type:** Bug

[View in Jira](https://your-domain.atlassian.net/browse/PROJ-123)
```

## API Endpoints

The plugin provides the following REST API endpoints:

- `POST /wp-json/jira/mattermost/slash/jira` - Handle slash commands
- `GET /wp-json/jira/mattermost/slash/mappings` - Get channel mappings
- `POST /wp-json/jira/mattermost/slash/mappings` - Add new mapping
- `DELETE /wp-json/jira/mattermost/slash/mappings/{id}` - Delete mapping

## Webhook Parameters

The webhook expects these parameters from Mattermost:

- `token` - Webhook verification token
- `channel_id` - Mattermost channel ID
- `channel_name` - Mattermost channel name
- `text` - Command text (e.g., "create Fix login bug")
- `user_name` - Username who executed the command

## Security

- All webhook requests are verified using the configured token
- Admin endpoints require WordPress admin capabilities
- Jira API credentials are stored securely in WordPress options

## Troubleshooting

### Common Issues

1. **"No Jira project mapped to this channel"**
   - Add a channel mapping in the admin interface
   - Or specify a project key in the command

2. **"Jira configuration not set up"**
   - Configure Jira domain and API key in settings

3. **"Failed to create issue"**
   - Check Jira API credentials
   - Verify project key exists in Jira
   - Check Jira API permissions

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Development

### File Structure

```
wp-mm-slash-jira/
├── wp-mm-slash-jira.php          # Main plugin file
├── includes/
│   ├── class-wp-mm-slash-jira.php        # Main plugin class
│   ├── class-wp-mm-slash-jira-admin.php  # Admin interface
│   ├── class-wp-mm-slash-jira-api.php    # API handler
│   └── class-wp-mm-slash-jira-logger.php # Logging functionality
├── assets/
│   ├── admin.js                   # Admin JavaScript
│   └── admin.css                  # Admin styles
└── README.md                      # This file
```

### Database Tables

The plugin creates a table `wp_mm_jira_mappings` with:
- `id` - Primary key
- `channel_id` - Mattermost channel ID
- `channel_name` - Channel display name
- `jira_project_key` - Jira project key
- `created_at` - Timestamp

The plugin also creates a table `wp_mm_jira_logs` with:
- `id` - Primary key
- `timestamp` - When the request was made
- `channel_id` - Mattermost channel ID
- `channel_name` - Channel display name
- `user_name` - Username who executed the command
- `command` - The slash command text
- `request_payload` - Full request payload (JSON)
- `response_payload` - Full response payload (JSON)
- `response_code` - HTTP response code
- `execution_time` - Request execution time in seconds
- `status` - Success or error status
- `error_message` - Error message if applicable

## License

GPL v2 or later

## Support

For issues and feature requests, please create an issue in the plugin repository.

## Testing

### Test Curl Commands

Use these curl commands to test the webhook functionality. Replace the placeholders with your actual values:

#### 1. Test Basic Issue Creation

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Fix login bug" \
  -d "user_name=alan"
```

#### 2. Test Issue Creation with Specific Project

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create PROJ-123 Add new feature" \
  -d "user_name=alan"
```

#### 3. Test Issue Creation with Task Type

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create Bug:Fix login issue" \
  -d "user_name=alan"
```

#### 4. Test Issue Creation with Project and Task Type

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=create PROJ Story:Add new feature" \
  -d "user_name=alan"
```

#### 5. Test Shortcut Commands

```bash
# Test bug shortcut
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=bug Fix login issue" \
  -d "user_name=alan"

# Test task shortcut
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=task Update documentation" \
  -d "user_name=alan"

# Test story shortcut
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=story Add new feature" \
  -d "user_name=alan"

# Test shortcut with project key
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=bug PROJ Fix login issue" \
  -d "user_name=alan"
```

#### 6. Test View Issue Details

```bash
# Test viewing issue details
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=view PROJ-123" \
  -d "user_name=alan"

# Test viewing bug issue
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=view BUG-456" \
  -d "user_name=alan"

# Test viewing story issue
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=view STORY-789" \
  -d "user_name=alan"
```

#### 7. Test Issue Assignment

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=assign PROJ-123 developer@company.com" \
  -d "user_name=alan"
```

#### 8. Test Channel Binding

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=bind PROJ" \
  -d "user_name=alan"
```

#### 9. Test Status Check

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=status" \
  -d "user_name=alan"
```

#### 10. Test Get Links

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=link" \
  -d "user_name=alan"
```

#### 11. Test Board Links

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=board" \
  -d "user_name=alan"
```

#### 12. Test Help Command

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/jira" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=your_webhook_token" \
  -d "channel_id=fukxanjgjbnp7ng383at53k1sy" \
  -d "channel_name=general" \
  -d "text=help" \
  -d "user_name=alan"
```

### Test Admin API Endpoints

#### 13. Test Get Mappings (Admin Only)

```bash
curl -X GET "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/mappings" \
  -H "X-WP-Nonce: your_nonce_here" \
  -H "Authorization: Bearer your_auth_token"
```

#### 14. Test Add Mapping (Admin Only)

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/mappings" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: your_nonce_here" \
  -d '{
    "channel_id": "fukxanjgjbnp7ng383at53k1sy",
    "channel_name": "general",
    "jira_project_key": "PROJ"
  }'
```

#### 15. Test Delete Mapping (Admin Only)

```bash
curl -X DELETE "https://your-wordpress-site.com/wp-json/jira/mattermost/slash/mappings/1" \
  -H "X-WP-Nonce: your_nonce_here"
```

### Test Script

You can also use the included `test-webhook.php` script for quick testing:

```bash
php test-webhook.php
```

### Web-Based Test Interface

For a more interactive testing experience, use the web-based test interface:

1. **Access the interface**: Navigate to `wp-content/plugins/wp-mm-slash-jira/test-interface.php` in your browser
2. **Login**: You must be logged in as a WordPress administrator
3. **Configure settings**: Set your channel ID, channel name, and username
4. **Test commands**: Use the quick command buttons or type commands manually
5. **View responses**: See real-time responses in a chat-like interface

**Features:**
- 🎯 **Chat-like interface** - Simulates Mattermost experience
- ⚡ **Quick commands** - One-click testing of common commands
- 🔧 **Configurable settings** - Test different channels and users
- 📊 **Status indicators** - Shows plugin configuration status
- 📝 **Response details** - View full API responses
- 📱 **Responsive design** - Works on desktop and mobile

**Usage:**
1. Open the test interface in your browser
2. Configure channel settings on the left sidebar
3. Click quick commands or type custom commands
4. View responses in the chat area
5. Click "Show Response Details" to see full API data

**Example workflow:**
1. Set channel name to "general"
2. Click "bind PROJ" to bind the channel
3. Click "create Fix login bug" to create an issue
4. Click "status" to check the binding
5. View all responses in the chat interface

### Expected Responses

#### Successful Issue Creation
```json
{
  "response_type": "in_channel",
  "text": "✅ Issue created successfully!\n\n**Issue:** PROJ-123\n**Title:** Fix login bug\n**Created by:** @alan\n**Project:** PROJ\n\n[View in Jira](https://your-domain.atlassian.net/browse/PROJ-123)"
}
```

#### Error Response
```json
{
  "response_type": "ephemeral",
  "text": "❌ No Jira project mapped to this channel. Please add a mapping or specify a project key."
}
```

### Testing Checklist

Before testing, ensure:

1. ✅ Plugin is activated
2. ✅ Jira domain and API key are configured
3. ✅ Webhook token is set
4. ✅ Channel mappings are added (or use specific project keys)
5. ✅ WordPress debug mode is enabled for detailed error messages
6. ✅ Logging is enabled to capture test requests

### Troubleshooting Test Issues

1. **403 Forbidden**: Check webhook token and WordPress permissions
2. **404 Not Found**: Verify the REST API endpoint URL
3. **500 Internal Server Error**: Check WordPress error logs
4. **Jira API Errors**: Verify Jira credentials and project keys

## Security 