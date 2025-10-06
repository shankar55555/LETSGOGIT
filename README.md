# ModularCrm - Development CRM System

A modern CRM system built with Laravel backend and Vue.js frontend.

## Tech Stack

- Backend: Laravel
- Frontend: Vue.js 3 + Vite + Vuexy Theme
- Database: PostgreSQL
- Theme: Vuexy Vue.js Admin Template
- Module System: Laravel Modules

## Theme and Module Information

### Vuexy Theme
- **Theme URL**: [Vuexy Vue.js Admin Template](https://demos.pixinvent.com/vuexy-vuejs-admin-template/demo-1/dashboards/crm)
- **Upgrade**: Upgrading Laravel from version 11 to 12 within the theme.

### Laravel Modules
- **Reference**: [Laravel Modules Documentation](https://laravelmodules.com/)
- **Purpose**: Managing different application modules (e.g., Leads, Invoices, etc.)

## Prerequisites

- PHP >= 8.1
- Node.js >= 16
- PostgreSQL
- Composer
- npm
- XAMPP (for local development)

## XAMPP Setup

1. Download and install [XAMPP](https://www.apachefriends.org/download.html)
2. Start Apache and MySQL services from XAMPP Control Panel
3. Configure PHP version in XAMPP:
   - Open XAMPP Control Panel
   - Click on "Config" button next to Apache
   - Select "PHP (php.ini)"
   - Make sure these extensions are uncommented (remove the semicolon if present):
     ```
     extension=pdo_pgsql
     extension=pgsql
     extension=zip
     extension=fileinfo
     extension=mbstrin
     extension=gd
     ```
   - Save the file and restart Apache

## PostgreSQL Setup

1. Download and install [PostgreSQL](https://www.postgresql.org/download/)
2. During installation:
   - Note down the password you set for the postgres user
   - Keep the default port (5432)
3. After installation:
   - Open pgAdmin 4
   - Create a new database for the project

## Project Setup

### Backend Setup

1. Clone the repository
2. Install PHP dependencies:
   ```sh
   composer install
   ```

3. Copy the environment file:
   ```sh
   cp .env.example .env
   ```

4. Generate application key:
   ```sh
   php artisan key:generate
   ```

5. Configure your database in `.env`:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=your_database_name
   DB_USERNAME=your_user_name
   DB_PASSWORD=your_pasword
   ```

6. Run migrations:
   ```sh
   php artisan module:install fresh
   ```

7. Start the Laravel development server:
   ```sh
   php artisan serve
   ```

### Frontend Setup

1. Install Node.js dependencies:
   ```sh
   npm install
   ```

2. Start the Vite development server:
   ```sh
   npm run dev
   ```

## Development







### Module Configuration

#### Router Configuration
- Dynamic import of all module router files
- Automatic route merging for all modules
- Example router configuration:
  ```javascript
  const moduleRoutes = import.meta.glob('@modules/*/resources/assets/js/router/index.{js,ts}', { eager: true })
  
  // Merge all routes (dynamic + additionalRoutes)
  const mergedModuleRoutes = Object.values(moduleRoutes).flatMap(mod => {
    const r = mod.default || []
    return Array.isArray(r) ? r : [r]
  })
  ```

#### Vite Configuration
- Module alias configuration for importing module pages
- Custom module path resolution
- Example Vite configuration:
  ```javascript
  '@modules': fileURLToPath(new URL('./Modules', import.meta.url))
  ```

#### Composer Configuration
- Module-specific composer.json files integration
- Automatic module dependency management
- Example composer.json configuration:
  ```json
  "extra": {
      "merge-plugin": {
          "include": [
              "Modules/*/composer.json"
          ]
      }
  }
  ```






## Environment Variables  For Live

Key environment variables are already configured in `.env`:

- Database configuration
- Application settings

## Building for Production

### Frontend Build
```sh
npm run build
```

### Backend Optimization
```sh
php artisan optimize
```


## Reverb Configration For .env
1) Make sure Your Allow port 6001
   sudo ufw allow 6001
   

BROADCAST_CONNECTION=reverb

# Reverb WebSocket Server (Laravel backend)
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=6001

# Reverb Frontend Access
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https



## apache2 
   1) Add These Line after SSl configration 
 /etc/apache2/sites-available/your-domain.conf
  # WebSocket Proxy for Laravel Reverb
   <IfModule mod_proxy.c>
        ProxyRequests Off
        ProxyPreserveHost On

        # Enable WebSocket support
        RewriteEngine On
        RewriteCond %{HTTP:Upgrade} =websocket [NC]
        RewriteCond %{HTTP:Connection} upgrade [NC]
        RewriteRule ^/app/(.*) ws://127.0.0.1:6001/app/$1 [P,L]

        # Normal HTTP fallback for app requests
        ProxyPass /app/ http://127.0.0.1:6001/app/
        ProxyPassReverse /app/ http://127.0.0.1:6001/app/
        
        # Required for pusher-http-php to send events to Reverb
        ProxyPass /apps/ http://127.0.0.1:6001/apps/
        ProxyPassReverse /apps/ http://127.0.0.1:6001/apps/
   </IfModule>


 ### Install PM2 Global
npm install -g pm2
```


### Notification Job run in server using pm2
```sh
pm2 start php --name=laravel-queue -- artisan queue:work
```

## License

This project is licensed under the MIT License.
