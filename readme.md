# R&D Logbook Management System

## 📋 Overview

A comprehensive web-based logbook management system designed for Research & Development laboratories. This system helps track instrument usage, experimental activities, and maintain detailed records with advanced security features.

## ✨ Features

### 🔬 **Laboratory Management**
- **Multi-instrument Support**: Manage various laboratory instruments with customizable parameter matrices
- **Dynamic Form Fields**: Instrument-specific parameter fields that adapt based on equipment type
- **Activity Tracking**: Detailed logging of research activities with start/end times
- **Sample Management**: Track samples, trial codes, and experimental conditions

### 👥 **User Management** 
- **Role-based Access Control**: Admin, User, and Viewer roles with appropriate permissions
- **Secure Authentication**: Password hashing, CSRF protection, and session management
- **Account Security**: Login attempt limiting with temporary lockouts
- **User Activity Tracking**: Monitor user actions and login history

### 📊 **Reporting & Analytics**
- **Advanced Filtering**: Search by date range, instrument, user, and custom parameters  
- **Data Export**: Export to Excel, CSV, PDF formats
- **Usage Statistics**: Instrument utilization reports and user productivity metrics
- **Maintenance Alerts**: Automated alerts for equipment needing attention

### 🔒 **Security Features**
- **CSRF Protection**: Cross-site request forgery prevention
- **SQL Injection Prevention**: Prepared statements throughout
- **Input Sanitization**: All user inputs properly escaped and validated
- **Audit Logging**: Comprehensive activity logging for compliance
- **File Upload Security**: Secure file handling with type validation

## 🚀 Installation

### **Prerequisites**
- PHP 8.0 or higher
- MySQL 5.7 or higher / MariaDB 10.3+
- Web server (Apache/Nginx)
- Composer (optional, for dependency management)

### **Step 1: Download & Extract**
```bash
git clone https://github.com/your-repo/logbook-system.git
cd logbook-system
```

### **Step 2: Database Setup**
1. Create a MySQL database:
```sql
CREATE DATABASE logbook_secure_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u your_username -p logbook_secure_db < database/schema.sql
```

### **Step 3: Configuration**
1. Copy the environment configuration:
```bash
cp config/env.example.php config/env.php
```

2. Edit `config/env.php` with your database credentials and settings:
```php
'database' => [
    'host' => 'localhost',
    'name' => 'logbook_secure_db',
    'username' => 'your_db_username',
    'password' => 'your_db_password',
],
```

### **Step 4: Set Permissions**
```bash
# Create required directories
mkdir -p logs uploads backups
chmod 755 logs uploads backups

# Set web server ownership (adjust for your system)
chown -R www-data:www-data .
```

### **Step 5: Web Server Configuration**

#### **Apache (.htaccess)**
```apache
RewriteEngine On
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
```

#### **Nginx**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/logbook-system/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
}
```

## 🎯 Usage

### **Default Login**
- **Username**: `admin`
- **Password**: `password`

*⚠️ Change the default password immediately after first login!*

### **Adding Instruments**
1. Go to **Settings** → **Instrument Matrix**  
2. Configure which parameters are active for each instrument
3. These settings will dynamically show/hide form fields

### **Creating Logbook Entries**
1. Navigate to **Entry** page
2. Select instrument (form fields will adapt automatically)  
3. Fill in required information:
   - Start date/time
   - Activity description
   - Instrument-specific parameters
4. Optional: Add finish time, condition after use, remarks

### **Managing Users** (Admin Only)
1. Go to **User Management**
2. Add new users with appropriate roles
3. Manage user status (active/inactive)
4. Reset passwords when needed

## 📂 Project Structure

```
logbook-system/
├── config/
│   ├── init.php              # Main configuration & initialization
│   ├── config.php            # Configuration loader class
│   ├── env.php               # Environment-specific settings
│   └── env.example.php       # Example environment file
├── database/
│   └── schema.sql            # Database schema and sample data
├── public/                   # Web root directory
│   ├── index.php            # Dashboard
│   ├── login.php            # Authentication
│   ├── entry.php            # Add new logbook entries
│   ├── logbook_list.php     # View all entries
│   ├── settings.php         # Instrument matrix management
│   ├── user_management.php  # User administration
│   └── css/
├── templates/
│   ├── header.php           # Common header template
│   └── footer.php           # Common footer template
├── logs/                    # Application logs
├── uploads/                 # File uploads storage
├── backups/                 # Database backup location
└── README.md               # This file
```

## 🔧 Configuration Options

### **Security Settings**
```php
'security' => [
    'max_login_attempts' => 5,        # Max failed login attempts
    'lockout_time' => '15 minutes',   # Account lockout duration
    'session_lifetime' => 1800,       # Session timeout (seconds)
    'csrf_token_expire' => 3600,      # CSRF token lifetime
]
```

### **File Upload Settings**
```php
'upload' => [
    'max_file_size' => 10485760,                    # 10MB limit
    'allowed_types' => ['pdf', 'doc', 'jpg'],       # Allowed extensions
    'upload_path' => '/path/to/uploads/',           # Upload directory
]
```

### **Logging Configuration**
```php
'logging' => [
    'app_log' => '/path/to/logs/app.log',
    'audit_log' => '/path/to/logs/audit.log', 
    'max_log_size' => 52428800,                     # 50MB before rotation
    'log_retention_days' => 90,                     # Keep logs for 90 days
]
```

## 🛡️ Security Considerations

### **Production Deployment**
1. **Change Default Credentials**: Update admin password immediately
2. **Use HTTPS**: Enable SSL/TLS certificates
3. **Database Security**: Use restricted database user with minimal privileges
4. **File Permissions**: Ensure proper file/directory permissions
5. **Regular Updates**: Keep PHP and dependencies updated

### **Security Features Included**
- ✅ Password hashing with PHP's `password_hash()`
- ✅ CSRF token validation on all forms
- ✅ SQL injection prevention via prepared statements
- ✅ XSS protection through output escaping
- ✅ Session security (httpOnly, secure flags)
- ✅ Login attempt limiting
- ✅ Comprehensive audit logging

## 📊 Database Schema

### **Main Tables**
- `users` - User accounts and authentication
- `instruments` - Laboratory instruments and parameter matrix
- `logbook_entries` - Main logbook records  
- `attachments` - File uploads linked to entries
- `audit_logs` - Activity tracking for compliance
- `system_settings` - Application configuration

### **Key Relationships**
- Users → Logbook Entries (1:Many)
- Instruments → Logbook Entries (1:Many)
- Logbook Entries → Attachments (1:Many)

## 🔄 Maintenance

### **Database Backups**
```bash
# Manual backup
mysqldump -u username -p logbook_secure_db > backup_$(date +%Y%m%d).sql

# Automated backup (add to crontab)
0 2 * * * /usr/bin/mysqldump -u username -p logbook_secure_db > /path/to/backups/backup_$(date +\%Y\%m\%d).sql
```

### **Log Rotation**
Logs automatically rotate when they exceed configured size limits. Old logs are compressed and retained according to the retention policy.

### **System Updates**
1. Backup database and files
2. Test updates in staging environment
3. Apply updates during maintenance window
4. Verify functionality post-update

## 🐛 Troubleshooting

### **Common Issues**

#### **Database Connection Error**
```
Error: Database connection failed
```
**Solution**: Check database credentials in `config/env.php` and ensure MySQL service is running.

#### **Permission Denied Errors**  
```
Error: Permission denied writing to logs/
```
**Solution**: Set proper directory permissions:
```bash
chmod 755 logs uploads
chown -R www-data:www-data logs uploads
```

#### **Session/CSRF Issues**
```
Error: CSRF Token Error
```
**Solution**: Clear browser cookies and ensure session directory is writable.

### **Debug Mode**
Enable debug mode in `config/env.php` for detailed error messages:
```php
'app' => [
    'debug' => true,  // Enable for development only
]
```

## 📞 Support

### **Getting Help**
- 📧 **Email**: support@your-domain.com
- 📖 **Documentation**: [Wiki/Docs URL]
- 🐛 **Bug Reports**: [GitHub Issues URL]

### **Contributing**
1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🏷️ Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Added user management and advanced reporting  
- **v1.2.0** - Enhanced security features and audit logging

---

**Built with ❤️ for the scientific community**