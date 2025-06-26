Here’s a complete `README.md` file for your **Drug Search and Tracker API** project:

````markdown
# Drug Search and Tracker API

A Laravel-based API service for drug information search and user-specific medication tracking.

## Features

- User authentication (register/login)
- Public drug search using [RxNorm API](https://rxnav.nlm.nih.gov/)
- User medication tracking (add/remove/list)
- Rate limiting for public endpoints
- Caching of RxNorm API responses

## Installation

1. **Clone the repository**

```bash
git clone https://github.com/your-username/drug-search-tracker.git
cd drug-search-tracker
````

2. **Install PHP dependencies**

```bash
composer install
```

3. **Create `.env` file and configure environment variables**

Copy the `.env.example` to `.env`:

```bash
cp .env.example .env
```

Then update the following in your `.env` file:

```env
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
QUEUE_FAILED_DRIVER=database
QUEUE_FAILED_DATABASE=drug-search-tracker
```

4. **Install Redis support (optional but recommended)**

```bash
composer require predis/predis
```

5. **Run migrations**

```bash
php artisan migrate
```

6. **Generate application key**

```bash
php artisan key:generate
```

## API Endpoints

### Authentication

* `POST /api/register` — Register a new user
* `POST /api/login` — Login and receive a token

### Drug Search (Public)

* `GET /api/drugs/search?drug_name={name}` — Search for a drug using name (cached)

### User Medications (Authenticated)

* `GET /api/user/medications` — Get all medications saved by the user
* `POST /api/user/medications` — Add a medication
  **Payload:**

  ```json
  {
    "rxcui": "123456"
  }
  ```
* `DELETE /api/user/medications/{rxcui}` — Remove a medication by its RxCUI

## Testing

Run all tests using:

```bash
php artisan test
```

## Start Development Server

```bash
php artisan serve
```

## License

This project is open-source and available under the [MIT License](LICENSE).

```

Let me know if you'd like me to generate a `LICENSE` file or setup example responses for the endpoints.
```
