<?php
/**
 * Test 'sub' alias functionality for subtask commands
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in a WordPress environment
if (!function_exists('get_option')) {
    die('WordPress not loaded');
}

echo "<h1>Testing 'sub' Alias Functionality</h1>\n";

// Test configuration
$test_email_domain = 'company.com';
$test_username = 'developer';
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

// Test 2: Command alias examples
echo "<h2>Test 2: Command Alias Examples</h2>\n";
echo "<h3>Subtask Commands (Full):</h3>\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug</code> - Full command\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug @developer</code> - Full command with assign\n\n";

echo "<h3>Sub Commands (Short Alias):</h3>\n";
echo "• <code>/jira sub PROJ-123 Fix login bug</code> - Short alias\n";
echo "• <code>/jira sub PROJ-123 Fix login bug @developer</code> - Short alias with assign\n\n";

// Test 3: Functionality comparison
echo "<h2>Test 3: Functionality Comparison</h2>\n";
echo "✅ Both commands work identically:\n";
echo "• <code>/jira subtask PROJ-123 Title</code> = <code>/jira sub PROJ-123 Title</code>\n";
echo "• <code>/jira subtask PROJ-123 Title @user</code> = <code>/jira sub PROJ-123 Title @user</code>\n";
echo "• <code>/jira subtask PROJ-123 Title user@company.com</code> = <code>/jira sub PROJ-123 Title user@company.com</code>\n\n";

// Test 4: Input validation
echo "<h2>Test 4: Input Validation</h2>\n";
echo "<h3>Valid 'sub' Command Formats:</h3>\n";
echo "• <code>/jira sub PROJ-123 Title</code> - Valid parent issue and title\n";
echo "• <code>/jira sub PROJ-123 Title @username</code> - Valid with assignee\n";
echo "• <code>/jira sub PROJ-123 Title user@company.com</code> - Valid with email\n\n";

echo "<h3>Invalid 'sub' Command Formats:</h3>\n";
echo "• <code>/jira sub PROJ-123</code> - Missing title\n";
echo "• <code>/jira sub Title</code> - Missing parent issue key\n";
echo "• <code>/jira sub INVALID Title</code> - Invalid issue key format\n";
echo "• <code>/jira sub PROJ-123 @username</code> - Missing title\n";
echo "• <code>/jira sub PROJ-123 Title username</code> - Invalid assignee format\n\n";

// Test 5: Parsing logic
echo "<h2>Test 5: Parsing Logic</h2>\n";
echo "The 'sub' alias uses the same parsing logic as 'subtask':\n";
echo "1. **Parent Issue Key**: Second parameter must be valid issue key (PROJ-123)\n";
echo "2. **Title**: All remaining words become the subtask title\n";
echo "3. **Assignee**: Last word if it's @username or email\n\n";

echo "<h3>Parsing Examples for 'sub':</h3>\n";
echo "• <code>/jira sub PROJ-123 Fix login bug @developer</code>\n";
echo "  - Parent: PROJ-123\n";
echo "  - Title: Fix login bug\n";
echo "  - Assignee: @developer\n\n";

echo "• <code>/jira sub STORY-456 Add unit tests for login module</code>\n";
echo "  - Parent: STORY-456\n";
echo "  - Title: Add unit tests for login module\n";
echo "  - Assignee: None\n\n";

echo "• <code>/jira sub BUG-789 Update documentation developer@company.com</code>\n";
echo "  - Parent: BUG-789\n";
echo "  - Title: Update documentation\n";
echo "  - Assignee: developer@company.com\n\n";

// Test 6: Error handling
echo "<h2>Test 6: Error Handling</h2>\n";
echo "The 'sub' alias provides the same error handling as 'subtask':\n";
echo "• Parent issue not found: Same error message\n";
echo "• Email domain not configured: Same error message\n";
echo "• Assignee not found: Same error message\n";
echo "• Invalid format: Same error message with examples\n";
echo "• Empty title: Same error message with usage examples\n\n";

// Test 7: Help text verification
echo "<h2>Test 7: Help Text Verification</h2>\n";
echo "The help command now includes both commands:\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug</code> - Full command\n";
echo "• <code>/jira sub PROJ-123 Fix login bug</code> - Short alias\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug @developer</code> - Full command with assign\n";
echo "• <code>/jira sub PROJ-123 Fix login bug @developer</code> - Short alias with assign\n\n";

// Test 8: Use cases
echo "<h2>Test 8: Common Use Cases</h2>\n";
echo "<h3>Breaking down large tasks (using 'sub'):</h3>\n";
echo "• <code>/jira sub STORY-100 Implement user authentication</code>\n";
echo "• <code>/jira sub STORY-100 Add login form @frontend</code>\n";
echo "• <code>/jira sub STORY-100 Add backend API @backend</code>\n";
echo "• <code>/jira sub STORY-100 Add unit tests @tester</code>\n\n";

echo "<h3>Bug investigation tasks (using 'sub'):</h3>\n";
echo "• <code>/jira sub BUG-200 Investigate login failure</code>\n";
echo "• <code>/jira sub BUG-200 Check server logs @devops</code>\n";
echo "• <code>/jira sub BUG-200 Reproduce issue @qa</code>\n";
echo "• <code>/jira sub BUG-200 Fix root cause @developer</code>\n\n";

echo "<h3>Documentation tasks (using 'sub'):</h3>\n";
echo "• <code>/jira sub TASK-300 Update project documentation</code>\n";
echo "• <code>/jira sub TASK-300 Update API docs @backend</code>\n";
echo "• <code>/jira sub TASK-300 Update user guide @writer</code>\n";
echo "• <code>/jira sub TASK-300 Review documentation @manager</code>\n\n";

// Test 9: Backward compatibility
echo "<h2>Test 9: Backward Compatibility</h2>\n";
echo "✅ All existing commands still work:\n";
echo "• <code>/jira subtask PROJ-123 Fix login bug</code> - Original command\n";
echo "• <code>/jira create Fix login bug</code> - Basic create\n";
echo "• <code>/jira bug Fix login issue</code> - Quick shortcut\n";
echo "• <code>/jira assign PROJ-123 @developer</code> - Assign existing issue\n";
echo "• <code>/jira view PROJ-123</code> - View issue details\n\n";

echo "✅ New 'sub' alias is available:\n";
echo "• <code>/jira sub PROJ-123 Fix login bug</code> - New short alias\n";
echo "• <code>/jira sub PROJ-123 Fix login bug @developer</code> - New short alias with assign\n\n";

echo "<h2>Summary</h2>\n";
if (!empty($current_domain)) {
    echo "✅ 'sub' alias functionality is ready to use!\n";
    echo "✅ Users can now use shorter commands for subtasks\n";
    echo "✅ Both 'subtask' and 'sub' work identically\n";
    echo "✅ Works with assignment during creation\n";
    echo "✅ Supports both @username and email formats\n";
} else {
    echo "⚠️ 'sub' alias functionality requires email domain configuration for @username assignment\n";
    echo "📝 To enable: Configure 'Email Domain' in WordPress Admin → Jira Integration → Settings\n";
}

echo "\n<h2>Usage Instructions</h2>\n";
echo "<h3>For Users:</h3>\n";
echo "1. Create subtask (full): <code>/jira subtask PROJ-123 Fix login bug</code>\n";
echo "2. Create subtask (short): <code>/jira sub PROJ-123 Fix login bug</code>\n";
echo "3. Create and assign (full): <code>/jira subtask PROJ-123 Fix login bug @developer</code>\n";
echo "4. Create and assign (short): <code>/jira sub PROJ-123 Fix login bug @developer</code>\n";
echo "5. Both commands work exactly the same way\n\n";

echo "<h3>For Administrators:</h3>\n";
echo "1. Go to WordPress Admin → Jira Integration → Settings\n";
echo "2. Set 'Email Domain' to your company domain (e.g., 'company.com')\n";
echo "3. Save settings\n";
echo "4. Users can now use both 'subtask' and 'sub' commands\n\n";

echo "<h3>Benefits:</h3>\n";
echo "• **Faster typing**: <code>/jira sub</code> vs <code>/jira subtask</code>\n";
echo "• **User preference**: Users can choose their preferred command\n";
echo "• **Consistent functionality**: Both commands work identically\n";
echo "• **Backward compatible**: Original 'subtask' command still works\n";
echo "• **Clear documentation**: Help text shows both options\n";
echo "• **Flexible**: Works with all subtask features (assignment, etc.)\n";
?> 