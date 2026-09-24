# Docker Setup Guide

This application is fully integrated with Docker for easy deployment and development.

## Prerequisites

- Docker installed on your system
- Docker Compose installed

## Quick Start

1. Clone the repository and navigate to the project directory
2. Run the following command to start all services:

```bash
docker-compose up -d
```

This will start:
- **Web Application**: http://localhost:8080
- **MySQL Database**: localhost:3306
- **phpMyAdmin**: http://localhost:8081

## Services

### Web Application
- **Container**: studentdetails_web
- **Port**: 8080
- **PHP Version**: 8.2
- **Web Server**: Apache

### MySQL Database
- **Container**: studentdetails_mysql
- **Port**: 3306
- **Version**: 5.7
- **Database**: student_details_db
- **User**: student_user
- **Password**: student_password

### phpMyAdmin
- **Container**: studentdetails_phpmyadmin
- **Port**: 8081
- **URL**: http://localhost:8081
- **Credentials**: student_user / student_password

## Environment Variables

The application uses environment variables for database configuration:

| Variable | Value |
|----------|-------|
| DB_HOST | mysql |
| DB_NAME | student_details_db |
| DB_USER | student_user |
| DB_PASS | student_password |

## Database Initialization

The database is automatically initialized with:
- SQL files from the `database/` directory
- Custom MySQL configuration from `database/my.cnf`

## Development

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f web
docker-compose logs -f mysql
```

### Stop Services

```bash
docker-compose down
```

### Stop and Remove Volumes

```bash
docker-compose down -v
```

### Rebuild Containers

```bash
docker-compose up -d --build
```

### Access Container Shell

```bash
# Web container
docker-compose exec web bash

# MySQL container
docker-compose exec mysql bash
```

## File Uploads

Uploaded files are persisted in the `uploads/` directory, which is mounted as a volume.

## Troubleshooting

### Database Connection Issues

If you encounter database connection errors:
1. Ensure MySQL container is healthy: `docker-compose ps`
2. Check MySQL logs: `docker-compose logs mysql`
3. Verify environment variables in docker-compose.yml

### Permission Issues

If you encounter permission issues with file uploads:
```bash
docker-compose exec web chown -R www-data:www-data /var/www/html/uploads
```

### Port Conflicts

If ports 8080, 3306, or 8081 are already in use, modify the port mappings in docker-compose.yml:
```yaml
ports:
  - "8081:80"  # Change to "8082:80" if 8081 is in use
```

## Production Deployment

For production deployment, consider:
1. Changing default passwords
2. Using environment-specific configuration files
3. Implementing proper backup strategies
4. Using SSL/TLS certificates
5. Setting up proper monitoring and logging

## Local Development (XAMPP)

The application also supports local XAMPP development. The `config.php` file automatically detects the environment:
- Uses Docker environment variables when running in Docker
- Falls back to XAMPP defaults (localhost/root/empty password) for local development
