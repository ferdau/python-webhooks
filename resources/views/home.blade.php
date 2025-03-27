<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhooks Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #4a5568;
            transition: .4s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #10B981;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            overflow-y: auto;
        }
        .modal.show {
            display: flex;
        }
        .modal > div {
            width: 100%;
            max-width: 32rem;
            margin: 2rem auto;
            background-color: #1F2937;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
        }
        .file-picker {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            background-color: #1F2937;
            z-index: 60;
            border-radius: 0.5rem;
            overflow-y: auto;
        }
        .file-picker.show {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        Overview
                    </h1>
                    <p class="text-gray-400 text-sm mt-1">All your configured webhooks are listed below.</p>
                </div>
                <button id="createWebhookBtn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md">
                    Create Webhook
                </button>
            </div>
        </div>
    </div>

    <!-- Create Webhook Modal -->
    <div id="createWebhookModal" class="modal">
        <div class="bg-gray-800 w-full max-w-2xl mx-auto mt-20 rounded-lg shadow-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Create Webhook</h2>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="createWebhookForm" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Name</label>
                        <input type="text" name="name" id="webhookName" class="w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:border-red-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">URL</label>
                        <input type="text" name="url" id="webhookUrl" class="w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:border-red-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Python Script</label>
                        <div class="flex items-center space-x-4">
                            <input type="text" name="script_path" id="scriptPath" class="flex-1 bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:border-red-500" readonly required>
                            <input type="file" id="scriptUpload" class="hidden" accept=".py,.txt">
                            <button type="button" id="uploadScriptBtn" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Upload</button>
                        </div>
                        <div id="uploadError" class="mt-2 text-red-500 text-sm hidden"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Parameters</label>
                        <div id="parameters" class="space-y-4">
                            <!-- Parameters will be added here -->
                        </div>
                        <button type="button" id="addParameterBtn" class="mt-4 bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Add Parameter</button>
                    </div>
                    <div class="flex justify-end mt-6">
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-md">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Server File Browser Modal -->
    <div id="fileBrowserModal" class="file-picker">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium">Select Script</h3>
                <button id="closeFileBrowser" class="text-gray-400 hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="fileList" class="space-y-2">
                <!-- Files will be loaded here -->
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            @if($webhooks->isEmpty())
                <div class="bg-gray-800 rounded-lg p-8 text-center">
                    <p class="text-gray-400 text-lg">It's quiet, too quiet!</p>
                    <p class="text-gray-500 mt-2">No webhooks created yet.</p>
                </div>
            @else
                @foreach($webhooks as $webhook)
                    <div class="bg-gray-800 rounded-lg mb-4 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold cursor-pointer hover:text-gray-300" onclick="openEditWebhook('{{ $webhook->id }}', '{{ $webhook->name }}', '{{ $webhook->url }}', '{{ $webhook->script_path }}', '{{ json_encode($webhook->parameters) }}')">{{ $webhook->name }}</h3>
                                <div class="flex items-center gap-2 mt-1 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 cursor-pointer hover:text-gray-200" viewBox="0 0 20 20" fill="currentColor" onclick="copyToClipboard('{{ url('/webhooks/' . $webhook->url) }}')" title="Copy URL">
                                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                                    </svg>
                                    <a href="{{ url('/webhooks/' . $webhook->url) }}" target="_blank" class="text-sm hover:text-gray-200">{{ url('/webhooks/' . $webhook->url) }}</a>
                                </div>
                                <p class="text-gray-400 text-sm mt-1">
                                    Last updated {{ $webhook->updated_at->diffForHumans() }} | Created {{ $webhook->created_at->format('j F') }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm {{ $webhook->active ? 'text-green-400' : 'text-gray-400' }}">
                                    {{ $webhook->active ? 'Active' : 'Inactive' }}
                                </span>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           {{ $webhook->active ? 'checked' : '' }}
                                           data-webhook-id="{{ $webhook->id }}"
                                           class="webhook-toggle">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <button onclick="deleteWebhook('{{ $webhook->id }}')" class="mt-4 text-red-400 hover:text-red-300 text-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Webhook
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle webhook active state
            $('.webhook-toggle').on('change', function() {
                const webhookId = $(this).data('webhook-id');
                const isActive = $(this).prop('checked');
                
                $.ajax({
                    url: `/webhooks/${webhookId}/toggle`,
                    type: 'POST',
                    data: {
                        active: isActive,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const statusText = $(this).closest('.flex').find('span').first();
                        statusText.text(isActive ? 'Active' : 'Inactive');
                        statusText.toggleClass('text-green-400 text-gray-400');
                    }.bind(this),
                    error: function() {
                        // Revert the toggle if the request fails
                        $(this).prop('checked', !isActive);
                    }.bind(this)
                });
            });

            // Create webhook modal
            const modal = $('#createWebhookModal');
            const fileBrowser = $('#fileBrowserModal');

            $('#createWebhookBtn').click(function() {
                modal.addClass('show');
            });

            $('#closeModal').click(function() {
                modal.removeClass('show');
            });

            // Auto-generate URL from name
            $('#webhookName').on('input', function() {
                const name = $(this).val();
                const url = name.toLowerCase()
                    .replace(/[^a-z0-9-\s]/g, '')
                    .replace(/\s+/g, '-');
                $('#webhookUrl').val(url);
            });

            // Handle file upload
            $('#uploadScriptBtn').click(function() {
                $('#scriptUpload').click();
            });

            $('#scriptUpload').change(function() {
                const file = this.files[0];
                if (!file) return;

                // Clear previous error
                $('#uploadError').addClass('hidden');

                const formData = new FormData();
                formData.append('script', file);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    url: '/webhooks/upload-script',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#scriptPath').val(response.path);
                        $('#scriptUpload').val('');

                        // Clear existing parameters
                        $('#parameters').empty();

                        // If we have parameters from the Python script, add them
                        if (response.parameters && response.parameters.arguments) {
                            // Add script description if available
                            if (response.parameters.description) {
                                $('#parameters').append(`
                                    <div class="mb-4 p-4 bg-gray-700 rounded-md">
                                        <h4 class="text-sm font-medium text-gray-300 mb-2">Script Description</h4>
                                        <p class="text-gray-400 text-sm">${response.parameters.description}</p>
                                    </div>
                                `);
                            }

                            // Add each parameter
                            response.parameters.arguments.forEach(param => {
                                $('#addParameterBtn').click();
                                const lastRow = $('.parameter-row').last();
                                lastRow.find('.param-name').val(param.name);
                                lastRow.find('.param-type').val(param.type);
                                lastRow.find('.param-required').prop('checked', param.required);
                                lastRow.find('.param-description').val(param.help);
                            });
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.error || 'Failed to upload script';
                        $('#uploadError').text(error).removeClass('hidden');
                    }
                });
            });

            // Handle form submission
            $('#createWebhookForm').submit(function(e) {
                e.preventDefault();
                const webhookId = $(this).data('webhook-id');
                const isEdit = !!webhookId;
                
                // Collect parameters
                const parameters = [];
                $('.parameter-row').each(function() {
                    const name = $(this).find('.param-name').val();
                    const type = $(this).find('.param-type').val();
                    const required = $(this).find('.param-required').prop('checked');
                    const description = $(this).find('.param-description').val();

                    // Ensure name has -- prefix for non-positional arguments
                    const finalName = type === 'positional' ? name.replace(/^--/, '') : name;
                    
                    parameters.push({
                        name: finalName,
                        type: type,
                        required: required,
                        description: description
                    });
                });
                
                const formData = {
                    name: $('#webhookName').val(),
                    url: $('#webhookUrl').val(),
                    script_path: $('#scriptPath').val(),
                    parameters: parameters,
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: isEdit ? `/webhooks/${webhookId}` : '/webhooks',
                    type: isEdit ? 'PUT' : 'POST',
                    data: formData,
                    success: function() {
                        window.location.reload();
                    },
                    error: function() {
                        alert(isEdit ? 'Failed to update webhook' : 'Failed to create webhook');
                    }
                });
            });

            // Reset form when opening create modal
            $('.create-webhook-btn').click(function() {
                $('#createWebhookForm').data('webhook-id', '');
                $('#createWebhookForm')[0].reset();
                $('#createWebhookModal h2').text('Create New Webhook');
                $('#createWebhookForm button[type="submit"]').text('Create Webhook');
            });

            // Add parameter row
            $('#addParameterBtn').click(function() {
                const paramRow = $(`
                    <div class="parameter-row bg-gray-700 p-4 rounded-md">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Name</label>
                                <input type="text" class="param-name w-full bg-gray-600 border border-gray-500 rounded-md px-3 py-2 text-white focus:outline-none focus:border-red-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Type</label>
                                <select class="param-type w-full bg-gray-600 border border-gray-500 rounded-md px-3 py-2 text-white focus:outline-none focus:border-red-500">
                                    <option value="value">Value (--param value)</option>
                                    <option value="flag">Flag (--param)</option>
                                    <option value="positional">Positional (value)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                            <input type="text" class="param-description w-full bg-gray-600 border border-gray-500 rounded-md px-3 py-2 text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" class="param-required bg-gray-600 border-gray-500 rounded">
                                <label class="ml-2 text-sm text-gray-400">Required</label>
                            </div>
                            <button type="button" class="delete-param text-red-400 hover:text-red-300">Delete</button>
                        </div>
                    </div>
                `);
                
                // Add delete handler
                paramRow.find('.delete-param').click(function() {
                    $(this).closest('.parameter-row').remove();
                });

                // Handle type change
                paramRow.find('.param-type').change(function() {
                    const type = $(this).val();
                    const nameInput = $(this).closest('.grid').find('.param-name');
                    const requiredCheckbox = $(this).closest('.parameter-row').find('.param-required');
                    
                    // Update name based on type
                    if (type === 'positional') {
                        nameInput.val(nameInput.val().replace(/^--/, ''));
                        // Positional arguments are always required
                        requiredCheckbox.prop('checked', true);
                        requiredCheckbox.prop('disabled', true);
                    } else {
                        if (!nameInput.val().startsWith('--')) {
                            nameInput.val('--' + nameInput.val());
                        }
                        requiredCheckbox.prop('disabled', false);
                    }
                });
                
                $('#parameters').append(paramRow);
            });

            // Load existing parameters when editing
            function openEditWebhook(id, name, url, scriptPath, parameters) {
                // Set form values
                $('#webhookName').val(name);
                $('#webhookUrl').val(url);
                $('#scriptPath').val(scriptPath);
                
                // Clear existing parameters
                $('#parameters').empty();
                
                // Add existing parameters
                if (parameters) {
                    parameters.forEach(param => {
                        $('#addParameterBtn').click();
                        const lastRow = $('.parameter-row').last();
                        lastRow.find('.param-name').val(param.name);
                        lastRow.find('.param-type').val(param.type);
                        lastRow.find('.param-required').prop('checked', param.required);
                        lastRow.find('.param-description').val(param.description);
                    });
                }
                
                // Update form for edit mode
                $('#createWebhookForm').data('webhook-id', id);
                $('#createWebhookModal h2').text('Edit Webhook');
                $('#createWebhookForm button[type="submit"]').text('Update Webhook');
                
                // Show modal
                $('#createWebhookModal').addClass('show');
            }
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Optional: Show a brief notification that the URL was copied
                const notification = document.createElement('div');
                notification.textContent = 'URL copied to clipboard';
                notification.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background-color: #10B981; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;';
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }

        function deleteWebhook(webhookId) {
            if (confirm('Are you sure you want to delete this webhook? This action cannot be undone.')) {
                $.ajax({
                    url: `/webhooks/${webhookId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function() {
                        // Reload the page to show updated list
                        window.location.reload();
                    },
                    error: function() {
                        alert('Failed to delete webhook');
                    }
                });
            }
        }
    </script>
</body>
</html> 