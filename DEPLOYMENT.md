# Deployment Guide

## Quick Start Deployment

### Prerequisites
- PHP 8.1+ installed
- MySQL 8.0+ installed
- Apache/Nginx web server
- OpenAI API key

### Step 1: Upload Files
Upload all files to your web server. Ensure the document root points to the `public` folder.

### Step 2: Set Permissions
```bash
chmod 755 -R /path/to/chatbot
chmod 777 /path/to/chatbot/config
```

### Step 3: Run Installer
Navigate to: `http://your-domain.com/install.php`

Fill in the installation form:
- **Database Host**: localhost (or your MySQL host)
- **Database Port**: 3306 (default)
- **Database Name**: chatbot_db (or your preferred name)
- **Database Username**: Your MySQL username
- **Database Password**: Your MySQL password
- **Admin Email**: Your email address
- **Admin Password**: Strong password (min 8 chars, uppercase, lowercase, number)
- **OpenAI API Key**: Your OpenAI API key (starts with sk-)

### Step 4: Complete Installation
Click "Install AI Chat" and wait for completion.

### Step 5: Login
After installation, you'll see a success message. Click "Go to Login Page" and login with your admin credentials.

## Apache Configuration

### .htaccess Method (Already Included)
The `.htaccess` file is already configured. Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Virtual Host Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/chatbot/public
    
    <Directory /path/to/chatbot/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/chatbot-error.log
    CustomLog ${APACHE_LOG_DIR}/chatbot-access.log combined
</VirtualHost>
```

## Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/chatbot/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ \.(htaccess|htpasswd|ini|log|sh|sql|conf)$ {
        deny all;
    }
}
```

## SSL/HTTPS Setup

### Using Let's Encrypt (Recommended)
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

### For Nginx:
```bash
sudo certbot --nginx -d your-domain.com
```

## Environment-Specific Configurations

### Production Settings
In `config/config.php`:
```php
'app' => [
    'environment' => 'production',
    'debug' => false,
],
```

### Development Settings
```php
'app' => [
    'environment' => 'development',
    'debug' => true,
],
```

## Performance Optimization

### PHP Configuration
Edit `php.ini`:
```ini
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 20M
upload_max_filesize = 20M
```

### Enable OPcache
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### MySQL Optimization
```sql
SET GLOBAL max_connections = 200;
SET GLOBAL innodb_buffer_pool_size = 256M;
```

## Backup Strategy

### Database Backup
```bash
# Daily backup script
#!/bin/bash
DATE=$(date +%Y%m%d)
mysqldump -u username -p'password' chatbot_db > backup_$DATE.sql
```

### File Backup
```bash
# Backup configuration and uploads
tar -czf backup_$DATE.tar.gz config/ public/uploads/
```

## Monitoring

### Enable Error Logging
In `php.ini`:
```ini
log_errors = On
error_log = /var/log/php_errors.log
```

### Check Logs
```bash
tail -f /var/log/apache2/chatbot-error.log
tail -f /var/log/php_errors.log
```

## Troubleshooting

### Installation Issues

**Problem**: "Database connection failed"
**Solution**: Check MySQL credentials, ensure MySQL is running

**Problem**: "Permission denied" when creating config file
**Solution**: Set proper permissions on config directory

**Problem**: "Cannot write to config directory"
**Solution**: `chmod 777 /path/to/chatbot/config`

### Runtime Issues

**Problem**: "500 Internal Server Error"
**Solution**: 
- Check Apache/Nginx error logs
- Verify PHP version (must be 8.1+)
- Check file permissions

**Problem**: "CSRF token validation failed"
**Solution**: 
- Clear browser cache and cookies
- Ensure sessions are working properly
- Check session.save_path in php.ini

**Problem**: "OpenAI API error"
**Solution**:
- Verify API key is correct
- Check API quota limits
- Review API logs in admin panel

### Performance Issues

**Problem**: Slow page loads
**Solution**:
- Enable OPcache
- Optimize MySQL queries
- Use connection pooling
- Consider CDN for assets

**Problem**: High memory usage
**Solution**:
- Increase PHP memory_limit
- Optimize database queries
- Review conversation history limits

## Security Checklist

- [ ] Change default admin password
- [ ] Enable HTTPS/SSL
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Disable directory listing
- [ ] Hide PHP version in headers
- [ ] Configure firewall rules
- [ ] Regular security updates
- [ ] Monitor API usage
- [ ] Review user access logs
- [ ] Backup encryption keys

## Scaling Considerations

### Vertical Scaling
- Increase server resources (CPU, RAM)
- Optimize database indices
- Enable query caching

### Horizontal Scaling
- Use load balancer
- Separate database server
- Redis for session storage
- CDN for static assets

### Database Optimization
```sql
-- Add indices for better performance
CREATE INDEX idx_messages_conversation ON messages(conversation_id, created_at);
CREATE INDEX idx_api_logs_user ON api_logs(user_id, created_at);
```

## Updates and Maintenance

### Regular Maintenance Tasks
1. Database optimization (weekly)
   ```sql
   OPTIMIZE TABLE users, conversations, messages, api_logs;
   ```

2. Clean old sessions (daily)
   ```sql
   DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY);
   ```

3. Archive old API logs (monthly)
   ```sql
   DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
   ```

4. Update dependencies and security patches

### Applying Updates
1. Backup database and files
2. Put site in maintenance mode
3. Update files
4. Run any new migrations
5. Test thoroughly
6. Remove maintenance mode

## Support and Resources

- Documentation: README.md
- GitHub Issues: For bug reports
- Error Logs: Check `/var/log/` for errors
- Admin Panel: Monitor usage and costs

---

**Need Help?**
Check the logs first, then create an issue on GitHub with:
- Error message
- PHP version
- MySQL version
- Steps to reproduce
