jQuery(document).ready(function($) {
    
    // Load mappings function
    window.loadMappings = function() {
        $.ajax({
            url: wp_mm_slash_jira.rest_url + 'mappings',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_mm_slash_jira.nonce);
            },
            success: function(data) {
                displayMappings(data);
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Error loading mappings: ' + error;
                
                // Try to get more detailed error information
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = 'Error loading mappings: ' + xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    errorMessage = 'Error loading mappings: Forbidden - Insufficient permissions. Please make sure you are logged in as an administrator.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Error loading mappings: Server error. Please check the WordPress error logs.';
                }
                
                $('#mappings-table-container').html('<p class="error">' + errorMessage + '</p>');
                console.error('Mappings load error:', xhr, status, error);
            }
        });
    };
    
    // Display mappings in table
    function displayMappings(mappings) {
        if (mappings.length === 0) {
            $('#mappings-table-container').html('<p>No mappings found. Add your first mapping above.</p>');
            return;
        }
        
        var table = '<table class="wp-list-table widefat fixed striped">' +
            '<thead>' +
            '<tr>' +
            '<th>Channel ID</th>' +
            '<th>Channel Name</th>' +
            '<th>Jira Project Key</th>' +
            '<th>Created</th>' +
            '<th>Actions</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>';
        
        mappings.forEach(function(mapping) {
            table += '<tr>' +
                '<td>' + mapping.channel_id + '</td>' +
                '<td>' + mapping.channel_name + '</td>' +
                '<td>' + mapping.jira_project_key + '</td>' +
                '<td>' + new Date(mapping.created_at).toLocaleDateString() + '</td>' +
                '<td>' +
                '<button type="button" class="button button-small delete-mapping" data-id="' + mapping.id + '">Delete</button>' +
                '</td>' +
                '</tr>';
        });
        
        table += '</tbody></table>';
        $('#mappings-table-container').html(table);
    }
    
    // Add mapping
    function addMapping() {
        var channelId = $('#channel_id').val().trim();
        var channelName = $('#channel_name').val().trim();
        var jiraProjectKey = $('#jira_project_key').val().trim();
        
        if (!channelId || !channelName || !jiraProjectKey) {
            alert('Please fill in all fields');
            return;
        }
        
        $.ajax({
            url: wp_mm_slash_jira.rest_url + 'mappings',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_mm_slash_jira.nonce);
            },
            data: JSON.stringify({
                channel_id: channelId,
                channel_name: channelName,
                jira_project_key: jiraProjectKey
            }),
            contentType: 'application/json',
            success: function(data) {
                $('#channel_id').val('');
                $('#channel_name').val('');
                $('#jira_project_key').val('');
                loadMappings();
                alert('Mapping added successfully');
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Error adding mapping: ' + error;
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = 'Error adding mapping: ' + xhr.responseJSON.message;
                }
                
                alert(errorMessage);
                console.error('Add mapping error:', xhr, status, error);
            }
        });
    }
    
    // Add mapping button click handler
    $('#add-mapping').click(function() {
        addMapping();
    });
    
    // Delete mapping
    function deleteMapping(id) {
        if (!confirm('Are you sure you want to delete this mapping?')) {
            return;
        }
        
        $.ajax({
            url: wp_mm_slash_jira.rest_url + 'mappings/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_mm_slash_jira.nonce);
            },
            success: function(data) {
                loadMappings();
                alert('Mapping deleted successfully');
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Error deleting mapping: ' + error;
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = 'Error deleting mapping: ' + xhr.responseJSON.message;
                }
                
                alert(errorMessage);
                console.error('Delete mapping error:', xhr, status, error);
            }
        });
    }
    
    // Delete mapping button click handler
    $(document).on('click', '.delete-mapping', function() {
        var id = $(this).data('id');
        deleteMapping(id);
    });
    
    // Copy webhook URL to clipboard
    $('.webhook-info code').click(function() {
        var text = $(this).text();
        navigator.clipboard.writeText(text).then(function() {
            alert('Webhook URL copied to clipboard!');
        }).catch(function() {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Webhook URL copied to clipboard!');
        });
    });
    
    // Add click styling to webhook URL
    $('.webhook-info code').css('cursor', 'pointer').attr('title', 'Click to copy');
    
    // Toggle password visibility for sensitive fields
    window.togglePasswordVisibility = function(fieldId) {
        var field = document.getElementById(fieldId);
        if (field.type === 'password') {
            field.type = 'text';
        } else {
            field.type = 'password';
        }
    };
    
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
    
    // Load logs on page load if logs tab is active
    if (window.location.hash === '#logs') {
        $('.nav-tab[href="#logs"]').click();
    }
    
    // Export settings functionality
    $('#export-settings').click(function() {
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text('Exporting...');
        
        // Create a form to submit the export request
        var form = $('<form>', {
            'method': 'POST',
            'action': wp_mm_slash_jira.ajax_url
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'export_mm_jira_settings'
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': wp_mm_slash_jira.ajax_nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        // Reset button after a short delay
        setTimeout(function() {
            button.prop('disabled', false).text(originalText);
            $('#export-result').html('<p class="success">✅ Settings exported successfully! File download should start automatically.</p>');
        }, 1000);
    });
    
    // Import settings functionality
    $('#import-settings').click(function() {
        $('#import-file').click();
    });
    
    $('#import-file').change(function() {
        var file = this.files[0];
        if (!file) {
            return;
        }
        
        // Validate file type
        if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
            alert('Please select a valid JSON file.');
            return;
        }
        
        // Validate file size (max 1MB)
        if (file.size > 1024 * 1024) {
            alert('File size must be less than 1MB.');
            return;
        }
        
        if (!confirm('Importing settings will overwrite your current configuration. Are you sure you want to continue?')) {
            return;
        }
        
        var button = $('#import-settings');
        var originalText = button.text();
        
        button.prop('disabled', true).text('Importing...');
        
        var formData = new FormData();
        formData.append('action', 'import_mm_jira_settings');
        formData.append('nonce', wp_mm_slash_jira.ajax_nonce);
        formData.append('import_file', file);
        
        $.ajax({
            url: wp_mm_slash_jira.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#import-result').html('<p class="success">✅ ' + response.data + '</p>');
                    // Reload page to show updated settings
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#import-result').html('<p class="error">❌ ' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Import failed: ' + error;
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                $('#import-result').html('<p class="error">❌ ' + errorMessage + '</p>');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
                $('#import-file').val(''); // Clear file input
            }
        });
    });
    
    // Log modal functionality
    $('.view-log').click(function() {
        var logId = $(this).data('id');
        loadLogDetails(logId);
    });
    
    // Close modal
    $('.close').click(function() {
        $('#log-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).is('#log-modal')) {
            $('#log-modal').hide();
        }
    });
    
    // Load log details
    function loadLogDetails(logId) {
        $.ajax({
            url: wp_mm_slash_jira.rest_url + 'logs/' + logId,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_mm_slash_jira.nonce);
            },
            success: function(data) {
                displayLogDetails(data);
                $('#log-modal').show();
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Error loading log details: ' + error;
                
                // Try to get more detailed error information
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = 'Error loading log details: ' + xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    errorMessage = 'Error loading log details: Forbidden - Insufficient permissions. Please make sure you are logged in as an administrator.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Error loading log details: Log entry not found. It may have been deleted.';
                } else if (xhr.status === 400) {
                    errorMessage = 'Error loading log details: Invalid log ID.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Error loading log details: Server error. Please check the WordPress error logs.';
                }
                
                alert(errorMessage);
                console.error('Log details load error:', xhr, status, error);
            }
        });
    }
    
    // Display log details in modal
    function displayLogDetails(log) {
        var html = '<div class="log-details">';
        html += '<h4>Basic Information</h4>';
        html += '<table class="form-table">';
        html += '<tr><th>Timestamp:</th><td>' + log.timestamp + '</td></tr>';
        html += '<tr><th>Channel:</th><td>' + log.channel_name + ' (' + log.channel_id + ')</td></tr>';
        html += '<tr><th>User:</th><td>' + log.user_name + '</td></tr>';
        html += '<tr><th>Command:</th><td>' + log.command + '</td></tr>';
        html += '<tr><th>Status:</th><td><span class="status-' + log.status + '">' + log.status + '</span></td></tr>';
        html += '<tr><th>Execution Time:</th><td>' + (log.execution_time * 1000).toFixed(2) + ' ms</td></tr>';
        if (log.error_message) {
            html += '<tr><th>Error:</th><td>' + log.error_message + '</td></tr>';
        }
        html += '</table>';
        
        // Check if this is a curl payload log
        var curlPayload = null;
        try {
            curlPayload = JSON.parse(log.request_payload);
        } catch (e) {
            // Not a curl payload, continue with normal display
        }
        
        if (curlPayload && curlPayload.method && curlPayload.url) {
            // This is a curl payload log
            html += '<h4>Jira API Request (Curl)</h4>';
            html += '<table class="form-table">';
            html += '<tr><th>Method:</th><td>' + curlPayload.method + '</td></tr>';
            html += '<tr><th>URL:</th><td><code>' + curlPayload.url + '</code></td></tr>';
            html += '<tr><th>Execution Time:</th><td>' + (curlPayload.execution_time * 1000).toFixed(2) + ' ms</td></tr>';
            if (curlPayload.error_message) {
                html += '<tr><th>Error:</th><td>' + curlPayload.error_message + '</td></tr>';
            }
            html += '</table>';
            
            html += '<h4>Request Headers</h4>';
            html += '<pre class="payload-display">' + JSON.stringify(curlPayload.request.headers, null, 2) + '</pre>';
            
            if (curlPayload.request.body) {
                html += '<h4>Request Body</h4>';
                html += '<pre class="payload-display">' + curlPayload.request.body + '</pre>';
            }
            
            html += '<h4>Response Information</h4>';
            html += '<table class="form-table">';
            html += '<tr><th>Response Code:</th><td>' + curlPayload.response.code + '</td></tr>';
            html += '</table>';
            
            if (curlPayload.response.headers && Object.keys(curlPayload.response.headers).length > 0) {
                html += '<h4>Response Headers</h4>';
                html += '<pre class="payload-display">' + JSON.stringify(curlPayload.response.headers, null, 2) + '</pre>';
            }
            
            if (curlPayload.response.body) {
                html += '<h4>Response Body</h4>';
                html += '<pre class="payload-display">' + curlPayload.response.body + '</pre>';
            }
            
            // Add curl command for Jira API testing
            html += '<h4>🔧 Curl Command for Testing</h4>';
            html += '<p>Use this curl command to reproduce the exact Jira API call:</p>';
            html += '<div class="curl-command-container">';
            html += '<pre class="curl-command">';
            html += 'curl -X ' + curlPayload.method + ' "' + curlPayload.url + '" \\';
            
            // Add headers
            if (curlPayload.request.headers) {
                for (var header in curlPayload.request.headers) {
                    if (curlPayload.request.headers.hasOwnProperty(header)) {
                        html += '\n  -H "' + header + ': ' + curlPayload.request.headers[header] + '" \\';
                    }
                }
            }
            
            // Add body if present
            if (curlPayload.request.body) {
                html += '\n  -d \'' + curlPayload.request.body.replace(/'/g, "'\"'\"'") + '\'';
            }
            
            html += '</pre>';
            html += '<button type="button" class="button button-small copy-curl" data-command="' + htmlEscape(generateCurlCommand(curlPayload)) + '">📋 Copy Command</button>';
            html += '</div>';
            
        } else {
            // Regular webhook log
            html += '<h4>Request Payload</h4>';
            html += '<pre class="payload-display">' + (log.request_payload_formatted || log.request_payload) + '</pre>';
            
            html += '<h4>Response Payload</h4>';
            html += '<pre class="payload-display">' + (log.response_payload_formatted || log.response_payload || 'No response') + '</pre>';
            
            // Add curl command for webhook testing
            html += '<h4>🔧 Curl Command for Testing</h4>';
            html += '<p>Use this curl command to reproduce the exact webhook call:</p>';
            html += '<div class="curl-command-container">';
            html += '<pre class="curl-command">';
            
            // Parse the request payload to extract parameters
            var webhookParams = parseWebhookPayload(log.request_payload);
            var curlCmd = 'curl -X POST "' + wp_mm_slash_jira.rest_url + 'jira" \\';
            
            if (webhookParams) {
                for (var param in webhookParams) {
                    if (webhookParams.hasOwnProperty(param)) {
                        curlCmd += '\n  -d "' + param + '=' + webhookParams[param] + '" \\';
                    }
                }
                curlCmd = curlCmd.slice(0, -2); // Remove trailing backslash and space
            }
            
            html += curlCmd;
            html += '</pre>';
            html += '<button type="button" class="button button-small copy-curl" data-command="' + htmlEscape(curlCmd) + '">📋 Copy Command</button>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $('#log-details').html(html);
        
        // Add click handler for copy buttons
        $('.copy-curl').click(function() {
            var command = $(this).data('command');
            copyToClipboard(command);
            $(this).text('✅ Copied!').prop('disabled', true);
            setTimeout(function() {
                $('.copy-curl').text('📋 Copy Command').prop('disabled', false);
            }, 2000);
        });
    }
    
    // Helper function to generate curl command for Jira API calls
    function generateCurlCommand(curlPayload) {
        var cmd = 'curl -X ' + curlPayload.method + ' "' + curlPayload.url + '" \\';
        
        // Add headers
        if (curlPayload.request.headers) {
            for (var header in curlPayload.request.headers) {
                if (curlPayload.request.headers.hasOwnProperty(header)) {
                    cmd += '\n  -H "' + header + ': ' + curlPayload.request.headers[header] + '" \\';
                }
            }
        }
        
        // Add body if present
        if (curlPayload.request.body) {
            cmd += '\n  -d \'' + curlPayload.request.body.replace(/'/g, "'\"'\"'") + '\'';
        }
        
        return cmd;
    }
    
    // Helper function to parse webhook payload and extract parameters
    function parseWebhookPayload(payload) {
        try {
            var data = JSON.parse(payload);
            var params = {};
            
            // Extract common webhook parameters
            if (data.token) params.token = data.token;
            if (data.channel_id) params.channel_id = data.channel_id;
            if (data.channel_name) params.channel_name = data.channel_name;
            if (data.text) params.text = data.text;
            if (data.user_name) params.user_name = data.user_name;
            if (data.user_id) params.user_id = data.user_id;
            if (data.team_id) params.team_id = data.team_id;
            if (data.team_domain) params.team_domain = data.team_domain;
            if (data.command) params.command = data.command;
            if (data.response_url) params.response_url = data.response_url;
            
            return params;
        } catch (e) {
            // If not JSON, try to parse as form data
            var params = {};
            var pairs = payload.split('&');
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].split('=');
                if (pair.length === 2) {
                    params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
                }
            }
            return params;
        }
    }
    
    // Helper function to escape HTML
    function htmlEscape(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
    
    // Helper function to copy text to clipboard
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            // Use modern clipboard API
            navigator.clipboard.writeText(text).then(function() {
                console.log('Text copied to clipboard');
            }).catch(function(err) {
                console.error('Failed to copy text: ', err);
                fallbackCopyToClipboard(text);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyToClipboard(text);
        }
    }
    
    // Fallback copy function
    function fallbackCopyToClipboard(text) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            console.log('Text copied to clipboard (fallback)');
        } catch (err) {
            console.error('Fallback copy failed: ', err);
        }
        
        document.body.removeChild(textArea);
    }
    
    // Create tables button
    $('#create-tables').click(function() {
        var button = $(this);
        var resultDiv = $('#create-tables-result');
        
        button.prop('disabled', true).text('Creating tables...');
        resultDiv.html('');
        
        $.ajax({
            url: wp_mm_slash_jira.ajax_url,
            method: 'POST',
            data: {
                action: 'create_mm_jira_tables',
                nonce: wp_mm_slash_jira.ajax_nonce
            },
            success: function(data) {
                if (data.mappings_table && data.logs_table) {
                    resultDiv.html('<p style="color: green;">✅ Database tables created successfully! Please refresh the page.</p>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    var errors = [];
                    if (!data.mappings_table) errors.push('Mappings table');
                    if (!data.logs_table) errors.push('Logs table');
                    resultDiv.html('<p style="color: red;">❌ Failed to create: ' + errors.join(', ') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                resultDiv.html('<p style="color: red;">❌ Error creating tables: ' + error + '</p>');
            },
            complete: function() {
                button.prop('disabled', false).text('Create Database Tables');
            }
        });
    });
    
    // Clear logs radio button interactions
    $('input[name="clear_type"]').change(function() {
        if ($(this).val() === 'old') {
            $('#clear_days').prop('disabled', false);
        } else {
            $('#clear_days').prop('disabled', true);
        }
    });
    
    // Clear logs button
    $('#clear-logs-btn').click(function() {
        var button = $(this);
        var resultDiv = $('#clear-logs-result');
        var clearType = $('input[name="clear_type"]:checked').val();
        var days = $('#clear_days').val();
        
        // Show confirmation dialog
        var confirmMessage = clearType === 'all' 
            ? 'Are you sure you want to clear ALL logs? This action cannot be undone.'
            : 'Are you sure you want to clear logs older than ' + days + ' days? This action cannot be undone.';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        button.prop('disabled', true).text('Clearing logs...');
        resultDiv.html('');
        
        $.ajax({
            url: wp_mm_slash_jira.ajax_url,
            method: 'POST',
            data: {
                action: 'clear_mm_jira_logs',
                nonce: wp_mm_slash_jira.ajax_nonce,
                clear_type: clearType,
                days: days
            },
            success: function(data) {
                if (data.success) {
                    resultDiv.html('<p style="color: green;">✅ ' + data.data.message + '</p>');
                    // Refresh the page after a short delay to show updated statistics
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    resultDiv.html('<p style="color: red;">❌ ' + data.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Error clearing logs: ' + error;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                resultDiv.html('<p style="color: red;">❌ ' + errorMessage + '</p>');
            },
            complete: function() {
                button.prop('disabled', false).text('🗑️ Clear Logs');
            }
        });
    });
}); 