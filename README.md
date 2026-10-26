# PGLife — Paying Guest Finder

PGLife is a PHP and MySQL-based web application for exploring paying guest (PG) accommodation listings. Users can browse properties by city, view property details and amenities, create an account, sign in, and save properties to an interested list.

## Features

- Browse PG properties and filter listings by city
- View property details, amenities, ratings, and images
- User sign-up, login, logout, and dashboard
- Save or remove properties from an interested list
- PHP backend with a MySQL/MariaDB database
- Responsive interface using HTML, CSS, JavaScript, and Bootstrap
- React-based property-list components included in `react-app/` and the compiled assets used by the PHP site

## Technologies

- PHP
- MySQL / MariaDB
- HTML5, CSS3, JavaScript
- Bootstrap and jQuery
- React (source and build assets included)
- XAMPP for local development

## Run locally with XAMPP

1. Install and open [XAMPP](https://www.apachefriends.org/).
2. Copy this `PGLife` folder into `C:\xampp\htdocs\PGLife`. Ensure `index.php` is directly inside that folder.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin/`. Create a database named `pglife` if it does not exist.
5. Select the `pglife` database, choose **Import**, and import `database/pglife.sql`. If you already have this database populated, do not import it again without backing it up first.
6. Check `includes/database_connect.php`. The included defaults are for a standard local XAMPP setup (`127.0.0.1`, user `root`, blank password, database `pglife`). Adjust these values for your own local setup if needed. Never use the root account or commit real hosting credentials for a public deployment.
7. Open `http://localhost/PGLife/` in your browser.

More detailed local setup and testing notes are in [`README_SETUP.txt`](README_SETUP.txt).

## Database

The SQL export is in `database/pglife.sql`. It creates the tables and includes demonstration data for local testing. Treat any included sample account data as demo data only; do not reuse real passwords or personal information.

## Security notes

- The database connection file is configured for local XAMPP development.
- Before deploying publicly, configure database credentials through your hosting provider and keep secrets out of GitHub.
- Use HTTPS and review server settings before putting the site online.
- Do not upload private credentials, production database exports, or real user data.

## Repository structure

```text
PGLife/
├── api/                 # PHP endpoints for listing and account actions
├── css/                 # Stylesheets
├── database/            # SQL schema and demo data
├── img/                 # Images and icons
├── includes/             # Shared PHP components and DB connection
├── js/                  # JavaScript assets
├── react-app/           # React source and build files
├── index.php            # Home page
├── property_list.php    # Property listings
├── property_detail.php  # Property details
├── dashboard.php        # User dashboard
└── logout.php           # Logout handler
```

## Future improvements

- Deploy to a PHP/MySQL-compatible hosting provider
- Add more property listings and automated tests
- Add environment-based configuration for database credentials

---

Created as a learning/portfolio project.
