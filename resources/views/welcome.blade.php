<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ogenki Farms - Farm Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            color: #667eea;
            font-size: 1.8em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .endpoint-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .endpoint-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .endpoint-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .endpoint-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        
        .endpoint-card .method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .method.get {
            background: #28a745;
            color: white;
        }
        
        .method.post {
            background: #007bff;
            color: white;
        }
        
        .method.put {
            background: #ffc107;
            color: #333;
        }
        
        .method.delete {
            background: #dc3545;
            color: white;
        }
        
        .endpoint-card .url {
            font-family: 'Courier New', monospace;
            background: #fff;
            padding: 8px;
            border-radius: 5px;
            margin-top: 10px;
            word-break: break-all;
            font-size: 0.9em;
            color: #495057;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .info-box h3 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        .code-block code {
            color: #f8f8f2;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 10px 10px 10px 0;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #28a745;
            color: white;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        .quick-start {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .quick-start h3 {
            color: white;
            margin-bottom: 15px;
        }
        
        .quick-start ol {
            margin-left: 20px;
            line-height: 2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="margin-bottom: 20px;">
                <img src="{{ asset('images/ogenki-logo.png') }}" alt="Ogenki Farms" style="max-height: 80px; max-width: 300px; margin: 0 auto; display: block;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <h1 style="display: none;">🌾 Ogenki Farms</h1>
            </div>
            <h1 style="margin-top: 20px;">Farm Management System</h1>
            <p>Backend API v1.0.0</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>Welcome</h2>
                <p style="font-size: 1.1em; line-height: 1.8; color: #555;">
                    Welcome to the Farm Management System API! This is a comprehensive backend API for managing farm operations, 
                    including crop management, livestock tracking, inventory, IoT sensors, scale integration, and label printing.
                </p>
            </div>

            <div class="section">
                <h2>🔐 Login</h2>
                <div style="max-width: 500px; margin: 20px 0;">
                    <form id="loginForm" style="background: #f8f9fa; padding: 30px; border-radius: 10px;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Email</label>
                            <input type="email" id="email" name="email" required 
                                   style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1em;"
                                   placeholder="admin@fms.test" value="admin@fms.test">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Password</label>
                            <input type="password" id="password" name="password" required 
                                   style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1em;"
                                   placeholder="password" value="password">
                        </div>
                        <button type="submit" 
                                style="width: 100%; padding: 14px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 1.1em; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                            Login
                        </button>
                    </form>
                    
                    <div id="loginResult" style="margin-top: 20px; display: none;">
                        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 8px; padding: 20px; margin-top: 20px;">
                            <h3 style="color: #155724; margin-bottom: 15px;">✅ Login Successful!</h3>
                            <p style="color: #155724; margin-bottom: 10px;"><strong>Your API Token:</strong></p>
                            <div class="code-block" style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; word-break: break-all; font-family: monospace; margin: 10px 0;">
                                <code id="tokenDisplay"></code>
                            </div>
                            <p style="color: #155724; margin-top: 15px; font-size: 0.9em;">
                                Copy this token and use it in the <code style="background: #fff; padding: 2px 6px; border-radius: 4px;">Authorization: Bearer {token}</code> header for API requests.
                            </p>
                            <button onclick="copyToken()" 
                                    style="margin-top: 10px; padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                📋 Copy Token
                            </button>
                        </div>
                    </div>
                    
                    <div id="loginError" style="display: none; background: #f8d7da; border: 2px solid #dc3545; border-radius: 8px; padding: 15px; margin-top: 20px; color: #721c24;">
                        <strong>❌ Login Failed:</strong> <span id="errorMessage"></span>
                    </div>
                </div>
            </div>

            <div class="quick-start">
                <h3>🚀 Quick Start</h3>
                <ol>
                    <li>Get your authentication token by logging in</li>
                    <li>Use the token in the <code>Authorization: Bearer {token}</code> header</li>
                    <li>Start making API requests to manage your farm data</li>
                </ol>
            </div>

            <div class="section">
                <h2>Authentication</h2>
                <div class="endpoint-grid">
                    <div class="endpoint-card">
                        <h3>
                            <span class="method post">POST</span>
                            Login
                        </h3>
                        <div class="url">/api/v1/login</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Authenticate and receive an API token
                        </p>
                    </div>
                    <div class="endpoint-card">
                        <h3>
                            <span class="method post">POST</span>
                            Register
                        </h3>
                        <div class="url">/api/v1/register</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Create a new user account
                        </p>
                    </div>
                </div>
                
                <div class="info-box">
                    <h3>Example Login Request</h3>
                    <div class="code-block">
<code>curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@fms.test",
    "password": "password"
  }'</code>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Core Endpoints <span class="badge">Protected</span></h2>
                <div class="endpoint-grid">
                    <div class="endpoint-card">
                        <h3>
                            <span class="method get">GET</span>
                            <span class="method post">POST</span>
                            Farms
                        </h3>
                        <div class="url">/api/v1/farms</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Manage farm entities
                        </p>
                    </div>
                    <div class="endpoint-card">
                        <h3>
                            <span class="method get">GET</span>
                            <span class="method post">POST</span>
                            Harvest Lots
                        </h3>
                        <div class="url">/api/v1/harvest-lots</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Track harvest operations
                        </p>
                    </div>
                    <div class="endpoint-card">
                        <h3>
                            <span class="method get">GET</span>
                            <span class="method post">POST</span>
                            Scale Devices
                        </h3>
                        <div class="url">/api/v1/scale-devices</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Manage weighing scale devices
                        </p>
                    </div>
                    <div class="endpoint-card">
                        <h3>
                            <span class="method post">POST</span>
                            Scale Readings
                        </h3>
                        <div class="url">/api/v1/scale-readings</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Get weight from scale (mock implementation)
                        </p>
                    </div>
                    <div class="endpoint-card">
                        <h3>
                            <span class="method get">GET</span>
                            <span class="method post">POST</span>
                            Label Templates
                        </h3>
                        <div class="url">/api/v1/label-templates</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Manage label templates for printing
                        </p>
                    </div>
                    <div class="endpoint-card">
                        <h3>
                            <span class="method post">POST</span>
                            Print Label
                        </h3>
                        <div class="url">/api/v1/labels/print</div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                            Print labels for harvest lots, storage units, etc.
                        </p>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Example API Usage</h2>
                <div class="info-box">
                    <h3>1. Create a Farm</h3>
                    <div class="code-block">
<code>curl -X POST http://127.0.0.1:8000/api/v1/farms \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Green Valley Farm",
    "location": "123 Farm Road",
    "total_area": 50.5,
    "area_unit": "hectares"
  }'</code>
                    </div>
                </div>

                <div class="info-box">
                    <h3>2. Get Weight from Scale</h3>
                    <div class="code-block">
<code>curl -X POST http://127.0.0.1:8000/api/v1/scale-readings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "scale_device_id": 1,
    "context_type": "App\\Models\\HarvestLot",
    "context_id": 1,
    "unit": "kg"
  }'</code>
                    </div>
                </div>

                <div class="info-box">
                    <h3>3. Print a Label</h3>
                    <div class="code-block">
<code>curl -X POST http://127.0.0.1:8000/api/v1/labels/print \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "label_template_id": 1,
    "target_type": "App\\Models\\HarvestLot",
    "target_id": 1
  }'</code>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Features</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong>🌾 Crop Management</strong>
                        <p style="margin-top: 5px; color: #666; font-size: 0.9em;">Crop plans, scouting logs, harvest lots</p>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong>🐄 Livestock Tracking</strong>
                        <p style="margin-top: 5px; color: #666; font-size: 0.9em;">Animals, health records, breeding events</p>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong>⚖️ Scale Integration</strong>
                        <p style="margin-top: 5px; color: #666; font-size: 0.9em;">Digital scale integration (mock available)</p>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong>🏷️ Label Printing</strong>
                        <p style="margin-top: 5px; color: #666; font-size: 0.9em;">QR codes, barcodes, traceability</p>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong>📊 Inventory Management</strong>
                        <p style="margin-top: 5px; color: #666; font-size: 0.9em;">Stock movements, locations, tracking</p>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong>💰 Financial Tracking</strong>
                        <p style="margin-top: 5px; color: #666; font-size: 0.9em;">Transactions, budgets, reporting</p>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Resources</h2>
                <a href="https://github.com" class="btn" target="_blank">📚 Full Documentation</a>
                <a href="/api/v1/login" class="btn btn-secondary">🔐 API Login</a>
                <a href="https://laravel.com/docs" class="btn btn-secondary" target="_blank">Laravel Docs</a>
            </div>
        </div>

        <div class="footer">
            <p><strong>Farm Management System</strong> - Built with Laravel {{ app()->version() }}</p>
            <p style="margin-top: 10px; font-size: 0.9em;">
                For API documentation and examples, see the <code>README.md</code> file
            </p>
        </div>
    </div>

    <script>
        let currentToken = '';
        
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const resultDiv = document.getElementById('loginResult');
            const errorDiv = document.getElementById('loginError');
            
            // Hide previous results
            resultDiv.style.display = 'none';
            errorDiv.style.display = 'none';
            
            try {
                const response = await fetch('/api/v1/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (response.ok && data.token) {
                    currentToken = data.token;
                    document.getElementById('tokenDisplay').textContent = data.token;
                    resultDiv.style.display = 'block';
                    errorDiv.style.display = 'none';
                    
                    // Scroll to result
                    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    throw new Error(data.message || 'Login failed');
                }
            } catch (error) {
                document.getElementById('errorMessage').textContent = error.message;
                errorDiv.style.display = 'block';
                resultDiv.style.display = 'none';
            }
        });
        
        function copyToken() {
            if (currentToken) {
                navigator.clipboard.writeText(currentToken).then(function() {
                    alert('Token copied to clipboard!');
                }, function() {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = currentToken;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('Token copied to clipboard!');
                });
            }
        }
    </script>
</body>
</html>
