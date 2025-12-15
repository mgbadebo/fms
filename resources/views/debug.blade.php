<!DOCTYPE html>
<html>
<head>
    <title>FMS Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .error { color: red; }
        .success { color: green; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>FMS Debug Information</h1>
    
    <h2>1. Check if app element exists</h2>
    <div id="app-check"></div>
    
    <h2>2. Check Vite assets</h2>
    <div id="vite-check"></div>
    
    <h2>3. Check JavaScript errors</h2>
    <div id="js-check"></div>
    
    <h2>4. Check API connectivity</h2>
    <div id="api-check"></div>
    
    <script>
        // Check app element
        const appEl = document.getElementById('app');
        if (appEl) {
            document.getElementById('app-check').innerHTML = '<span class="success">✓ App element found</span>';
        } else {
            document.getElementById('app-check').innerHTML = '<span class="error">✗ App element NOT found</span>';
        }
        
        // Check for Vite manifest
        fetch('/build/manifest.json')
            .then(r => r.json())
            .then(data => {
                document.getElementById('vite-check').innerHTML = '<span class="success">✓ Vite manifest found</span><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(e => {
                document.getElementById('vite-check').innerHTML = '<span class="error">✗ Vite manifest not found: ' + e.message + '</span>';
            });
        
        // Check for JavaScript errors
        window.addEventListener('error', (e) => {
            document.getElementById('js-check').innerHTML = '<span class="error">✗ JavaScript Error:</span><pre>' + e.message + '\n' + e.filename + ':' + e.lineno + '</pre>';
        });
        
        // Check API
        fetch('/api/v1/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: 'test@test.com', password: 'test' })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('api-check').innerHTML = '<span class="success">✓ API is accessible</span><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(e => {
            document.getElementById('api-check').innerHTML = '<span class="error">✗ API error: ' + e.message + '</span>';
        });
    </script>
</body>
</html>

