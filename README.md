Back-Office & Agency Management System (PHP + MySQL)

Overview

This is a starter scaffold for a travel agency back-office system built with PHP (OOP, MVC), MySQL, HTML/CSS/JS.

Quick start (XAMPP, Windows):

1. Copy this folder to your web root (e.g., C:\\xampp\\htdocs\\backoffice_system)
2. Create a MySQL database and import the schema in `db/schema.sql`.
3. Update `config/config.php` and `config/db.php` with DB credentials.
4. Start Apache + MySQL via XAMPP and visit http://localhost/backoffice_system/public/

What's included:

- MVC skeleton
- Authentication scaffolding (bcrypt + email OTP stub)
- Dashboard layout with dark/light mode toggle
- Staff module CRUD (skeleton)

Next steps:

- Complete modules: Supplier, Tours, Marketing, Finance, Visa
- Implement PHPMailer-based OTP delivery
- Add tests and improve UX

Developer notes:

- To create an admin user locally run:

  php scripts/create_admin.php admin@example.com strongpassword "Admin Name"

- OTP delivery on this scaffold writes to `var/logs/mail.log` for development (see `helpers/mailer.php`).

Using PHPMailer (SMTP)

1. Install dependencies (Composer must be available):

composer install

2. Configure SMTP in `config/config.php` by editing the 'mail' section (use Gmail SMTP or other provider):

'mail' => [
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_user' => 'your@gmail.com',
'smtp_pass' => 'your-app-password',
'smtp_secure' => 'tls',
'from_email' => 'no-reply@yourdomain.com',
'from_name' => 'Travel Backoffice'
]

3. After configuration, PHPMailer will be used to send OTP emails. If PHPMailer is not installed or SMTP is not configured, the system will fallback to writing to `var/logs/mail.log`.
