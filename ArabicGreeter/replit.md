# Military Devices Management System

## Overview

This is a Flask-based military devices management system written in Arabic. The application manages military equipment inventory with user authentication, role-based permissions, device tracking, and comprehensive activity logging.

## User Preferences

Preferred communication style: Simple, everyday language.

## Recent Changes (2024)

### Major System Conversion
- **Date**: January 2024
- **Change**: Complete conversion from Flask/Python to PHP/HTML
- **Reason**: Enable deployment on free hosting platforms like GitHub, InfinityFree
- **Impact**: System now compatible with shared hosting and easier to deploy

### Designer Credit Integration
- **Feature**: Added "تصميم وبرمجة أبو ربيع الحميدي" with contact information
- **Location**: Login page with elegant Amiri font styling
- **Design**: Gradient background card with hover effects
- **Contact Info**: Phone number and email prominently displayed

## System Architecture

### Backend Architecture
- **Framework**: PHP 7.4+ with PDO for database interactions
- **Database**: MySQL/MariaDB with UTF-8 support
- **Authentication**: Custom session-based authentication with SHA-256 password hashing + salt
- **Session Management**: PHP native sessions with timeout management
- **Structure**: Object-oriented PHP with separate classes for Database, Auth, and business logic

### Frontend Architecture
- **Template System**: Native PHP with HTML templates
- **CSS Framework**: Bootstrap 5 RTL for Arabic language support
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Google Fonts Amiri for elegant Arabic typography
- **JavaScript**: Vanilla JavaScript with Bootstrap components
- **Language**: Full Arabic interface with RTL (Right-to-Left) support

### Security Architecture
- Password hashing using SHA-256
- Session token generation with secrets.token_urlsafe()
- IP address and browser tracking for sessions
- Permission-based access control system
- CSRF protection through Flask's built-in mechanisms

## Key Components

### Database Schema
- **المستخدمين** (Users): User accounts with military unit assignments
- **الصلاحيات** (Permissions): Role-based permissions system
- **تخصيص_الصلاحيات** (Permission Assignments): User-permission relationships
- **جلسات_المستخدمين** (User Sessions): Session management table
- **الأجهزة** (Devices): Military device inventory
- **سجل_الأنشطة** (Activity Log): Comprehensive audit trail

### Authentication System
- Custom authentication in `auth.py`
- Session-based login with configurable expiry
- Password verification with SHA-256 hashing
- User session tracking with IP and browser information
- Role-based permission checking decorators

### Device Management
- Complete CRUD operations for military devices
- Device status tracking (جيد/معطل/صيانة/مفقود)
- Search and filtering capabilities
- Device assignment and tracking

### Permission System
- Granular permission-based access control
- Administrative interface for permission management
- Permission inheritance and assignment tracking

### Activity Logging
- Comprehensive audit trail for all system activities
- User action tracking with timestamps
- IP address and session information logging

## Data Flow

1. **User Authentication**: Login → Session Creation → Permission Loading
2. **Device Operations**: Permission Check → Database Operation → Activity Logging
3. **User Management**: Admin Permission Check → User CRUD → Activity Logging
4. **Reports**: Data Aggregation → Template Rendering → Chart Generation

## External Dependencies

### PHP Requirements
- PHP 7.4 or higher
- PDO MySQL extension
- mbstring extension
- fileinfo extension (for file uploads)
- zip extension (for compressed exports)

### Database
- MySQL 5.7+ or MariaDB 10.2+
- UTF-8mb4 character set support
- InnoDB storage engine

### Frontend Dependencies
- Bootstrap 5 RTL: CSS framework for Arabic interfaces
- Font Awesome 6.4.0: Icon library
- Google Fonts Amiri: Elegant Arabic typography
- Chart.js: Data visualization for reports (future implementation)

### Hosting Requirements
- PHP hosting with MySQL support
- .htaccess support (Apache)
- SSL certificate recommended for production

## Deployment Strategy

### Current Setup
- PHP-based web application ready for shared hosting
- MySQL database with proper UTF-8 support
- Session-based authentication with security measures
- Bootstrap RTL interface with Arabic typography
- Comprehensive installation wizard

### Production Deployment
- Compatible with shared hosting (InfinityFree, 000webhost, etc.)
- GitHub repository ready for version control
- .htaccess security configurations
- Optimized for performance with caching headers
- SSL/HTTPS ready configuration

### File Structure
```
/
├── index.php           # Main entry point (redirects to login/dashboard)
├── config.php          # System configuration and database settings
├── database.php        # Database class with PDO operations
├── auth.php            # Authentication and authorization system
├── login.php           # Login page with designer credit
├── logout.php          # Logout handler
├── dashboard.php       # Main dashboard with statistics
├── devices.php         # Device management interface
├── navbar.php          # Navigation bar component
├── access_denied.php   # Access denied page
├── install.php         # Installation wizard
├── database.sql        # Database schema and initial data
├── .htaccess           # Apache security and optimization
└── README.md           # Complete documentation
```

The system is designed for military environments with Arabic language support, comprehensive audit trails, and robust permission management suitable for sensitive equipment tracking.