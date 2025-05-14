
# Employee Skill Tracking System - Setup Guide

## Database Setup

1. Start your XAMPP server (Apache and MySQL)

2. Open phpMyAdmin by navigating to http://localhost/phpmyadmin in your web browser

3. Create a new database:
   - Click on "New" in the left sidebar
   - Enter "skill_tracking_system" as the database name
   - Click "Create"

4. Import the SQL script:
   - Select the newly created "skill_tracking_system" database
   - Click on the "Import" tab
   - Click "Choose file" and select the `setup/database.sql` file
   - Click "Go" to execute the script

## Application Setup

1. Place the entire application folder in your XAMPP htdocs directory:
   - Default path: `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
   - You can create a subfolder like `C:\xampp\htdocs\skill-tracking\`

2. Access the application by navigating to:
   - http://localhost/skill-tracking/ (if you placed it in a subfolder)
   - or http://localhost/ (if placed directly in htdocs)

## Default Login Credentials

### Admin Login:
- Username: admin
- Password: admin123

### Sample Employee Login:
- Employee ID: EMP001
- Password: password

## Security Notes

This is a demonstration system. In a production environment, you should:

1. Use HTTPS for all connections
2. Implement stronger password security policies
3. Add proper input validation
4. Implement access control checks on all pages
5. Use prepared statements for all database queries (already implemented)
6. Consider implementing CSRF protection
