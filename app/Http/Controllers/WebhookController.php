<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class WebhookController extends Controller
{
    public function index()
    {
        $webhooks = Webhook::orderBy('created_at', 'desc')->get();
        return view('home', compact('webhooks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255|unique:webhooks',
            'script_path' => 'required|string',
            'parameters' => 'nullable|array'
        ]);

        $webhook = Webhook::create($validated + ['active' => true]);

        return response()->json($webhook);
    }

    public function toggle(Request $request, Webhook $webhook)
    {
        $webhook->update([
            'active' => $request->active
        ]);

        return response()->json([
            'success' => true,
            'active' => $webhook->active
        ]);
    }

    private function parsePythonArguments($scriptPath)
    {
        $fullPath = Storage::path($scriptPath);
        if (!file_exists($fullPath)) {
            return null;
        }

        $content = file_get_contents($fullPath);
        $parameters = [];

        // Extract description from ArgumentParser
        if (preg_match('/ArgumentParser\(description=[\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $parameters['description'] = $matches[1];
        }

        // Find all argument definitions with a more comprehensive pattern
        preg_match_all('/add_argument\(\s*[\'"]([^\'"]+)[\'"]((?:[^)]|[\r\n])*)\)/', $content, $matches, PREG_SET_ORDER);

        $parameters['arguments'] = [];
        foreach ($matches as $match) {
            $argName = $match[1];
            $argDef = $match[2];

            // Extract help text if present
            $help = '';
            if (preg_match('/help\s*=\s*[\'"]([^\'"]+)[\'"]/', $argDef, $helpMatch)) {
                $help = $helpMatch[1];
            }

            // Determine argument type and name
            $type = 'value';
            $name = $argName;

            // Check if it's a flag argument (starts with --)
            if (strpos($argName, '--') === 0) {
                // Check if it's a flag by looking for action='store_true' or similar
                if (preg_match('/action\s*=\s*[\'"]store_(true|false)[\'"]/', $argDef)) {
                    $type = 'flag';
                } else {
                    $type = 'value';
                }
                $name = $argName;
            } else {
                $type = 'positional';
                $name = $argName;
            }

            // Check if it's required
            $required = $type === 'positional'; // Positional args are required by default

            // Look for required=True/False in argument definition
            if (preg_match('/required\s*=\s*(True|False)/i', $argDef, $reqMatch)) {
                $required = strtolower($reqMatch[1]) === 'true';
            }

            // Also check for optional parameters (those with default values)
            if (preg_match('/default\s*=/', $argDef)) {
                $required = false;
            }

            $parameters['arguments'][] = [
                'name' => $name,
                'type' => $type,
                'help' => $help,
                'required' => $required
            ];
        }

        return $parameters;
    }

    public function uploadScript(Request $request)
    {
        try {
            if (!$request->hasFile('script')) {
                return response()->json([
                    'error' => 'No file uploaded',
                    'status' => 'error'
                ], 400);
            }

            $file = $request->file('script');
            $extension = strtolower($file->getClientOriginalExtension());

            // Validate file extension
            if (!in_array($extension, ['py', 'txt'])) {
                return response()->json([
                    'error' => 'Invalid file type. Only .py and .txt files are allowed.',
                    'debug_info' => [
                        'original_name' => $file->getClientOriginalName(),
                        'extension' => $extension,
                        'mime_type' => $file->getMimeType(),
                        'guessed_extension' => $file->guessExtension()
                    ],
                    'status' => 'error'
                ], 400);
            }

            // Ensure upload directory exists
            if (!Storage::exists('uploaded-scripts')) {
                Storage::makeDirectory('uploaded-scripts');
            }

            // Generate unique filename
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.py';
            $path = 'uploaded-scripts/' . $filename;

            // Store the file
            if (!$file->storeAs('', $path)) {
                throw new \Exception('Failed to store file');
            }

            // Verify file was stored
            if (!Storage::exists($path)) {
                throw new \Exception('File not found after storage');
            }

            // Set file permissions
            chmod(Storage::path($path), 0644);

            // Parse Python arguments if it's a Python file
            $parameters = null;
            if ($extension === 'py') {
                $parameters = $this->parsePythonArguments($path);
            }

            return response()->json([
                'path' => $path,
                'parameters' => $parameters,
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload script',
                'message' => $e->getMessage(),
                'debug_info' => [
                    'original_name' => $file->getClientOriginalName() ?? 'unknown',
                    'target_path' => $path ?? 'unknown',
                    'upload_dir_exists' => Storage::exists('uploaded-scripts'),
                    'upload_dir_writable' => is_writable(Storage::path('uploaded-scripts'))
                ],
                'status' => 'error'
            ], 500);
        }
    }

    public function listScripts()
    {
        // List scripts from the correct directory
        $files = Storage::files('uploaded-scripts');
        return response()->json([
            'files' => $files
        ]);
    }

    public function execute(Webhook $webhook, Request $request)
    {
        // Check if webhook is active
        if (!$webhook->active) {
            return response()->json([
                'error' => 'This webhook is not active',
                'status' => 'error'
            ], 403);
        }

        try {
            // Get the script path and ensure it exists
            $scriptPath = $webhook->script_path;
            $fullScriptPath = Storage::path($scriptPath);

            if (!file_exists($fullScriptPath)) {
                return response()->json([
                    'error' => 'Script not found',
                    'message' => 'Script file not found',
                    'debug_info' => [
                        'script_path' => $scriptPath,
                        'full_path' => $fullScriptPath,
                        'exists' => file_exists($fullScriptPath),
                        'is_readable' => is_readable($fullScriptPath),
                        'storage_exists' => Storage::exists($scriptPath),
                        'files_in_directory' => Storage::files('uploaded-scripts')
                    ],
                    'status' => 'error'
                ], 404);
            }

            // Use the exact Python path we found
            $pythonPath = 'C:\\Python310\\python.exe';

            if (!file_exists($pythonPath)) {
                return response()->json([
                    'error' => 'Python executable not found',
                    'message' => 'Expected Python at: ' . $pythonPath,
                    'status' => 'error'
                ], 500);
            }

            // Build command arguments based on parameters
            $args = [$pythonPath, $fullScriptPath];

            // Get parameters from webhook definition
            $parameterDefs = $webhook->parameters ?? [];

            // Get request parameters (both GET and POST)
            $requestParams = array_merge($request->query(), $request->post());

            // Check for missing required parameters
            $missingParams = [];
            foreach ($parameterDefs as $param) {
                // Skip non-required parameters
                // Handle required parameters as strings or booleans
                if ($param['required'] !== 'true' && $param['required'] !== true) {
                    continue;
                }

                // For flag parameters, they're only required if they need to be true
                if ($param['type'] === 'flag') {
                    continue;
                }

                // Check if parameter is missing or empty
                if (!isset($requestParams[$param['name']]) || $requestParams[$param['name']] === '') {
                    $missingParams[] = $param['name'];
                }
            }

            if (!empty($missingParams)) {
                return response()->json([
                    'error' => 'Missing required parameters',
                    'message' => 'The following parameters are required: ' . implode(', ', $missingParams),
                    'debug_info' => [
                        'provided_params' => $requestParams,
                        'required_params' => array_filter($parameterDefs, fn($p) => $p['required'] ?? false),
                    ],
                    'status' => 'error'
                ], 400);
            }

            // Add parameters based on webhook parameter definitions
            foreach ($parameterDefs as $param) {
                $paramName = $param['name'];
                if (isset($requestParams[$paramName])) {
                    // Add parameter based on type
                    switch ($param['type']) {
                        case 'flag':
                            if ($requestParams[$paramName]) {
                                // For flag parameters, only add the name
                                $args[] = $paramName;
                            }
                            break;
                        case 'value':
                            // For value parameters, add both name and value
                            $args[] = $paramName;
                            $args[] = $requestParams[$paramName];
                            break;
                        case 'positional':
                            // For positional parameters, just add the value
                            $args[] = $requestParams[$paramName];
                            break;
                    }
                }
            }

            // Create a new process with proper environment
            $process = new Process($args);

            // Set environment variables
            $process->setEnv([
                'PYTHONPATH' => dirname($fullScriptPath),
                'PYTHONHOME' => 'C:\\Python310',
                'PATH' => getenv('PATH'),
                'SystemRoot' => getenv('SystemRoot'),
                'TEMP' => getenv('TEMP'),
                'TMP' => getenv('TMP')
            ]);

            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                return response()->json([
                    'error' => 'Script execution failed',
                    'output' => $process->getErrorOutput(),
                    'command' => $process->getCommandLine(),
                    'env' => $process->getEnv(),
                    'debug_info' => [
                        'script_exists' => file_exists($fullScriptPath),
                        'script_readable' => is_readable($fullScriptPath),
                        'script_permissions' => substr(sprintf('%o', fileperms($fullScriptPath)), -4),
                    ],
                    'status' => 'error'
                ], 500);
            }

            return response()->json([
                'output' => $process->getOutput(),
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to execute script',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function update(Request $request, Webhook $webhook)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255|unique:webhooks,url,' . $webhook->id,
            'script_path' => 'required|string',
            'parameters' => 'nullable|array'
        ]);

        $webhook->update($validated);

        return response()->json([
            'message' => 'Webhook updated successfully',
            'webhook' => $webhook,
            'status' => 'success'
        ]);
    }

    public function destroy(Webhook $webhook)
    {
        try {
            // Delete the associated script file if it exists
            if ($webhook->script_path && Storage::exists($webhook->script_path)) {
                Storage::delete($webhook->script_path);
            }

            $webhook->delete();

            return response()->json([
                'message' => 'Webhook deleted successfully',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete webhook',
                'message' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}
