<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python Webhooks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.js"></script>
    <style>
        body {
            background-color: #1b1c1d !important;
            color: #ffffff !important;
        }
        .ui.segment {
            background-color: #2d2d2d !important;
            border: 1px solid #3d3d3d !important;
        }
        .ui.divided.items > .item {
            border-color: #3d3d3d !important;
        }
        .ui.divided.items > .item:last-child {
            border-color: #3d3d3d !important;
        }
        .ui.toggle.checkbox input:checked ~ .box:before,
        .ui.toggle.checkbox input:checked ~ label:before {
            background-color: #2185d0 !important;
        }
        .ui.toggle.checkbox .box:before,
        .ui.toggle.checkbox label:before {
            background-color: #4a4a4a !important;
            border-color: #666666 !important;
        }
        .ui.primary.button {
            background-color: #2185d0 !important;
            box-shadow: 0 2px 4px rgba(33, 133, 208, 0.3) !important;
        }
        .ui.primary.button:hover {
            background-color: #1678c2 !important;
            box-shadow: 0 4px 8px rgba(33, 133, 208, 0.4) !important;
        }
        .ui.container {
            max-width: 800px !important;
        }
        .item .header {
            color: #ffffff !important;
        }
        .item .meta {
            color: #a0a0a0 !important;
        }
        .item .description {
            color: #e0e0e0 !important;
        }
        .ui.toggle.checkbox label {
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <div class="ui container" style="margin-top: 2em;">
        <div class="ui segment">
            <h1 class="ui header" style="color: #ffffff; margin-bottom: 1em;">
                Python Webhooks
            </h1>
            @yield('content')
        </div>
    </div>
</body>
</html> 