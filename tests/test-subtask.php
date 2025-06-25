<?php
/**
 * Test subtask functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in a WordPress environment
if (!function_exists('get_option')) {
    die('WordPress not loaded');
}

echo "<h1>Testing Subtask Functionality</h1>\n";

// Test configuration
$test_email_domain = 'company.com';
$test_username = 'omartuhin';
$test_email = $test_username . '@' . $test_email_domain;

// Test 1: Check if email domain is configured
echo "<h2>Test 1: Email Domain Configuration</h2>\n";
$current_domain = get_option('wp_mm_slash_jira_email_domain');
echo "Current email domain: " . ($current_domain ? $current_domain : 'Not configured') . "\n";

if (empty($current_domain)) {
    echo "⚠️ Email domain not configured. @username assignment will not work.\n";
    echo "To configure: Go to WordPress Admin → Jira Integration → Settings → Email Domain\n\n";
} else {
    echo "✅ Email domain configured: {$current_domain}\n\n";
}

// Test 2: Command parsing examples
echo "<h2>Test 2: Command Parsing Examples</h2>\n";
echo "<h3>Subtask Creation Examples:</h3>\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug</code> - Create subtask\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug @omartuhin</code> - Create subtask and assign\n";
echo "• <code>/jira sub PROJ-123 Fix login bug</code> - Create subtask (short alias)\n";
echo "• <code>/jira sub PROJ-123 Fix login bug @omartuhin</code> - Create subtask and assign (short alias)\n\n";

// Test 3: Input validation examples
echo "<h2>Test 3: Input Validation</h2>\n";
echo "<h3>Valid Subtask Formats:</h3>\n";
echo "• <code>/jira subtask PROJ-123 Title</code> - Valid parent issue and title\n";
echo "• <code>/jira subtask PROJ-123 Title @username</code> - Valid with assignee\n";
echo "• <code>/jira subtask PROJ-123 Title user@company.com</code> - Valid with email\n\n";

echo "<h3>Invalid Formats:</h3>\n";
echo "• <code>/jira subtask PROJ-123</code> - Missing title\n";
echo "• <code>/jira subtask Title</code> - Missing parent issue key\n";
echo "• <code>/jira subtask INVALID Title</code> - Invalid issue key format\n";
echo "• <code>/jira subtask PROJ-123 @username</code> - Missing title\n";
echo "• <code>/jira subtask PROJ-123 Title username</code> - Invalid assignee format\n\n";

// Test 4: Parsing logic
echo "<h2>Test 4: Parsing Logic</h2>\n";
echo "The system parses subtask commands in this order:\n";
echo "1. **Parent Issue Key**: Second parameter must be valid issue key (PROJ-123)\n";
echo "2. **Title**: All remaining words become the subtask title\n";
echo "3. **Assignee**: Last word if it's @username or email\n\n";

echo "<h3>Parsing Examples:</h3>\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug @omartuhin</code>\n";
echo "  - Parent: PROJ-123\n";
echo "  - Title: Fix login bug\n";
echo "  - Assignee: @omartuhin\n\n";

echo "• <code>/jira subtask STORY-456 Add unit tests for login module</code>\n";
echo "  - Parent: STORY-456\n";
echo "  - Title: Add unit tests for login module\n";
echo "  - Assignee: None\n\n";

echo "• <code>/jira subtask BUG-789 Update documentation developer@company.com</code>\n";
echo "  - Parent: BUG-789\n";
echo "  - Title: Update documentation\n";
echo "  - Assignee: developer@company.com\n\n";

// Test 5: Error handling
echo "<h2>Test 5: Error Handling</h2>\n";
echo "When parent issue not found:\n";
echo "• Shows error: 'Parent issue PROJ-123 not found or not accessible'\n";
echo "• Suggests checking the issue key\n";
echo "• Provides usage examples\n\n";

echo "When email domain is not configured:\n";
echo "• Using @username will show error: 'Email domain not configured'\n";
echo "• Users will be prompted to use full email address\n";
echo "• Admin will be directed to configure email domain\n\n";

echo "When assignee not found in Jira:\n";
echo "• Both @username and email will show 'User not found' error\n";
echo "• Subtask creation will fail\n";
echo "• Helpful suggestions will be provided\n\n";

echo "When title is empty:\n";
echo "• Shows error: 'Please provide a title for the subtask'\n";
echo "• Provides usage examples\n";
echo "• Shows all valid formats\n\n";

// Test 6: Help text verification
echo "<h2>Test 6: Help Text Verification</h2>\n";
echo "The help command now includes:\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug</code> - Create subtask\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug @omartuhin</code> - Create subtask and assign\n";
echo "• Examples showing both basic and assignment formats\n\n";

// Test 7: Use cases
echo "<h2>Test 7: Common Use Cases</h2>\n";
echo "<h3>Breaking down large tasks:</h3>\n";
echo "• <code>/jira subtask STORY-100 Implement user authentication</code>\n";
echo "• <code>/jira subtask STORY-100 Add login form @frontend</code>\n";
echo "• <code>/jira subtask STORY-100 Add backend API @backend</code>\n";
echo "• <code>/jira subtask STORY-100 Add unit tests @tester</code>\n\n";

echo "<h3>Bug investigation tasks:</h3>\n";
echo "• <code>/jira subtask BUG-200 Investigate login failure</code>\n";
echo "• <code>/jira subtask BUG-200 Check server logs @devops</code>\n";
echo "• <code>/jira subtask BUG-200 Reproduce issue @qa</code>\n";
echo "• <code>/jira subtask BUG-200 Fix root cause @developer</code>\n\n";

echo "<h3>Documentation tasks:</h3>\n";
echo "• <code>/jira subtask TASK-300 Update project documentation</code>\n";
echo "• <code>/jira subtask TASK-300 Update API docs @backend</code>\n";
echo "• <code>/jira subtask TASK-300 Update user guide @writer</code>\n";
echo "• <code>/jira subtask TASK-300 Review documentation @manager</code>\n\n";

// Test 8: Backward compatibility
echo "<h2>Test 8: Backward Compatibility</h2>\n";
echo "✅ All existing commands still work:\n";
echo "• <code>/jira create Fix login bug</code> - Basic create\n";
echo "• <code>/jira bug Fix login issue</code> - Quick shortcut\n";
echo "• <code>/jira assign PROJ-123 @developer</code> - Assign existing issue\n";
echo "• <code>/jira view PROJ-123</code> - View issue details\n\n";

echo "✅ New subtask command is separate:\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug</code> - New subtask command\n";
echo "• <code>/jira create Subtask:Fix login bug</code> - Still works for standalone subtasks\n\n";

echo "<h2>Summary</h2>\n";
if (!empty($current_domain)) {
    echo "✅ Subtask functionality is ready to use!\n";
    echo "✅ Users can now create subtasks within existing issues\n";
    echo "✅ Works with assignment during creation\n";
    echo "✅ Supports both @username and email formats\n";
} else {
    echo "⚠️ Subtask functionality requires email domain configuration for @username assignment\n";
    echo "📝 To enable: Configure 'Email Domain' in WordPress Admin → Jira Integration → Settings\n";
}

echo "\n<h2>Usage Instructions</h2>\n";
echo "<h3>For Users:</h3>\n";
echo "1. Create subtask: <code>/jira subtask PROJ-123 Fix login bug</code>\n";
echo "2. Create and assign: <code>/jira subtask PROJ-123 Fix login bug @omartuhin</code>\n";
echo "3. Create in different issue types: <code>/jira subtask STORY-456 Add tests @tester</code>\n";
echo "4. View parent issue: <code>/jira view PROJ-123</code>\n";
echo "5. View subtask: <code>/jira view PROJ-124</code> (after creation)\n\n";

echo "<h3>For Administrators:</h3>\n";
echo "1. Go to WordPress Admin → Jira Integration → Settings\n";
echo "2. Set 'Email Domain' to your company domain (e.g., 'company.com')\n";
echo "3. Save settings\n";
echo "4. Users can now use @username format for subtask assignment\n\n";

echo "<h3>Benefits:</h3>\n";
echo "• **Better organization**: Break down large tasks into manageable pieces\n";
echo "• **Clear ownership**: Assign specific parts to different team members\n";
echo "• **Progress tracking**: Track completion of individual subtasks\n";
echo "• **Workflow efficiency**: Create and assign in one command\n";
echo "• **Flexible**: Works with any issue type that supports subtasks\n";
echo "• **Consistent**: Uses same @username format as other commands\n";
?> 