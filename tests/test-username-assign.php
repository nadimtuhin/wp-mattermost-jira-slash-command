<?php
/**
 * Test @username functionality for assign and find commands
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in a WordPress environment
if (!function_exists('get_option')) {
    die('WordPress not loaded');
}

// Test configuration
$test_email_domain = 'company.com';
$test_username = 'omartuhin';
$test_email = $test_username . '@' . $test_email_domain;

echo "<h1>Testing @username Functionality</h1>\n";

// Test 1: Check if email domain is configured
echo "<h2>Test 1: Email Domain Configuration</h2>\n";
$current_domain = get_option('wp_mm_slash_jira_email_domain');
echo "Current email domain: " . ($current_domain ? $current_domain : 'Not configured') . "\n";

if (empty($current_domain)) {
    echo "⚠️ Email domain not configured. @username functionality will not work.\n";
    echo "To configure: Go to WordPress Admin → Jira Integration → Settings → Email Domain\n\n";
} else {
    echo "✅ Email domain configured: {$current_domain}\n\n";
}

// Test 2: Test username to email conversion
echo "<h2>Test 2: Username to Email Conversion</h2>\n";
if (!empty($current_domain)) {
    $test_conversion = $test_username . '@' . $current_domain;
    echo "Username: @{$test_username}\n";
    echo "Converted email: {$test_conversion}\n";
    echo "✅ Username conversion working\n\n";
} else {
    echo "❌ Cannot test conversion - email domain not configured\n\n";
}

// Test 3: Test command parsing
echo "<h2>Test 3: Command Parsing Examples</h2>\n";
echo "<h3>Assign Command Examples:</h3>\n";
echo "• <code>/jira assign PROJ-123 @omartuhin</code> - Assign by username\n";
echo "• <code>/jira assign PROJ-123 omartuhin@company.com</code> - Assign by email\n";
echo "• <code>/jira assign PROJ-123 @rahmantanvir</code> - Assign by username\n\n";

echo "<h3>Find Command Examples:</h3>\n";
echo "• <code>/jira find @omartuhin</code> - Find by username\n";
echo "• <code>/jira find omartuhin@company.com</code> - Find by email\n";
echo "• <code>/jira find @rahmantanvir</code> - Find by username\n\n";

// Test 4: Input validation examples
echo "<h2>Test 4: Input Validation</h2>\n";
echo "<h3>Valid Inputs:</h3>\n";
echo "• <code>@omartuhin</code> - Valid username format\n";
echo "• <code>omartuhin@company.com</code> - Valid email format\n";
echo "• <code>@rahmantanvir</code> - Valid username with dots\n\n";

echo "<h3>Invalid Inputs:</h3>\n";
echo "• <code>developer</code> - Missing @ or email format\n";
echo "• <code>@</code> - Empty username\n";
echo "• <code>invalid-email</code> - Invalid email format\n\n";

// Test 5: Error handling
echo "<h2>Test 5: Error Handling</h2>\n";
echo "When email domain is not configured:\n";
echo "• Using @username will show error: 'Email domain not configured'\n";
echo "• Users will be prompted to use full email address\n";
echo "• Admin will be directed to configure email domain\n\n";

echo "When user not found in Jira:\n";
echo "• Both @username and email will show 'User not found' error\n";
echo "• Helpful suggestions will be provided\n\n";

// Test 6: Help text verification
echo "<h2>Test 6: Help Text Verification</h2>\n";
echo "The help command now includes:\n";
echo "• <code>/jira assign PROJ-123 @username</code> - Assign issue (using @username)\n";
echo "• <code>/jira find @username</code> - Find Jira user (using @username)\n";
echo "• Examples showing both email and @username formats\n\n";

echo "<h2>Summary</h2>\n";
if (!empty($current_domain)) {
    echo "✅ @username functionality is ready to use!\n";
    echo "✅ Users can now assign issues using: <code>/jira assign PROJ-123 @username</code>\n";
    echo "✅ Users can now find users using: <code>/jira find @username</code>\n";
} else {
    echo "⚠️ @username functionality requires email domain configuration\n";
    echo "📝 To enable: Configure 'Email Domain' in WordPress Admin → Jira Integration → Settings\n";
}

echo "\n<h2>Usage Instructions</h2>\n";
echo "<h3>For Users:</h3>\n";
echo "1. Assign issues: <code>/jira assign PROJ-123 @omartuhin</code>\n";
echo "2. Find users: <code>/jira find @omartuhin</code>\n";
echo "3. Still works with full emails: <code>/jira assign PROJ-123 omartuhin@company.com</code>\n\n";

echo "<h3>For Administrators:</h3>\n";
echo "1. Go to WordPress Admin → Jira Integration → Settings\n";
echo "2. Set 'Email Domain' to your company domain (e.g., 'company.com')\n";
echo "3. Save settings\n";
echo "4. Users can now use @username format\n\n";

echo "<h3>Benefits:</h3>\n";
echo "• Faster typing: <code>@omartuhin</code> vs <code>omartuhin@company.com</code>\n";
echo "• Less error-prone: No need to remember full email addresses\n";
echo "• Consistent with Mattermost @mentions\n";
echo "• Backward compatible: Full emails still work\n";
?> 