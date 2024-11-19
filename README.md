
# Laravel Dockerized Application

This is a Laravel application set up with Docker to streamline development. Follow the steps below to get the application running.

---

## Prerequisites
Ensure the following tools are installed on your system:
- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)

---

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd <repository-folder>
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
docker exec -it laravel-app php artisan migrate
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

---

## Troubleshooting

### Database Connection Issues
- Ensure the database settings in `.env` match the service names in `docker-compose.yml`:
  ```env
  DB_HOST=mysql
  DB_PORT=3306
  ```

### Permission Issues
If you encounter file permission issues, set the correct ownership:
```bash
docker exec -it laravel-app chown -R www-data:www-data /var/www
```

### Service Logs
Check logs for specific services:
```bash
docker logs <container-name>
```

---

## Folder Structure
```plaintext
├── app/                # Laravel application code
├── docker/             # Docker-related files
├── docker-compose.yml  # Docker Compose configuration
├── .env                # Environment variables
├── README.md           # Project documentation
```

---

## Contributing
Feel free to open issues or submit pull requests for improvements.

---

## License
This project is licensed under the MIT License.
