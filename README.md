# Sunscape Hotel

Sunscape Hotel is a Symfony web application for presenting a resort website,
managing rooms and services, and accepting guest reservations.

## Features

- Hotel home page with room availability search
- Room and suite listings with detail pages
- Dining and island experience pages
- Guest registration, login, account pages, and logout
- Authenticated reservation workflow
- Contact form
- Admin login and dashboard
- Admin management for rooms, services, reservations, and settings
- Doctrine ORM persistence with MySQL
- Symfony form CSRF protection and password hashing

## Requirements

- PHP 8.4 or newer
- Composer
- MySQL 8.0 or a compatible MySQL server
- Symfony CLI (recommended)
- Docker Desktop (optional, for the included MySQL and phpMyAdmin services)

The application uses Symfony 8.1, Doctrine ORM 3, Twig, Symfony Security,
Symfony Forms, and the Symfony Asset Mapper.

## Installation

Clone the repository and install PHP dependencies:

```bash
composer install
```

Create a local environment file from the safe template:

```bash
copy .env.example .env.local
```

Update `.env.local` with a local application secret and database connection.
For the included Docker MySQL service, use:

```dotenv
APP_ENV=dev
APP_SECRET=replace-with-a-random-secret
DATABASE_URL="mysql://symfony:<docker-password>@127.0.0.1:3306/sunscape_symfony?serverVersion=8.0&charset=utf8mb4"
```

Use the MySQL username, password, and database name configured in
`docker-compose.yaml` when running the included development container.

Do not commit `.env.local` or real production credentials.

## Database

Start the development MySQL server and phpMyAdmin with Docker:

```bash
docker compose -f docker-compose.yaml up -d
```

Create or update the database schema through Doctrine migrations:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

The included phpMyAdmin instance is available at
<http://127.0.0.1:8082>.

The application does not include a fixture loader. Populate rooms, services,
users, and administrators through the database or the application’s supported
management workflows before testing a complete booking scenario.

## Running the application

Using the Symfony CLI:

```bash
symfony server:start
```

The application is then available at <http://127.0.0.1:8000> (the exact port
may vary).

Without the Symfony CLI, use PHP’s built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

## Main routes

| Area | Routes |
| --- | --- |
| Home and booking search | `/` |
| Rooms | `/rooms`, `/rooms/{slug}` |
| Dining | `/dining`, `/dining/{id}` |
| Experiences and brands | `/experiences`, `/el-juwan`, `/island-bar`, `/beach-bbq`, `/malaya-tours` |
| Contact | `/contact` |
| Guest authentication | `/login`, `/register`, `/logout` |
| Guest account | `/account` |
| Reservations | `/booking` |
| Admin authentication | `/admin/login`, `/admin/logout` |
| Admin management | `/admin`, `/admin/rooms`, `/admin/services`, `/admin/reservations`, `/admin/settings` |

The booking and account areas require a guest account. The admin area requires
an account with `ROLE_ADMIN`.

## Testing

Configure the test database in `.env.test`, then run:

```bash
php bin/phpunit
```

The test suite includes public page checks, authentication flows, booking
access control, reservation processing, and admin controller coverage.

## Project structure

```text
assets/       JavaScript and CSS source files
config/       Symfony and package configuration
migrations/   Doctrine migrations
public/       Web entry point and public images
src/Controller Application and admin controllers
src/Entity     Doctrine entities
src/Repository Doctrine repositories
templates/     Twig templates
tests/         PHPUnit and Symfony functional tests
```

## Development commands

```bash
# Clear the application cache
php bin/console cache:clear

# List registered routes
php bin/console debug:router

# Validate the service container
php bin/console lint:container

# Inspect Doctrine mappings
php bin/console doctrine:schema:validate
```

## Security

- Keep secrets and production database credentials outside committed files.
- Use HTTPS and secure, unique passwords in deployed environments.
- Replace development Docker passwords before sharing or deploying the stack.
- Review admin users and roles before exposing `/admin` publicly.
