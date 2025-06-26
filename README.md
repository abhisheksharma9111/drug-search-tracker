# Drug Search and Tracker API

A Laravel-based API service for drug information search and user-specific medication tracking.

## Features

-   User authentication (register/login)
-   Public drug search using RxNorm API
-   User medication tracking (add/remove/list)
-   Rate limiting for public endpoints
-   Caching of RxNorm API responses

## Installation

1. Clone the repository
2. Install dependencies:

```bash
composer install
```

3. Create and configure and add this variables `.env` file

    CACHE_DRIVER=file
    QUEUE_CONNECTION=sync
    QUEUE_FAILED_DRIVER=database
    QUEUE_FAILED_DATABASE=drug-search-tracker

    # Install predis for redis support

    `composer require predis/predis`

4. Run migrations: `php artisan migrate`
5. Generate application key: `php artisan key:generate`

## API Endpoints

### Authentication

-   `POST /api/register` - Register a new user
-   `POST /api/login` - Login user

### Drug Search

-   `GET /api/drugs/search?drug_name={name}` - Search for drugs (public)

### User Medications (require authentication)

-   `GET /api/user/medications` - Get user's medications
-   `POST /api/user/medications` - Add a medication (payload: `rxcui`)
-   `DELETE /api/user/medications/{rxcui}` - Remove a medication

## Testing

Run tests with:

```bash
php artisan test
```

# Start development server

```bash
php artisan serve

```
