# Restaurant Management System – Backend

A RESTful API-based Restaurant Management System developed using **Laravel, PHP, MySQL, and Laravel Sanctum**. This backend provides secure authentication, role-based access control, user management, menu management, email services, and social login integration.

## Technologies Used

- PHP
- Laravel
- MySQL
- Laravel Sanctum
- Spatie Laravel Permission
- Laravel Socialite
- RESTful APIs
- Postman

## Features

### 1. Authentication
- User Registration and Login
- Laravel Sanctum Token Authentication
- Logout
- Email Verification
- Forgot Password
- Reset Password
- Google Login
- Facebook Login

### 2. Role-Based Access Control

The system supports two roles:

**Admin**
- Access to the admin dashboard.
- Manage users.
- Create, view, update, and delete users.
- View deleted users.
- Restore deleted users.
- Manage menus.

**User**
- Access to the user dashboard.
- View available menus.
- No access to user management or admin-only operations.

### 3. User Management
- Create users.
- View all users.
- View individual user details.
- Update user information.
- Soft delete users.
- View trashed users.
- Restore deleted users.

### 4. Menu Management
- Create menus.
- View all menus.
- View individual menu details.
- Update menu information.
- Soft delete menus.
- View trashed menus.
- Restore deleted menus.

### 5. Email Services
- Welcome emails after registration.
- Welcome emails for admin-created users.
- Email verification notifications.
- Password reset emails.

### 6. Security
- Sanctum API authentication.
- Role-based API authorization.
- Protected routes.
- Request validation.
- Password hashing.
- Soft deletes.
- OAuth authentication.

## Project Installation

### 1. Clone the Repository

```bash
git clone https://github.com/emashahzad/restaurant-management-backend.git
```

### 2. Navigate to Project Directory

```bash
cd restaurant-management-backend
```

### 3. Install Dependencies

```bash
composer install
```

### 4. Configure Environment

Create your environment file:

```bash
cp .env.example .env
```

For Windows, you can also create a copy of `.env.example` and rename it to `.env`.

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Configure Database

Update the following settings in your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurant_db
DB_USERNAME=root
DB_PASSWORD=
```

Create the database named `restaurant_db` in phpMyAdmin or MySQL.

### 7. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This creates the required database tables and seeds the application roles.

### 8. Configure Email Services

Configure your SMTP credentials in `.env` to enable welcome emails, email verification, and password reset emails.

### 9. Configure Google and Facebook Login

Add your OAuth credentials to `.env` and configure the callback URLs in the respective developer consoles.

Never commit actual credentials or secrets to GitHub.

### 10. Start Laravel Development Server

```bash
php artisan serve
```

Backend API will be available at:

`http://127.0.0.1:8000`

## API Authentication

The project uses Laravel Sanctum for API authentication.

- Register or log in to receive an authentication token.
- Include the token in the Authorization header for protected API requests.

Example:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

## API Modules

| Module | Description |
|---|---|
| Authentication | Register, login, logout |
| Email Verification | Verify and resend verification |
| Password Management | Forgot and reset password |
| Social Login | Google and Facebook |
| Users | User CRUD and role management |
| Menus | Menu CRUD |
| Trash Management | View and restore deleted records |

## HTTP Status Codes

| Status | Meaning |
|---|---|
| 200 | Successful request |
| 201 | Resource created |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Resource not found |
| 422 | Validation error |

## API Testing

All API endpoints can be tested using **Postman**.

Make sure the Laravel server is running before sending requests.

## Frontend Repository

The React frontend is maintained in a separate repository:

[Restaurant Management Frontend](https://github.com/emashahzad/restaurant-management-frontend)

## Developer

**Ema Shahzad**

BS Computer Science | Full Stack Web Developer

Technologies: Laravel, PHP, MySQL, React.js, JavaScript, Bootstrap, and REST APIs.

---

*This project was developed as a practical full-stack development project, implementing backend API development, authentication, authorization, database management, and frontend integration.*