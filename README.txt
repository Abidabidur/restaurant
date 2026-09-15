ONLINE RESTAURANT MANAGEMENT SYSTEM - GROUP 08

MAMP MAC SETUP
<<<<<<< HEAD
1. Copy the "restaurant " folder to:
=======
1. Copy the "restaurant 2" folder to:
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
   /Applications/MAMP/htdocs/restaurant 2

2. Start MAMP Apache and MySQL.
   Apache: 8888
   MySQL: 8889

3. Open phpMyAdmin:
   http://localhost:8888/phpMyAdmin/

4. Import:
   restaurant 2/database/restaurant.sql
   This creates restaurant_db and sample data.

5. Open:
   http://localhost:8888/restaurant 2/

DEMO LOGIN ACCOUNTS
Admin:
  admin@restaurant.com
  123456

Manager:
  manager@restaurant.com
  123456

Customer:
  customer@restaurant.com
  123456

Kitchen:
  kitchen@restaurant.com
  123456

PROJECT ROLES
Admin: Manage users, food menu, revenue
Manager: Manage reservations, tables, invoices
Customer: Food ordering, table reservation, order tracking, reviews
Kitchen Staff: Order status, food preparation workflow, ingredient stock

IMPORTANT
This is a complete beginner-friendly academic project starter. Before production use,
replace MD5 passwords with password_hash/password_verify and add stronger validation,
CSRF protection, file upload validation and payment gateway integration.
