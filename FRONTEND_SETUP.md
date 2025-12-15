# Frontend Setup Guide

The Farm Management System now includes a modern React-based frontend for farm managers and operators.

## Features

- **Dashboard**: Overview of farms, harvest lots, and scale devices
- **Farm Management**: Create and manage farms with fields and seasons
- **Harvest Lot Tracking**: Track harvest operations with weight and quality data
- **Scale Integration**: Connect and use digital scales for weighing
- **Label Printing**: Print labels with traceability information
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Modern UI**: Clean, intuitive interface built with React and Tailwind CSS

## Installation

### 1. Install Dependencies

```bash
npm install
```

This will install:
- React 18
- React Router DOM
- Axios (for API calls)
- Lucide React (icons)
- Tailwind CSS (already configured)

### 2. Build for Development

```bash
npm run dev
```

This starts Vite in development mode with hot module replacement.

### 3. Build for Production

```bash
npm run build
```

This compiles and optimizes the React app for production.

## Development

The frontend is a Single Page Application (SPA) that connects to your Laravel API.

### File Structure

```
resources/
├── js/
│   ├── frontend.jsx          # React entry point
│   └── frontend/
│       ├── App.jsx           # Main app component with routing
│       ├── contexts/         # React contexts (Auth)
│       ├── components/       # Reusable components (Layout)
│       ├── pages/            # Page components
│       └── utils/            # Utilities (API client)
└── views/
    └── app.blade.php         # Blade template that serves React app
```

### Authentication

The frontend uses Laravel Sanctum tokens stored in localStorage. When a user logs in:
1. Token is stored in localStorage
2. Token is added to all API requests via Axios interceptors
3. On 401 errors, user is redirected to login

### API Integration

All API calls go through `/resources/js/frontend/utils/api.js` which:
- Sets base URL to current origin
- Adds authentication token to headers
- Handles errors and redirects on 401

## Deployment

### Production Build

1. **Build the frontend:**
   ```bash
   npm run build
   ```

2. **The build output goes to `public/build/`** and is automatically served by Laravel

3. **Ensure Vite assets are published:**
   ```bash
   php artisan vendor:publish --tag=laravel-assets
   ```

### Server Requirements

- Node.js and npm (for building)
- PHP 8.2+ (for Laravel)
- The built assets are static files served by your web server

### Nginx Configuration

Make sure your Nginx config serves the Laravel app correctly:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/fms/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Usage

### Accessing the Frontend

1. Navigate to your production URL (e.g., `https://yourdomain.com`)
2. You'll see the login page
3. Use your API credentials to log in
4. Default demo credentials: `admin@fms.test` / `password`

### Key Pages

- **Dashboard** (`/`): Overview of farm operations
- **Farms** (`/farms`): Manage farms
- **Harvest Lots** (`/harvest-lots`): Track harvest operations
- **Scale Devices** (`/scale-devices`): Manage weighing scales
- **Label Templates** (`/label-templates`): View label templates

### Creating a Harvest Lot

1. Go to "Harvest Lots"
2. Click "New Harvest Lot"
3. Select farm and enter harvest details
4. After creation, you can:
   - Weigh the harvest using a scale device
   - Print a label with traceability information

## Customization

### Styling

The app uses Tailwind CSS. Modify styles in:
- `resources/css/app.css` - Global styles
- Component files - Inline Tailwind classes

### Adding New Pages

1. Create a new component in `resources/js/frontend/pages/`
2. Add route in `resources/js/frontend/App.jsx`
3. Add navigation link in `resources/js/frontend/components/Layout.jsx`

### API Endpoints

The frontend uses these API endpoints:
- `POST /api/v1/login` - Authentication
- `GET /api/v1/farms` - List farms
- `GET /api/v1/harvest-lots` - List harvest lots
- `POST /api/v1/scale-readings` - Read from scale
- `POST /api/v1/labels/print` - Print label

See `routes/api.php` for all available endpoints.

## Troubleshooting

### Frontend not loading

- Check that `npm run build` completed successfully
- Verify `public/build/` directory exists
- Clear Laravel cache: `php artisan cache:clear`

### API calls failing

- Check browser console for errors
- Verify API base URL in `resources/js/frontend/utils/api.js`
- Ensure CORS is configured if frontend is on different domain

### Authentication issues

- Check that token is stored in localStorage
- Verify API returns token in correct format
- Check browser Network tab for 401 errors

## Next Steps

- Add more features (crop management, livestock tracking, etc.)
- Implement real-time updates with WebSockets
- Add data visualization and charts
- Create mobile app using React Native
- Add offline support with service workers

