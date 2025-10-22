# Event Management System

A comprehensive web-based event management system built with Symfony 6.1 and MariaDB, featuring attendee registration, admin management, and secure file downloads.

## 🚀 Features

### Core Functionality
- **Multiple Events**: Create and manage multiple events with unique URLs
- **Event Landing Pages**: Rich event detail pages with descriptions, schedules, and materials
- **Attendee Registration**: Simple registration process with email verification
- **File Downloads**: Secure access to event materials (PDFs, presentations, etc.)
- **Admin Dashboard**: Comprehensive administrative interface for event management
- **Badge Printing**: Customizable badge generation for attendees

### Security Features
- **Email-based Authentication**: Passwordless login via secure email links
- **Role-based Access Control**: Separate permissions for attendees, admins, and super admins
- **Email Verification**: Required email verification for accessing materials
- **CSRF Protection**: Built-in cross-site request forgery protection
- **Data Encryption**: Secure handling of sensitive information

### User Experience
- **Responsive Design**: Mobile-first design using Bootstrap 5
- **Accessibility**: WCAG 2.1 compliant interface
- **Real-time Statistics**: Live attendee counts and event metrics
- **Search & Filter**: Advanced event discovery tools

## 🛠 Technical Specifications

### System Requirements
- **PHP**: 7.4+ (tested with PHP 8.4)
- **Web Server**: Apache HTTP Server (with mod_rewrite)
- **Database**: MariaDB 10.x+ (tested with 11.8.3)
- **PHP Extensions**: MySQLi, PDO, mbstring, XML, ZIP, cURL, GD, Intl

### Framework & Libraries
- **Symfony**: 6.1.x (web framework)
- **Doctrine ORM**: Database abstraction and migrations
- **Twig**: Template engine
- **Bootstrap 5**: Frontend CSS framework
- **VichUploaderBundle**: File upload management
- **KnpPaginatorBundle**: Pagination support
- **DomPDF**: PDF generation
- **FOSCKEditorBundle**: WYSIWYG editor integration

## 🗂 Database Schema

### Events Table
- Event ID (Primary Key)
- Title, Description, Slug
- Start/End Dates, Location
- Maximum Attendees, Banner Image
- Active Status, Created/Updated timestamps

### Attendees Table
- Attendee ID (Primary Key)
- Name, Email, Phone, Organization, Job Title
- Email verification status and token
- Check-in status and timestamps
- Event relationship (Foreign Key)

### Administrators Table
- Administrator ID (Primary Key)
- Name, Email, Department
- Password hash, Active status
- Super admin privileges
- Last login tracking

### Event Files Table
- File ID (Primary Key)
- File name, description, original name
- MIME type, file size
- Upload timestamp, download count
- Event relationship (Foreign Key)

## 📁 Project Structure

```
event-management-system/
├── config/                 # Configuration files
│   ├── packages/           # Bundle configurations
│   └── routes.yaml         # Routing configuration
├── migrations/             # Database migrations
├── public/                 # Web-accessible files
│   └── index.php          # Application entry point
├── src/                   # Application source code
│   ├── Command/           # Console commands
│   ├── Controller/        # Web controllers
│   ├── Entity/            # Doctrine entities
│   └── Repository/        # Database repositories
├── templates/             # Twig templates
│   ├── admin/             # Admin interface templates
│   ├── attendee/          # Attendee dashboard
│   ├── event/             # Event pages
│   └── security/          # Authentication pages
└── tests/                 # Test suites
```

## 🚀 Installation & Setup

### 1. Clone and Install Dependencies

```bash
# Clone the repository
git clone <repository-url> event-management-system
cd event-management-system

# Install PHP dependencies
composer install
```

### 2. Database Configuration

```bash
# Create database and user
sudo mysql
CREATE DATABASE event_management;
CREATE USER 'event_user'@'localhost' IDENTIFIED BY 'event_password';
GRANT ALL PRIVILEGES ON event_management.* TO 'event_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Environment Setup

Update `.env` file with your database credentials:
```env
DATABASE_URL="mysql://event_user:event_password@127.0.0.1:3306/event_management?serverVersion=mariadb-11.8.3&charset=utf8mb4"
```

### 4. Database Migration

```bash
# Run database migrations
bin/console doctrine:migrations:migrate
```

### 5. Create Admin User

```bash
# Create super administrator
bin/console app:create-admin admin@example.com --name="System Administrator" --password="secure_password" --super-admin
```

### 6. Load Sample Data (Optional)

```bash
# Create sample events for testing
bin/console app:create-sample-data
```

### 7. Start Development Server

```bash
# Start PHP built-in server
php -S 127.0.0.1:8000 -t public/
```

Visit `http://127.0.0.1:8000` to access the application.

## 👤 User Roles & Access

### Public Users
- Browse public event listings
- View event details and schedules
- Register for events
- Request login links via email

### Attendees
- Access personalized dashboard
- Download event materials (after email verification)
- View registration status and check-in status
- Update personal information

### Administrators
- Manage assigned events
- View attendee lists and statistics
- Upload and manage event files
- Generate attendee badges
- Access admin dashboard

### Super Administrators
- Full system access
- Manage all events across the platform
- Create and manage other administrators
- System-wide configuration and settings

## 🔐 Security Features

### Authentication
- **Email-based Login**: Secure, passwordless authentication using time-limited email links
- **Session Management**: Secure session handling with automatic timeouts
- **Remember Me**: Optional persistent login sessions

### Authorization
- **Role-based Access**: Granular permissions based on user roles
- **Event-specific Access**: Attendees can only access their registered events
- **Admin Boundaries**: Regular admins limited to assigned events

### Data Protection
- **Email Verification**: Required for accessing sensitive materials
- **CSRF Protection**: All forms protected against cross-site request forgery
- **Input Validation**: Comprehensive input sanitization and validation
- **Password Security**: Industry-standard password hashing (when used)

## 📊 Performance Features

### Caching Strategy
- **Application Cache**: Symfony's built-in caching for configuration and metadata
- **Template Cache**: Compiled Twig templates for faster rendering
- **Query Optimization**: Efficient database queries with proper indexing

### Scalability
- **Horizontal Scaling**: Stateless architecture supports load balancing
- **Database Optimization**: Proper indexing and query optimization
- **File Storage**: Extensible file storage system (local/cloud)

## 🧪 Testing

### Test Categories
- **Unit Tests**: Individual component testing using PHPUnit
- **Integration Tests**: Database and service integration testing
- **Functional Tests**: End-to-end user workflow testing

### Running Tests
```bash
# Run all tests
bin/phpunit

# Run specific test suite
bin/phpunit tests/Controller/
```

## 📱 Mobile Compatibility

- **Responsive Design**: Mobile-first approach using Bootstrap 5
- **Touch-friendly**: Optimized for touch interactions
- **Performance**: Lightweight and fast-loading on mobile devices
- **Accessibility**: Screen reader compatible and keyboard navigable

## 🔧 Configuration

### Email Configuration
Configure your email provider in `.env`:
```env
MAILER_DSN=smtp://username:password@smtp.example.com:587
```

### File Upload Configuration
Adjust file upload limits in `config/packages/vich_uploader.yaml`

### Security Configuration
Review security settings in `config/packages/security.yaml`

## 📈 Monitoring & Analytics

### Built-in Statistics
- Event attendance tracking
- File download analytics
- Registration conversion rates
- Admin activity monitoring

### Performance Monitoring
- Symfony Profiler integration
- Database query optimization
- Request/response time tracking

## 🚀 Production Deployment

### Apache Configuration
```apache
<VirtualHost *:80>
    DocumentRoot /var/www/event-management-system/public
    DirectoryIndex index.php
    
    <Directory "/var/www/event-management-system/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Environment Setup
```bash
# Set production environment
APP_ENV=prod

# Clear and warm cache
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod
```

## 🆘 Support & Troubleshooting

### Common Issues
1. **Database Connection**: Verify credentials and MariaDB service status
2. **File Permissions**: Ensure web server can write to `var/` directory
3. **Email Delivery**: Configure proper SMTP settings for email verification

### Debug Mode
Enable debug mode by setting `APP_ENV=dev` in `.env` file.

### Logs
Application logs are stored in `var/log/` directory.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Symfony Framework Team
- Bootstrap Contributors
- All open-source contributors who made this project possible

---

**Version**: 1.0.0  
**Last Updated**: October 2025  
**Author**: Event Management System Team