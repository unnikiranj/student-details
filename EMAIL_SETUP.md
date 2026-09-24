# Daily Attendance Email System - Setup Instructions

This system sends daily attendance emails to all students with their latest month attendance. Email format: `roll_number@pvpsit.ac.in`

## Prerequisites

1. PHP 7.4 or higher
2. MySQL database with attendance data
3. SMTP server access (Gmail, or your institutional email server)
4. Composer (for installing PHPMailer)

## Installation Steps

### 1. Install PHPMailer

Navigate to your project directory and run:

```bash
cd c:\xampp\htdocs\studentdetails
composer require phpmailer/phpmailer
```

If you don't have Composer installed, download it from [getcomposer.org](https://getcomposer.org/)

### 2. Configure Email Settings

Edit `send_daily_attendance.php` and update the SMTP configuration:

```php
// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');        // Your SMTP server
define('SMTP_PORT', 587);                     // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USER', 'your-email@gmail.com');  // Your email address
define('SMTP_PASS', 'your-app-password');     // Your email password or app password
define('SMTP_FROM', 'your-email@gmail.com');  // From email address
define('SMTP_FROM_NAME', 'PVPSIT Attendance System'); // From name
```

### 3. For Gmail Users

If using Gmail, you need to:
1. Enable 2-Factor Authentication on your Google Account
2. Generate an App Password:
   - Go to Google Account Settings → Security
   - Enable 2-Step Verification
   - Go to App Passwords
   - Create a new app password for "Mail"
   - Use this 16-character password in `SMTP_PASS`

### 4. For Institutional Email Server

If using your institutional email server (@pvpsit.ac.in), update the settings:

```php
define('SMTP_HOST', 'mail.pvpsit.ac.in');      // Your institutional SMTP server
define('SMTP_PORT', 587);                     // Check with IT department
define('SMTP_USER', 'attendance@pvpsit.ac.in'); // Your institutional email
define('SMTP_PASS', 'your-password');          // Your email password
define('SMTP_FROM', 'attendance@pvpsit.ac.in'); // From email address
define('SMTP_FROM_NAME', 'PVPSIT Attendance System');
```

## Testing the Script

Run the script manually to test:

```bash
php send_daily_attendance.php
```

Check the log file for results:
```
logs/daily_attendance_YYYY-MM-DD.log
```

## Setting Up Cron Job (Linux/Unix)

### For Linux Server

Add to crontab:
```bash
crontab -e
```

Add this line to run daily at 4:45 PM:
```bash
45 16 * * * /usr/bin/php /path/to/studentdetails/send_daily_attendance.php
```

### For Windows Server

Use Task Scheduler:
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger to "Daily" at 4:45 PM
4. Action: "Start a program"
5. Program: `C:\xampp\php\php.exe`
6. Arguments: `C:\xampp\htdocs\studentdetails\send_daily_attendance.php`

## Email Template Features

The email includes:
- Student name and roll number
- Latest month attendance data
- Academic year and semester
- Present/Total classes
- Attendance percentage
- Status indicator (Safe/Condonation/Detention)
- Color-coded status (Green/Orange/Red)

## Troubleshooting

### PHPMailer not found
- Ensure Composer is installed
- Run `composer require phpmailer/phpmailer` in the project directory
- Verify `vendor/` folder exists

### Email not sending
- Check SMTP credentials are correct
- Verify SMTP server allows external connections
- Check firewall settings
- Review log file for specific error messages

### Students not receiving emails
- Verify email format: `roll_number@pvpsit.ac.in`
- Check if email addresses are valid
- Verify email server accepts emails to @pvpsit.ac.in domain
- Check spam/junk folders

### Database connection error
- Verify `config.php` has correct database credentials
- Ensure MySQL service is running
- Check database name and table structure

## Log Files

Logs are stored in:
```
logs/daily_attendance_YYYY-MM-DD.log
```

Each log entry includes:
- Timestamp
- Process status
- Success/failure counts
- Individual email send results
- Error messages

## Security Notes

1. **Never commit SMTP credentials to version control**
2. **Use environment variables for sensitive data** (recommended)
3. **Limit script execution permissions**
4. **Monitor log files for abuse**
5. **Consider rate limiting** for large student populations

## Optional: Environment Variables

For better security, use environment variables instead of hardcoding credentials:

```php
// In send_daily_attendance.php
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'your-email@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your-app-password');
```

Set environment variables in your system or `.env` file.

## Customization

### Change Email Template
Edit the HTML in the `$message` variable in `send_daily_attendance.php`

### Change Send Time
Modify the cron job schedule:
```bash
# Run at 6:00 PM instead
0 18 * * * /usr/bin/php /path/to/send_daily_attendance.php
```

### Add Additional Recipients
Add CC/BCC in the `sendEmail()` function:
```php
$mail->addCC('admin@pvpsit.ac.in');
$mail->addBCC('principal@pvpsit.ac.in');
```

## Support

For issues or questions:
1. Check log files first
2. Verify SMTP settings
3. Test PHPMailer installation
4. Review database connectivity
