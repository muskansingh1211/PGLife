PGLIFE — LOCAL SETUP AND TESTING
================================

This folder keeps the PHP pages, CSS, JavaScript, images, and React property-list bundle from your original project. The backend files have been updated to use prepared statements, validated inputs, and password_hash/password_verify for new accounts. Existing SHA-1 sample accounts from the provided SQL export remain compatible: after their first successful login, their password hash is upgraded automatically.

REQUIREMENTS
------------
- XAMPP (Apache, PHP, MariaDB/MySQL)
- A browser
- PHP's mysqli extension (enabled by default in standard XAMPP)

INSTALL ON XAMPP
----------------
1. Extract this ZIP.
2. Copy the PGLife folder to C:\xampp\htdocs\PGLife\ so that this file exists:
   C:\xampp\htdocs\PGLife\index.php
3. Open XAMPP Control Panel and start Apache and MySQL.
4. Open http://localhost/phpmyadmin
5. If starting fresh, create a database named `pglife`, click it, choose Import, select database/pglife.sql, and click Import/Go.
   IMPORTANT: The SQL file creates the tables and contains demonstration data. If you already imported the original PGLife SQL and your `pglife` database has the expected tables/data, do NOT import the SQL again. Back up your database before any schema changes.
6. If you already have the original starter database and want the recommended unique indexes plus longer password column, back up first and run database/upgrade_existing.sql exactly once. Skip this for a fresh import of database/pglife.sql.
7. Open includes/database_connect.php. The defaults are for a standard local XAMPP installation (root user, blank password, database pglife). If your MySQL root user has a password, set $db_password in that file to your local password. On hosting, use the database host/user/password/database name supplied by the host instead; do not use root.
8. Open http://localhost/PGLife/index.php

TEST CHECKLIST
--------------
- Open the signup modal and create a new test account.
- Login with that account; the navbar should show the account name after the page reloads.
- Open Dashboard and confirm profile values load.
- Open a city from the home page or footer, e.g. property_list.php?city=Delhi.
- Click a property and confirm property_detail.php loads its amenities and testimonials.
- Login first, then click the heart icon to add/remove a property from the interested list.
- Open Dashboard and confirm interested properties appear.
- Click Logout and verify that Dashboard redirects to the home page if reopened directly.

DATA INCLUDED IN THIS SQL EXPORT
--------------------------------
The supplied SQL file has the seven expected tables: users, cities, properties, amenities, properties_amenities, testimonials, and interested_users_properties. It contains only five property records: two in Delhi and three in Mumbai. The four sample user records in this export use legacy SHA-1 password hashes; the login code remains backward-compatible and upgrades a sample hash to a modern hash only after that account successfully logs in. Bengaluru and Hyderabad exist in the cities table but contain no property records in this export, so those pages will correctly show “No PG to list” until property records are added.

FILES AND ROUTES
----------------
- index.php: homepage and city search
- property_list.php: React-powered property list; its data comes from api/get_properties_by_city.php
- property_detail.php: selected property details
- dashboard.php: logged-in user's profile and interested properties
- api/signup_submit.php: signup processing (JSON for AJAX)
- api/login_submit.php: login processing and session creation (JSON for AJAX)
- api/toggle_interested.php: add/remove interested property
- api/get_properties_by_city.php: JSON endpoint used by the property-list React bundle
- logout.php: clears the session and redirects home
- react-app/: source/build files for the separate React application; the main PHP site currently uses the prebuilt bundle in the top-level js/ folder, so npm is not required just to run the PHP site.

SECURITY / DEPLOYMENT
---------------------
This is an educational project and should not be considered production-ready just because it runs locally. The SQL export includes demonstration user profile rows and legacy password hashes; review/remove seed user data before a public deployment. Before making it public, configure a least-privilege database user and HTTPS, review CSRF protection for state-changing requests (the provided UI currently toggles interest by GET to stay compatible with its existing bundle), set secure session cookie settings, and test the whole application. Never commit database credentials or real user exports to a public repository.
