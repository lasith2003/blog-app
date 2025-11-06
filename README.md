# Blog Hut

A modern, maintainable PHP blog platform built as a University of Moratuwa IN2120 web programming project. Designed for easy local development, clear separation of concerns, and safe deployment practices.

## Table of contents
- [Summary](#summary)
- [Features](#features)
- [Technology stack](#technology-stack)
- [Prerequisites](#prerequisites)
- [Installation — Local (XAMPP)](#installation---local-xampp)
- [Configuration](#configuration)
- [Database](#database)
- [Running the app](#running-the-app)
- [Project structure](#project-structure)
- [Security & secrets](#security--secrets)
- [Contributing](#contributing)
- [License](#license)
- [Author](#author)

## Summary
Blog Hut is a full-featured blogging application with user authentication, CRUD for posts, comments, reactions, categories, and an admin dashboard. It is intended for learning and demonstration purposes.

## Features
- User registration, login, logout, and password recovery
- Create / edit / delete posts with image uploads
- AJAX comments and reaction system
- Categories and search
- Admin panel for content and user management
- Profile pages and avatars
- Responsive UI with light/dark support

## Technology stack
- PHP 8+
- MySQL 8+
- Apache (XAMPP)
- Frontend: HTML5, CSS3, JavaScript (ES6), Bootstrap 5
- Optional: Composer for PHP dependencies

## Prerequisites
- XAMPP (Apache + MySQL + PHP) or equivalent environment
- Git (for version control)
- Composer (if using external PHP packages)

## Installation — Local (XAMPP)
1. Clone or copy the project into XAMPP's htdocs:
   - C:\xampp\htdocs\BLOG_APP
2. Start Apache and MySQL in the XAMPP control panel.
3. Import database:
   - Open phpMyAdmin -> Create database `blog_hut` -> Import `database.sql`.
4. Configure environment (see next section).
5. Ensure upload folders are writable:
   - `uploads/avatars/` and `uploads/posts/`

## Configuration
Create a local environment file (.env) or update `config/database.php` with your database credentials. Example `.env`:

## Database
- Schema and sample data are provided in `database.sql`.
- Main tables: `users`, `blogPost`, `comments`, `reactions`, `categories`, `badges`, `user_badges`.

## Running the app
Open your browser and navigate to:
http://localhost/BLOG_APP

## Project structure (high level)
- admin/ — Admin pages
- auth/ — Authentication pages
- config/ — Database and constants (do not commit secrets)
- includes/ — Shared components and helpers
- posts/, profile/ — Public and user content pages
- uploads/ — User uploaded files (do not commit)
- css/, js/, images/ — Static assets

## Security & secrets
- Add `.env`, `config/database.php`, and `uploads/` to `.gitignore`.
- Use `config/database.php.example` or `.env.example` in the repo for reference without real credentials.
- If secrets were accidentally committed, remove them from the git index and rotate credentials:
  - git rm --cached .env
  - git commit -m "Remove sensitive files"
  - git push

## Contributing
- Fork the repository, create a feature branch, test locally, and open a PR with a clear description.
- Keep commits focused and include migration or config notes if needed.

## License
MIT — see LICENSE file for details.

## Author
University of Moratuwa — IN2120 Web Programming Project (2025)
