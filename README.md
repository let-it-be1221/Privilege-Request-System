# Privilege Access Request System (PHP + MySQL)

Simple RBAC-based Privilege Access Request System prototype.

Requirements:
- PHP 8+, MySQL, Composer

Setup:
1. Create MySQL database and import `sql/schema.sql`.
2. Edit `config.php` to match your DB and SMTP settings.
3. Run `composer install` to install dependencies.
4. Serve the `public/` directory (e.g. with PHP built-in server):

```bash
composer install
php -S localhost:8000 -t public
```

Notes:
- Authentication supports a DB-backed test mode. Toggle LDAP in `config.php`.
- Use prepared statements, CSRF tokens, and basic input validation.
# Privilege-Request-System
