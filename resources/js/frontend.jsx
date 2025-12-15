import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './frontend/App';
import '../css/app.css';

// Error boundary for better error handling
class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('React Error:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div style={{ padding: '20px', fontFamily: 'sans-serif' }}>
                    <h1>Something went wrong</h1>
                    <p>Error: {this.state.error?.message || 'Unknown error'}</p>
                    <pre style={{ background: '#f5f5f5', padding: '10px', overflow: 'auto' }}>
                        {this.state.error?.stack}
                    </pre>
                    <button onClick={() => window.location.reload()}>Reload Page</button>
                </div>
            );
        }

        return this.props.children;
    }
}

const appElement = document.getElementById('app');

if (!appElement) {
    console.error('App element not found! Make sure <div id="app"></div> exists in the HTML.');
    document.body.innerHTML = '<div style="padding: 20px;"><h1>Error: App element not found</h1><p>Make sure the HTML includes: &lt;div id="app"&gt;&lt;/div&gt;</p></div>';
} else {
    try {
        const root = ReactDOM.createRoot(appElement);
        root.render(
            <React.StrictMode>
                <ErrorBoundary>
                    <BrowserRouter>
                        <App />
                    </BrowserRouter>
                </ErrorBoundary>
            </React.StrictMode>
        );
    } catch (error) {
        console.error('Failed to render React app:', error);
        appElement.innerHTML = `
            <div style="padding: 20px; font-family: sans-serif;">
                <h1>Failed to Load Application</h1>
                <p>Error: ${error.message}</p>
                <pre style="background: #f5f5f5; padding: 10px; overflow: auto;">${error.stack}</pre>
                <button onclick="window.location.reload()">Reload Page</button>
            </div>
        `;
    }
}

