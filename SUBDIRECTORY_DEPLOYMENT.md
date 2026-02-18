# Subdirectory Deployment Guide

## Quick Fix for Subdirectory Deployment

If you've deployed the application to a subdirectory (like `/portfolio/chatbot/`) and are experiencing issues, follow these steps:

### The Issue
When deployed to `https://mertcanyigit.com/portfolio/chatbot/`:
- ❌ Getting 403 Forbidden error
- ❌ Redirects go to wrong URLs (e.g., `/login.php` instead of `/portfolio/chatbot/login.php`)
- ❌ Assets not loading (CSS, JS files)

### The Solution (Automatic!)

The application now **auto-detects** the subdirectory and adjusts all paths automatically. No manual configuration needed!

## Deployment Steps

### 1. Upload Files
Upload all files to your subdirectory:
```
/home/your-user/public_html/portfolio/chatbot/
```

### 2. Run Installer
Navigate to:
```
https://mertcanyigit.com/portfolio/chatbot/install.php
```

The installer will:
- ✅ Auto-detect the base path (`/portfolio/chatbot`)
- ✅ Save it in the configuration
- ✅ Create database tables
- ✅ Set up admin account

### 3. Access Your Application
After installation:
```
https://mertcanyigit.com/portfolio/chatbot/
```

All paths will work correctly:
- Login: `/portfolio/chatbot/login.php`
- Assets: `/portfolio/chatbot/assets/css/main.css`
- API: `/portfolio/chatbot/api/chat.php`
- Admin: `/portfolio/chatbot/admin/`

## How It Works

### Auto-Detection
The `Url` helper class automatically detects your deployment path by examining `$_SERVER['SCRIPT_NAME']`. For example:

- **Script**: `/portfolio/chatbot/public/index.php`
- **Detected base**: `/portfolio/chatbot`

### No Configuration Needed
All URL generation uses the `Url` helper:
```php
Url::to('login.php')        // → /portfolio/chatbot/login.php
Url::asset('css/main.css')  // → /portfolio/chatbot/assets/css/main.css
Url::api('chat.php')        // → /portfolio/chatbot/api/chat.php
```

### JavaScript Support
The base path is passed to JavaScript:
```javascript
window.BASE_PATH = '/portfolio/chatbot';
this.url('/api/chat.php');  // → /portfolio/chatbot/api/chat.php
```

## Troubleshooting

### Still Getting 403 Forbidden?

Check your `.htaccess` file in the root of your subdirectory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Optional: Set base explicitly if auto-detection fails
    # RewriteBase /portfolio/chatbot/
    
    # Redirect to public folder
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### Assets Not Loading?

1. **Check file permissions**:
   ```bash
   chmod 755 -R portfolio/chatbot
   chmod 777 portfolio/chatbot/config
   ```

2. **Verify .htaccess is working**:
   - Ensure `mod_rewrite` is enabled in Apache
   - Check that `AllowOverride All` is set in your virtual host

3. **Test direct access**:
   ```
   https://mertcanyigit.com/portfolio/chatbot/public/assets/css/main.css
   ```
   If this works, the rewrite rules are the issue.

### Redirects Going to Root?

If redirects still go to root (`/login.php` instead of `/portfolio/chatbot/login.php`):

1. **Clear browser cache** - Old JavaScript may be cached
2. **Check config.php** - Ensure `base_path` is set:
   ```php
   'app' => [
       'base_path' => '/portfolio/chatbot',
       ...
   ]
   ```
3. **Re-run installer** - It will auto-detect and fix the path

### Manual Configuration

If auto-detection doesn't work, manually set the base path in `config/config.php`:

```php
return [
    'app' => [
        'name' => 'AI Chat',
        'url' => 'https://mertcanyigit.com',
        'base_path' => '/portfolio/chatbot',  // ← Add this
        'environment' => 'production',
        ...
    ],
    ...
];
```

## Apache Configuration

### Virtual Host Example

If you control the Apache configuration:

```apache
<VirtualHost *:80>
    ServerName mertcanyigit.com
    DocumentRoot /home/your-user/public_html
    
    # Set up the subdirectory
    Alias /portfolio/chatbot /home/your-user/public_html/portfolio/chatbot/public
    
    <Directory /home/your-user/public_html/portfolio/chatbot/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Optional: Set base explicitly
        RewriteEngine On
        RewriteBase /portfolio/chatbot/
    </Directory>
</VirtualHost>
```

### .htaccess in Public Directory

The `public/.htaccess` handles routing:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Serve existing files/directories
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]
    
    # Route everything else to index.php
    RewriteRule ^ index.php [L]
</IfModule>
```

## Testing Checklist

After deployment, test these URLs:

- [ ] Main page: `https://mertcanyigit.com/portfolio/chatbot/`
- [ ] Login page: `https://mertcanyigit.com/portfolio/chatbot/login.php`
- [ ] CSS loads: Check browser console for 404 errors
- [ ] Login works: Submit login form
- [ ] Redirects work: After login, check URL
- [ ] API calls work: Send a chat message
- [ ] Admin panel: Access `/portfolio/chatbot/admin/`

## Common Scenarios

### Scenario 1: cPanel Subdirectory
```
public_html/
  └── portfolio/
      └── chatbot/
          ├── app/
          ├── public/
          ├── .htaccess
          └── ...
```

**Access**: `yourdomain.com/portfolio/chatbot/`
**Auto-detected path**: `/portfolio/chatbot`

### Scenario 2: Custom DocumentRoot
```
/var/www/
  └── chatbot/
      ├── app/
      ├── public/ ← DocumentRoot points here
      └── ...
```

**Access**: `yourdomain.com/`
**Auto-detected path**: `` (empty, root deployment)

### Scenario 3: Subdomain with Path
```
DocumentRoot: /var/www/apps/
  └── chatbot/
      └── public/
```

**Access**: `apps.yourdomain.com/chatbot/`
**Auto-detected path**: `/chatbot`

## Support

If you're still having issues:

1. **Check error logs**:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

2. **Enable debug mode** in `config/config.php`:
   ```php
   'app' => [
       'debug' => true,  // ← Enable this temporarily
       'environment' => 'development',
   ]
   ```

3. **Test base path detection**:
   Create `test.php` in your subdirectory:
   ```php
   <?php
   echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
   echo "Detected base: " . dirname($_SERVER['SCRIPT_NAME']) . "<br>";
   ```

4. **Clear everything**:
   ```bash
   # Clear browser cache
   # Delete config/config.php
   # Re-run installer
   ```

---

**Note**: The subdirectory support is automatic. In most cases, you just need to upload files and run the installer. The application will handle the rest!
