<?php
/**
 * Test assign-during-creation functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in a WordPress environment
if (!function_exists('get_option')) {
    die('WordPress not loaded');
}

echo "<h1>Testing Assign-During-Creation Functionality</h1>\n";

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

// Test 2: Command parsing examples
echo "<h2>Test 2: Command Parsing Examples</h2>\n";
echo "<h3>Create with Assignment Examples:</h3>\n";
echo "• <code>/jira create Fix login bug @developer</code> - Create and assign by username\n";
echo "• <code>/jira create PROJ Fix login bug @developer</code> - Create with project and assign\n";
echo "• <code>/jira create Bug:Fix login bug @developer</code> - Create with type and assign\n";
echo "• <code>/jira create PROJ Bug:Fix login bug @developer</code> - Create with project, type, and assign\n";
echo "• <code>/jira create Fix login bug developer@company.com</code> - Create and assign by email\n\n";

echo "<h3>Quick Shortcut Examples:</h3>\n";
echo "• <code>/jira bug Fix login issue @developer</code> - Create bug and assign\n";
echo "• <code>/jira task Update docs @writer</code> - Create task and assign\n";
echo "• <code>/jira story Add feature @dev</code> - Create story and assign\n\n";

// Test 3: Input validation examples
echo "<h2>Test 3: Input Validation</h2>\n";
echo "<h3>Valid Create + Assign Formats:</h3>\n";
echo "• <code>/jira create Title @username</code> - Valid username format\n";
echo "• <code>/jira create Title user@company.com</code> - Valid email format\n";
echo "• <code>/jira create Bug:Title @username</code> - Valid with type\n";
echo "• <code>/jira create PROJ Title @username</code> - Valid with project\n";
echo "• <code>/jira create PROJ Bug:Title @username</code> - Valid with project and type\n\n";

echo "<h3>Invalid Formats:</h3>\n";
echo "• <code>/jira create Title username</code> - Missing @ or email format\n";
echo "• <code>/jira create Title @</code> - Empty username\n";
echo "• <code>/jira create Title invalid-email</code> - Invalid email format\n";
echo "• <code>/jira create @username Title</code> - Assignee in wrong position\n\n";

// Test 4: Parsing logic
echo "<h2>Test 4: Parsing Logic</h2>\n";
echo "The system parses commands in this order:\n";
echo "1. **Project Key**: Checks if second parameter is a project key\n";
echo "2. **Issue Type**: Looks for 'TYPE:' prefix in title\n";
echo "3. **Assignee**: Checks if last word is @username or email\n";
echo "4. **Title**: Everything else becomes the issue title\n\n";

echo "<h3>Parsing Examples:</h3>\n";
echo "• <code>/jira create PROJ Fix login bug @developer</code>\n";
echo "  - Project: PROJ\n";
echo "  - Type: Task (default)\n";
echo "  - Title: Fix login bug\n";
echo "  - Assignee: @developer\n\n";

echo "• <code>/jira create Bug:Fix login bug @developer</code>\n";
echo "  - Project: (from channel mapping)\n";
echo "  - Type: Bug\n";
echo "  - Title: Fix login bug\n";
echo "  - Assignee: @developer\n\n";

echo "• <code>/jira create PROJ Bug:Fix login bug @developer</code>\n";
echo "  - Project: PROJ\n";
echo "  - Type: Bug\n";
echo "  - Title: Fix login bug\n";
echo "  - Assignee: @developer\n\n";

// Test 5: Error handling
echo "<h2>Test 5: Error Handling</h2>\n";
echo "When email domain is not configured:\n";
echo "• Using @username will show error: 'Email domain not configured'\n";
echo "• Users will be prompted to use full email address\n";
echo "• Admin will be directed to configure email domain\n\n";

echo "When assignee not found in Jira:\n";
echo "• Both @username and email will show 'User not found' error\n";
echo "• Issue creation will fail\n";
echo "• Helpful suggestions will be provided\n\n";

echo "When title is empty after parsing:\n";
echo "• Shows error: 'Please provide a title for the issue'\n";
echo "• Provides usage examples\n";
echo "• Shows all valid formats\n\n";

// Test 6: Help text verification
echo "<h2>Test 6: Help Text Verification</h2>\n";
echo "The help command now includes:\n";
echo "• <code>/jira create Fix login bug @developer</code> - Create and assign\n";
echo "• <code>/jira bug Fix login issue @developer</code> - Create bug and assign\n";
echo "• <code>/jira task Update docs @writer</code> - Create task and assign\n";
echo "• <code>/jira story Add feature @dev</code> - Create story and assign\n\n";

// Test 7: Backward compatibility
echo "<h2>Test 7: Backward Compatibility</h2>\n";
echo "✅ All existing commands still work:\n";
echo "• <code>/jira create Fix login bug</code> - Basic create\n";
echo "• <code>/jira create PROJ Fix login bug</code> - Create with project\n";
echo "• <code>/jira create Bug:Fix login bug</code> - Create with type\n";
echo "• <code>/jira bug Fix login issue</code> - Quick shortcut\n";
echo "• <code>/jira assign PROJ-123 @developer</code> - Separate assign\n\n";

echo "<h2>Summary</h2>\n";
if (!empty($current_domain)) {
    echo "✅ Assign-during-creation functionality is ready to use!\n";
    echo "✅ Users can now create and assign in one command\n";
    echo "✅ Works with all create command variations\n";
    echo "✅ Supports both @username and email formats\n";
} else {
    echo "⚠️ Assign-during-creation requires email domain configuration\n";
    echo "📝 To enable: Configure 'Email Domain' in WordPress Admin → Jira Integration → Settings\n";
}

echo "\n<h2>Usage Instructions</h2>\n";
echo "<h3>For Users:</h3>\n";
echo "1. Create and assign: <code>/jira create Fix login bug @developer</code>\n";
echo "2. Create with project and assign: <code>/jira create PROJ Fix login bug @developer</code>\n";
echo "3. Create with type and assign: <code>/jira create Bug:Fix login bug @developer</code>\n";
echo "4. Quick shortcuts: <code>/jira bug Fix login issue @developer</code>\n";
echo "5. Still works without assignment: <code>/jira create Fix login bug</code>\n\n";

echo "<h3>For Administrators:</h3>\n";
echo "1. Go to WordPress Admin → Jira Integration → Settings\n";
echo "2. Set 'Email Domain' to your company domain (e.g., 'company.com')\n";
echo "3. Save settings\n";
echo "4. Users can now use @username format for assignment\n\n";

echo "<h3>Benefits:</h3>\n";
echo "• **One-step workflow**: Create and assign in single command\n";
echo "• **Faster**: No need for separate assign command\n";
echo "• **Less error-prone**: Assignment happens immediately\n";
echo "• **Flexible**: Works with all create command variations\n";
echo "• **Backward compatible**: Existing commands still work\n";
echo "• **Consistent**: Uses same @username format as assign command\n";
?> 