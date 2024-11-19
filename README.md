## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/alireza2000sajedi/kayer.co.ir-task.git
cd kayer.co.ir-task
```

### 2. Configure Environment Variables
- Copy the `.env.example` file and rename it to `.env`:
  ```bash
  cp .env.example .env
  ```
- Update the `.env` file with your desired configuration, especially the `APP_URL`, `DB_*`, and other settings.

### 3. Build and Start Docker Containers
Run the following command to build and start the containers:
```bash
docker-compose up -d
```

This will:
- Build the Laravel application container.
- Start services for MySQL, Redis, and other dependencies.

---

## Running the Application

### 1. Access the Application
Once the containers are up, visit the application in your browser:
```
http://127.0.0.1:8085
```

### 2. Run Migrations
To set up the database schema, execute the following command:
```bash
docker exec -it laravel-app php artisan migrate --seed
```
Replace `laravel-app` with the name of your Laravel container.

---

## Useful Commands

### Stopping Containers
To stop the containers:
```bash
docker-compose down
```

### Rebuilding Containers
If you make changes to the `Dockerfile` or `docker-compose.yml`, rebuild the containers:
```bash
docker-compose up --build -d
```

### Clearing Laravel Cache
Clear configuration and application caches:
```bash
docker exec -it laravel-app php artisan config:clear
docker exec -it laravel-app php artisan cache:clear
```
