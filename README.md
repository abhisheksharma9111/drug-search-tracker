Your `README.md` file is very well-structured and almost complete! Below is a **cleaned-up and corrected version** of your Markdown file with minor formatting fixes and corrections (like fixing the `git` command):

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
git clone https://github.com/abhisheksharma9111/drug-search-tracker.git
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

Then connect your database and update the following variables in the `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drug-search-tracker
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
QUEUE_FAILED_DRIVER=database
QUEUE_FAILED_DATABASE=drug-search-tracker
```

4. **Install Redis support (optional but recommended)**

```bash
composer require predis/predis
```

5. **Run database migrations**

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

Would you like:
- A sample `.env` configuration?
- Sample `POSTMAN` collection or Swagger file for API testing?
- A `LICENSE` file (MIT) added? 

Let me know and I’ll generate it.
```
