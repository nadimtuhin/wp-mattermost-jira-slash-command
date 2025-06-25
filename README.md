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
4. **Email Domain**: Your company's email domain for automatic reporter assignment (e.g., `company.com`)
5. **Enable Logging**: Check this option to store detailed invocation logs for debugging and monitoring

**Email Domain Feature**: When configured, the plugin will automatically search for Jira users using the pattern `username@emaildomain` and set them as the reporter when creating issues. For example, if the email domain is set to "company.com" and user "john" creates an issue, the plugin will search for "john@company.com" in Jira and set that user as the reporter.

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

## Project Management

### Binding Channels to Projects

There are two ways to associate Mattermost channels with Jira projects:

#### Method 1: Admin Interface (Recommended)
1. Go to **Settings > MM Jira Integration** in WordPress admin
2. Navigate to the **"Channel Mappings"** tab
3. Add new mappings with:
   - **Channel ID**: The Mattermost channel ID (e.g., `fukxanjgjbnp7ng383at53k1sy`)
   - **Channel Name**: Display name for the channel (e.g., `general`)
   - **Jira Project Key**: The Jira project key (e.g., `PROJ`, `DEV`, `BUG`)

#### Method 2: Channel Binding Command
Use the `/jira bind` command directly in any Mattermost channel:

```
/jira bind PROJECT-KEY
```

**Examples:**
- `/jira bind PROJ` - Binds current channel to PROJ project
- `/jira bind DEV` - Binds current channel to DEV project
- `/jira bind BUG` - Binds current channel to BUG project

**Benefits of Channel Binding:**
- Create issues without specifying project key: `/jira create Fix login bug`
- Use quick shortcuts: `/jira bug Fix login issue`, `/jira task Update docs`
- Get project-specific links: `/jira link`, `/jira board`
- View channel statistics: `/jira status`

### Unbinding Channels from Projects

To remove a channel's project binding:

```
/jira unbind
```

**What happens when you unbind:**
- The channel is no longer mapped to any Jira project
- You must specify project keys in all commands: `/jira create PROJ Title`
- Quick shortcuts will no longer work without project keys
- Use `/jira bind PROJECT-KEY` to bind to a different project

**Example:**
- `/jira unbind` - Removes current channel's project binding
- After unbinding, use `/jira create PROJ Fix bug` instead of `/jira create Fix bug`

### Viewing Available Projects

To see all available Jira projects and their keys:

```
/jira projects
```

This command will:
- List all projects in your Jira instance
- Group projects alphabetically for easy browsing
- Show project keys, names, and direct links to Jira
- Provide instructions for binding and creating issues

**Example Output:**
```
📋 **Available Jira Projects**

**Total Projects:** 15

**A**
• **API** - [API Development](https://your-domain.atlassian.net/browse/API)
• **APP** - [Application Development](https://your-domain.atlassian.net/browse/APP)

**B**
• **BUG** - [Bug Tracking](https://your-domain.atlassian.net/browse/BUG)

**D**
• **DEV** - [Development](https://your-domain.atlassian.net/browse/DEV)

**T**
• **TEST** - [Testing](https://your-domain.atlassian.net/browse/TEST)

**To bind this channel to a project:**
• `/jira bind PROJECT-KEY` - Replace PROJECT-KEY with one of the keys above

**To create issues in a specific project:**
• `/jira create PROJECT-KEY Title` - Create issue in specific project
• `/jira bug PROJECT-KEY Title` - Create bug in specific project
• `/jira task PROJECT-KEY Title` - Create task in specific project
• `/jira story PROJECT-KEY Title` - Create story in specific project
```

### Checking Channel Status

To see the current project binding and channel statistics:

```
/jira status
```

This will show:
- Current project binding (if any)
- When the binding was created
- Total issues created in this channel
- Recent activity statistics
- Available commands for the current setup

### Creating Issues Without Binding

You can create issues in specific projects without binding the channel:

```
/jira create PROJECT-KEY Title
/jira create PROJ-123 Title
```

**Examples:**
- `/jira create PROJ Fix login bug` - Creates issue in PROJ project
- `/jira create DEV-456 Add new feature` - Creates issue in DEV project
- `/jira bug BUG Fix critical issue` - Creates bug in BUG project
- `/jira task TEST Update test cases` - Creates task in TEST project

### Project Key Requirements

Jira project keys must:
- Contain only uppercase letters and numbers (A-Z, 0-9)
- Be 10 characters or less
- Exist in your Jira instance

**Valid examples:** `PROJ`, `DEV`, `BUG`, `TEST`, `API123`
**Invalid examples:** `project` (lowercase), `PROJECT-KEY` (hyphens), `VERYLONGPROJECTKEY` (too long)

### Quick Reference: Project Management Commands

| Command | Description | Example |
|---------|-------------|---------|
| `/jira projects` | List all available Jira projects | `/jira projects` |
| `/jira bind PROJECT-KEY` | Bind current channel to a project | `/jira bind PROJ` |
| `/jira unbind` | Remove current channel's project binding | `/jira unbind` |
| `/jira status` | Check current project binding and stats | `/jira status` |
| `/jira create PROJECT-KEY Title` | Create issue in specific project | `/jira create PROJ Fix bug` |
| `/jira create Title` | Create issue in bound project | `/jira create Fix bug` |
| `/jira bug PROJECT-KEY Title` | Create bug in specific project | `/jira bug PROJ Fix bug` |
| `/jira task PROJECT-KEY Title` | Create task in specific project | `/jira task PROJ Update docs` |
| `/jira story PROJECT-KEY Title` | Create story in specific project | `/jira story PROJ Add feature` |

**Workflow Examples:**
1. **First time setup**: `/jira projects` → `/jira bind PROJ` → `/jira create Fix bug`
2. **Quick issue creation**: `/jira bug Fix login issue` (if channel is bound)
3. **One-off issue**: `/jira create DEV-123 Add feature` (no binding needed)
4. **Change project binding**: `/jira unbind` → `/jira bind DEV` → `/jira create Fix bug`

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
- `/jira find developer@company.com` - Search for a user by email address
- `/jira bind PROJ` - Binds current channel to Jira project
- `/jira unbind` - Removes current channel's project binding
- `/jira status` - Shows current project binding and statistics
- `/jira link` - Get links for creating new tasks
- `/jira board` - Get links to Jira boards and backlogs
- `/jira projects` - List all available Jira projects
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
/jira find developer@company.com
/jira bind PROJ
/jira unbind
/jira status
/jira link
/jira board
/jira projects
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
**Reporter:** John Developer (john@company.com)

[View in Jira](https://your-domain.atlassian.net/browse/PROJ-123)
```

**Note:** The Reporter field will only appear when the Email Domain setting is configured and a matching Jira user is found.

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
   
   This error occurs when you try to create an issue without specifying a project key and the current channel is not bound to any Jira project.
   
   **Solutions:**
   
   **Option A: Bind the channel to a project (Recommended)**
   - Use `/jira bind PROJECT-KEY` to bind the current channel to a Jira project
   - Example: `/jira bind PROJ` or `/jira bind DEV`
   - After binding, you can use simple commands like `/jira create Fix login bug`
   
   **Option B: Specify project key in command**
   - Use `/jira create PROJECT-KEY Title` to create issues in specific projects
   - Example: `/jira create PROJ Fix login bug` or `/jira create DEV-123 Add feature`
   
   **Option C: Use admin interface**
   - Go to **Settings > MM Jira Integration > Channel Mappings**
   - Add a mapping for the channel ID to a Jira project key
   
   **To find available project keys:**
   - Use `/jira projects` to see all available Jira projects and their keys
   - This will show you the exact project keys you can use

2. **"Jira configuration not set up"**
   - Configure Jira domain and API key in settings
   - Go to **Settings > MM Jira Integration** and fill in:
     - Jira Domain (e.g., `your-domain.atlassian.net`)
     - API Key (format: `email:api_token`)
     - Webhook Token

3. **"Failed to create issue"**
   - Check Jira API credentials are correct
   - Verify the project key exists in your Jira instance
   - Check Jira API permissions for the user
   - Use `/jira projects` to verify available project keys
   - Check WordPress error logs for detailed error messages

4. **"Invalid project key format"**
   - Project keys must contain only uppercase letters and numbers (A-Z, 0-9)
   - Project keys must be 10 characters or less
   - Use `/jira projects` to see valid project keys
   - Examples: `PROJ`, `DEV`, `BUG`, `TEST` (valid) vs `project`, `PROJECT-KEY` (invalid)

5. **"Jira domain not configured"**
   - Go to **Settings > MM Jira Integration**
   - Set the Jira Domain field (e.g., `your-domain.atlassian.net`)
   - Make sure to use the correct domain format without `https://`

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Getting Help

If you're still having issues:

1. **Check the logs**: Go to **Settings > MM Jira Integration > Invocation Logs** to see detailed request/response logs
2. **Test connectivity**: Use `/jira projects` to test if Jira API is working
3. **Verify credentials**: Double-check your Jira domain and API key
4. **Check permissions**: Ensure your Jira user has permission to create issues in the target project

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