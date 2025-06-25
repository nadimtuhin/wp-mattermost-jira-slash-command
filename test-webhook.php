<?php
/**
 * Test script for the Jira webhook endpoint
 * 
 * Usage: php test-webhook.php
 * 
 * This script sends a test request to the webhook endpoint to verify it's working.
 */

// Configuration
$webhook_url = 'http://localhost/wp-json/jira/mattermost/slash/jira';
$webhook_token = 'qzgakf1nx3yt9dr4n8585ihbxy'; // Replace with your actual token

// Test data (simulating Mattermost webhook)
$test_data = array(
    'token' => $webhook_token,
    'channel_id' => 'fukxanjgjbnp7ng383at53k1sy',
    'channel_name' => 'WWW',
    'text' => 'create Test issue from webhook',
    'user_name' => 'testuser',
    'team_domain' => 'team-awesome',
    'team_id' => 'wx4zz8t4ttgmtxqiwfohijayzc',
    'user_id' => 'erj6qck3rfgtujs86w5r6rckzh',
    'command' => '/jira',
    'response_url' => 'http://localhost/hooks/commands/test',
    'trigger_id' => 'test-trigger-id'
);

echo "Testing webhook endpoint: $webhook_url\n";
echo "Token: $webhook_token\n";
echo "Test data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Send POST request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Test-Script/1.0'
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $http_code\n";
if ($error) {
    echo "cURL Error: $error\n";
}

echo "Response:\n";
if ($response) {
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $response . "\n";
    }
} else {
    echo "No response received\n";
}

echo "\n--- Testing Assign Command ---\n";

// Test assign command
$assign_test_data = array(
    'token' => $webhook_token,
    'channel_id' => 'fukxanjgjbnp7ng383at53k1sy',
    'channel_name' => 'WWW',
    'text' => 'assign PROJ-123 test@example.com',
    'user_name' => 'testuser',
    'team_domain' => 'team-awesome',
    'team_id' => 'wx4zz8t4ttgmtxqiwfohijayzc',
    'user_id' => 'erj6qck3rfgtujs86w5r6rckzh',
    'command' => '/jira',
    'response_url' => 'http://localhost/hooks/commands/test',
    'trigger_id' => 'test-trigger-id'
);

echo "Assign test data: " . json_encode($assign_test_data, JSON_PRETTY_PRINT) . "\n\n";

// Send POST request for assign command
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($assign_test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Test-Script/1.0'
));

$assign_response = curl_exec($ch);
$assign_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$assign_error = curl_error($ch);
curl_close($ch);

echo "Assign HTTP Status Code: $assign_http_code\n";
if ($assign_error) {
    echo "Assign cURL Error: $assign_error\n";
}

echo "Assign Response:\n";
if ($assign_response) {
    $assign_decoded = json_decode($assign_response, true);
    if ($assign_decoded) {
        echo json_encode($assign_decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $assign_response . "\n";
    }
} else {
    echo "No assign response received\n";
}

echo "\nTest completed.\n";

echo "\n--- Testing Bind Command ---\n";

// Test bind command
$bind_test_data = array(
    'token' => $webhook_token,
    'channel_id' => 'fukxanjgjbnp7ng383at53k1sy',
    'channel_name' => 'WWW',
    'text' => 'bind PROJ',
    'user_name' => 'testuser',
    'team_domain' => 'team-awesome',
    'team_id' => 'wx4zz8t4ttgmtxqiwfohijayzc',
    'user_id' => 'erj6qck3rfgtujs86w5r6rckzh',
    'command' => '/jira',
    'response_url' => 'http://localhost/hooks/commands/test',
    'trigger_id' => 'test-trigger-id'
);

echo "Bind test data: " . json_encode($bind_test_data, JSON_PRETTY_PRINT) . "\n\n";

// Send POST request for bind command
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($bind_test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Test-Script/1.0'
));

$bind_response = curl_exec($ch);
$bind_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$bind_error = curl_error($ch);
curl_close($ch);

echo "Bind HTTP Status Code: $bind_http_code\n";
if ($bind_error) {
    echo "Bind cURL Error: $bind_error\n";
}

echo "Bind Response:\n";
if ($bind_response) {
    $bind_decoded = json_decode($bind_response, true);
    if ($bind_decoded) {
        echo json_encode($bind_decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $bind_response . "\n";
    }
} else {
    echo "No bind response received\n";
}

echo "\nAll tests completed.\n";

echo "\n--- Testing Status Command ---\n";

// Test status command
$status_test_data = array(
    'token' => $webhook_token,
    'channel_id' => 'fukxanjgjbnp7ng383at53k1sy',
    'channel_name' => 'WWW',
    'text' => 'status',
    'user_name' => 'testuser',
    'team_domain' => 'team-awesome',
    'team_id' => 'wx4zz8t4ttgmtxqiwfohijayzc',
    'user_id' => 'erj6qck3rfgtujs86w5r6rckzh',
    'command' => '/jira',
    'response_url' => 'http://localhost/hooks/commands/test',
    'trigger_id' => 'test-trigger-id'
);

echo "Status test data: " . json_encode($status_test_data, JSON_PRETTY_PRINT) . "\n\n";

// Send POST request for status command
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($status_test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Test-Script/1.0'
));

$status_response = curl_exec($ch);
$status_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$status_error = curl_error($ch);
curl_close($ch);

echo "Status HTTP Status Code: $status_http_code\n";
if ($status_error) {
    echo "Status cURL Error: $status_error\n";
}

echo "Status Response:\n";
if ($status_response) {
    $status_decoded = json_decode($status_response, true);
    if ($status_decoded) {
        echo json_encode($status_decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $status_response . "\n";
    }
} else {
    echo "No status response received\n";
}

echo "\nAll tests completed successfully.\n";

echo "\n--- Testing Link Command ---\n";

// Test link command
$link_test_data = array(
    'token' => $webhook_token,
    'channel_id' => 'fukxanjgjbnp7ng383at53k1sy',
    'channel_name' => 'WWW',
    'text' => 'link',
    'user_name' => 'testuser',
    'team_domain' => 'team-awesome',
    'team_id' => 'wx4zz8t4ttgmtxqiwfohijayzc',
    'user_id' => 'erj6qck3rfgtujs86w5r6rckzh',
    'command' => '/jira',
    'response_url' => 'http://localhost/hooks/commands/test',
    'trigger_id' => 'test-trigger-id'
);

echo "Link test data: " . json_encode($link_test_data, JSON_PRETTY_PRINT) . "\n\n";

// Send POST request for link command
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($link_test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Test-Script/1.0'
));

$link_response = curl_exec($ch);
$link_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$link_error = curl_error($ch);
curl_close($ch);

echo "Link HTTP Status Code: $link_http_code\n";
if ($link_error) {
    echo "Link cURL Error: $link_error\n";
}

echo "Link Response:\n";
if ($link_response) {
    $link_decoded = json_decode($link_response, true);
    if ($link_decoded) {
        echo json_encode($link_decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $link_response . "\n";
    }
} else {
    echo "No link response received\n";
}

echo "\n--- Testing Board Command ---\n";

// Test board command
$board_test_data = array(
    'token' => $webhook_token,
    'channel_id' => 'fukxanjgjbnp7ng383at53k1sy',
    'channel_name' => 'WWW',
    'text' => 'board',
    'user_name' => 'testuser',
    'team_domain' => 'team-awesome',
    'team_id' => 'wx4zz8t4ttgmtxqiwfohijayzc',
    'user_id' => 'erj6qck3rfgtujs86w5r6rckzh',
    'command' => '/jira',
    'response_url' => 'http://localhost/hooks/commands/test',
    'trigger_id' => 'test-trigger-id'
);

echo "Board test data: " . json_encode($board_test_data, JSON_PRETTY_PRINT) . "\n\n";

// Send POST request for board command
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($board_test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Test-Script/1.0'
));

$board_response = curl_exec($ch);
$board_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$board_error = curl_error($ch);
curl_close($ch);

echo "Board HTTP Status Code: $board_http_code\n";
if ($board_error) {
    echo "Board cURL Error: $board_error\n";
}

echo "Board Response:\n";
if ($board_response) {
    $board_decoded = json_decode($board_response, true);
    if ($board_decoded) {
        echo json_encode($board_decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $board_response . "\n";
    }
} else {
    echo "No board response received\n";
}

echo "\nAll tests completed successfully.\n"; 