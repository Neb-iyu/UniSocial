# Unifyze Backend

This is the backend for the Unifyze project, built with PHP.

## Quick Start

1. **Install dependencies:**  
   Run `composer install` in the `backend` directory.

2. **Set up environment variables:**  
   Copy `.env.example` to `.env` and update values as needed.

3. **Set file permissions:**  
   Make sure `public/uploads/` is writable by the web server.

4. **Set up the database:**  
   - Create a MySQL database (default: `unifyze`).
   - Import the schema from `src/Utilities/schema.sql`:
     - **Command line:**  
       `mysql -u root -p unifyze < backend/src/Utilities/schema.sql`
     - **phpMyAdmin:**  
       Use the Import tab and select `backend/src/Utilities/schema.sql`.
5. **Run**
   - Using `XAMPP`: clone/download it in `htdocs` and start the server.

## Structure

- `src/` — Main PHP code (controllers, models, utilities)
- `public/` — Public files and entry point (`index.php`)
- `vendor/` — Composer dependencies



## Notes

- Environment variables are listed in `.env.example`.
- Log files and uploads are not tracked by git.
- For API details - work in progress