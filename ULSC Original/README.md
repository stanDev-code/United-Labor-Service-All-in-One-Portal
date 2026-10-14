# Inventory & Maintenance Management System

A responsive web-based system for managing company vehicles, equipment, tools, maintenance records, and breakdown/repair tracking.

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache (XAMPP recommended)

## Installation

1. **Copy files** to your web server document root (e.g. `C:\xampp\htdocs\ULSC`).

2. **Configure database** – Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'ulsc_inventory');
   ```

3. **Run setup** – Open in browser:  
   `http://localhost/ULSC/setup.php`  
   This creates the database, tables, and default admin user.

4. **Default login:**  
   - Username: `admin`  
   - Password: `admin123`

5. **Delete `setup.php`** after installation for security.

## User Roles

| Role | Permissions |
|------|-------------|
| **Admin** | Full CRUD on equipment, tools, maintenance, breakdowns; manage staff; view reports |
| **Staff** | View equipment/vehicles; add maintenance updates; report breakdowns; view history |

## Module Overview

- **Dashboard** – Summary cards, bar/pie charts, recent activity, Tools tab
- **Equipment & Vehicles** – Track items with ID, name, category, status, assigned operator
- **Maintenance** – Log maintenance with type, performed by, next schedule
- **Breakdowns & Repairs** – Log incidents with category, repair action, downtime (minutes)
- **Tools Inventory** – Track tools with quantity, condition, assigned to, last inspection
- **History & Reports** – Searchable logs with filters
- **Manage Staff** (Admin only) – Add/edit users, roles, passwords

## Folder Structure

```
ULSC/
├── config/database.php
├── includes/auth.php, header.php, footer.php
├── assets/css/style.css
├── sql/schema.sql
├── setup.php          (run once, then delete)
├── login.php, logout.php
├── index.php          (dashboard)
├── equipment.php
├── maintenance.php
├── breakdowns.php
├── tools.php
├── reports.php
└── users.php          (admin only)
```

## Security

- Password hashing (bcrypt)
- Session-based authentication
- Role-based access control
- Prepared statements (SQL injection prevention)
- Input validation and output escaping
