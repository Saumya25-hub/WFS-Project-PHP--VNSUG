# Online Watches Website - (PHP)

## Project Overview
- **Subject:** BCA Semester 5 - 504 Web Framework & Services (WFS)
- **Technology:** Core PHP, MySQL / MariaDB, HTML5, CSS3, XAMPP

## Week 1 Milestone Deliverables
1. **Database Connection:** Established connection with MySQL database via `config/db.php`.
2. **Two Database Tables:** `users` (customers) and `admins` (administrators).
3. **User Registration:** Form with validation and `password_hash()` encryption.
4. **User Login:** Form with authentication using `password_verify()` and PHP session creation.
5. **Admin Login:** Dedicated portal for system administrators.
6. **Session & Security:** Protected user dashboard and admin view with logout capability.
7. **Clean Navigation & UI:** Header, footer, and responsive design.

## How to Run in XAMPP
1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Open phpMyAdmin (`http://localhost/phpmyadmin/`) and import `database.sql` (or create database `watches_db` and execute the queries).
3. Open the project in browser: `http://localhost/WFS-Project(PHP)/`

## Demo Credentials
- **Customer Account:**
  - Email: `user@example.com`
  - Password: `user123`
- **Admin Account:**
  - Username: `admin` (or Email: `admin@watches.com`)
  - Password: `admin123`
