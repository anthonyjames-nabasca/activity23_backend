<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<h1 align="center">Account22API</h1>

<p align="center">
  Laravel 12 REST API for Activity 22
</p>

---

## About the Project

**Account22API** is a Laravel-based backend API developed for **Activity 22**.  
It provides user account management features such as:

- User registration
- User login using **username or email**
- Email verification
- Forgot password
- Reset password
- User profile retrieval
- User profile update
- User account deletion
- Protected routes using Laravel authentication/token handling

This project is designed to serve as the backend API for a frontend application such as React, Vite, or any other client-side framework.

---

## Features

- Register new users
- Login using **username or email**
- Email verification through Gmail SMTP
- Forgot password email sending
- Reset password using token
- Profile viewing and updating
- Account deletion
- MySQL database integration
- API-ready JSON responses

---

## Requirements

Before running this project, make sure you have installed:

- PHP 8.2 or higher
- Composer
- MySQL / MariaDB
- Laravel CLI
- Node.js and npm (optional, only if frontend assets are needed)
- XAMPP / Laragon / any local server environment

---

## Installation

Clone the repository:

```bash
git clone <your-repository-link>
cd Account22API


````

Install PHP dependencies:

```bash
composer install
```

Copy the environment file:

```bash
copy .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Start the Laravel server:

```bash
php artisan serve
```

The API will usually run at:

```bash
http://localhost:8000
```

---

## Environment Setup

Update your `.env` file with your local configuration.

### Example `.env`

```env
APP_NAME="Account22API"
APP_ENV=local
APP_KEY=base64:+KUCiIWWLqalYWRB2H2UUq6JTwI+y65SpwDy+qZT3nw=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=account22_db
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

FRONTEND_URL=http://localhost:5173

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD="your_gmail_app_password"
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=your_gmail@gmail.com
MAIL_FROM_NAME="Account22API"
```

---

## `.env` Setup Instructions

### 1. Database Configuration

Make sure MySQL is running, then create a database named:

```sql
account22_db
```

Set these values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=account22_db
DB_USERNAME=root
DB_PASSWORD=
```

If your MySQL has a password, place it in `DB_PASSWORD`.

Example:

```env
DB_PASSWORD=1234
```

---

### 2. Frontend URL

This is the URL of your frontend project.

```env
FRONTEND_URL=http://localhost:5173
```

If your frontend runs on another port, change it.

Example:

```env
FRONTEND_URL=http://localhost:3000
```

---

### 3. Gmail SMTP Setup

This project uses **Gmail SMTP** for email verification and password reset.

Set these in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD="your_gmail_app_password"
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=your_gmail@gmail.com
MAIL_FROM_NAME="Account22API"
```

#### Important:

Do **not** use your normal Gmail password.

You must generate a **Google App Password**:

1. Log in to your Google account
2. Enable **2-Step Verification**
3. Go to **Google Account > Security > App Passwords**
4. Generate an app password
5. Use that generated password in:

```env
MAIL_PASSWORD="your_gmail_app_password"
```

---

### 4. Application Key

If the app key is missing, run:

```bash
php artisan key:generate
```

This will automatically update:

```env
APP_KEY=...
```

---

## Run the Project

After setting up `.env`, run these commands:

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate
php artisan serve
```

---

## API Base URL

Default local API base URL:

```bash
http://localhost:8000/api
```

---

## Common Commands

Run migrations:

```bash
php artisan migrate
```

Rollback migrations:

```bash
php artisan migrate:rollback
```

Clear config cache:

```bash
php artisan config:clear
```

Clear application cache:

```bash
php artisan cache:clear
```

Run development server:

```bash
php artisan serve
```

---

## Notes

* Make sure your `.env` file is **not uploaded publicly**.
* Never expose your Gmail app password in GitHub.
* If email sending fails, verify your Gmail SMTP credentials.
* If database connection fails, check your MySQL username, password, and database name.
* If CORS issues appear, make sure the frontend URL matches `FRONTEND_URL`.

---

## Suggested `.gitignore`

Make sure these are ignored:

```gitignore
/vendor
/node_modules
/public/storage
/.env
```

---

## Developer

Developed for **Activity 22** using **Laravel 12** and **MySQL**.

---

```


```
