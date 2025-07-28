# Mautic Docker Setup

This directory contains Docker configuration files for running Mautic in a containerized environment.

## Quick Start

1. **Clone the repository:**
   ```bash
   git clone https://github.com/mautic/mautic.git
   cd mautic
   ```

2. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

3. **Edit environment variables (optional):**
   ```bash
   nano .env
   ```

4. **Start the services:**
   ```bash
   docker-compose up -d
   ```

5. **Access Mautic:**
   Open your browser and go to `http://localhost:8000`

## Configuration

### Environment Variables

The following environment variables can be configured in your `.env` file:

#### Mautic Configuration
- `MAUTIC_URL`: The URL where Mautic will be accessible (default: http://localhost:8000)
- `MAUTIC_DB_HOST`: Database host (default: db)
- `MAUTIC_DB_PORT`: Database port (default: 3306)
- `MAUTIC_DB_NAME`: Database name (default: mautic)
- `MAUTIC_DB_USER`: Database user (default: mautic)
- `MAUTIC_DB_PASSWORD`: Database password (required)
- `MAUTIC_DB_TABLE_PREFIX`: Database table prefix (optional)

#### Admin User
- `MAUTIC_ADMIN_USERNAME`: Admin username (default: admin)
- `MAUTIC_ADMIN_PASSWORD`: Admin password (required)
- `MAUTIC_ADMIN_EMAIL`: Admin email address
- `MAUTIC_ADMIN_FIRSTNAME`: Admin first name
- `MAUTIC_ADMIN_LASTNAME`: Admin last name

#### PHP Settings
- `PHP_MEMORY_LIMIT`: PHP memory limit (default: 512M)
- `PHP_MAX_EXECUTION_TIME`: Maximum execution time (default: 300)
- `PHP_UPLOAD_MAX_FILESIZE`: Maximum upload file size (default: 256M)
- `PHP_POST_MAX_SIZE`: Maximum POST size (default: 256M)

## Services

### Mautic
- **Image**: Built from the included Dockerfile
- **Ports**: 8000:80
- **Volumes**: 
  - `mautic_config`: Configuration files
  - `mautic_var`: Cache, logs, and temporary files
  - `mautic_media`: Uploaded media files

### Database (MariaDB)
- **Image**: mariadb:10.11
- **Volumes**: `mautic_db` for persistent database storage

### Redis (Optional)
- **Image**: redis:7-alpine
- **Purpose**: Caching and session storage
- **Volumes**: `mautic_redis` for persistent cache

## System Requirements

- Docker 20.10+
- Docker Compose 2.0+
- At least 2GB RAM
- At least 5GB disk space

## Volumes

The following volumes are created for persistent data:

- `mautic_config`: Stores Mautic configuration files
- `mautic_var`: Stores cache, logs, and temporary files
- `mautic_media`: Stores uploaded media files
- `mautic_db`: Stores database data
- `mautic_redis`: Stores Redis cache data

## Cron Jobs

The Docker container automatically sets up the following cron jobs:

- **Segment Updates**: Every 5 minutes
- **Campaign Updates**: Every 5 minutes  
- **Campaign Triggers**: Every 5 minutes
- **Email Sending**: Daily at 2:10 AM
- **Maintenance Cleanup**: Weekly on Sunday at 4:00 AM
- **IP Lookup Database Update**: Weekly on Sunday at 1:05 AM

## Commands

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f mautic
```

### Access Mautic Console
```bash
docker-compose exec mautic php bin/console
```

### Update Mautic
```bash
# Pull latest changes
git pull origin main

# Rebuild and restart
docker-compose up -d --build
```

### Backup Database
```bash
docker-compose exec db mysqldump -u mautic -p mautic > mautic_backup.sql
```

### Restore Database
```bash
docker-compose exec -T db mysql -u mautic -p mautic < mautic_backup.sql
```

## Development

For development purposes, you can mount the source code directly:

```yaml
# Add this to the mautic service in docker-compose.yml
volumes:
  - .:/var/www/html
  - mautic_config:/var/www/html/config
  - mautic_var:/var/www/html/var
  - mautic_media:/var/www/html/media
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure database credentials match in `.env` file
   - Wait for database to fully start before accessing Mautic

2. **Permission Errors**
   - Ensure proper file permissions: `docker-compose exec mautic chown -R www-data:www-data /var/www/html`

3. **Memory Errors**
   - Increase `PHP_MEMORY_LIMIT` in `.env` file
   - Ensure host system has sufficient memory

4. **Slow Performance**
   - Enable Redis caching
   - Increase allocated resources
   - Optimize database queries

### Health Check

The container includes a health check that verifies Mautic is responding:

```bash
docker-compose ps
```

### Logs

Check application logs:
```bash
# Mautic logs
docker-compose exec mautic tail -f /var/www/html/var/logs/prod.log

# Apache logs
docker-compose exec mautic tail -f /var/log/apache2/error.log
```

## Security Considerations

1. **Change Default Passwords**: Update all default passwords in `.env`
2. **Use HTTPS in Production**: Configure SSL/TLS certificates
3. **Regular Updates**: Keep Mautic and Docker images updated
4. **Backup Strategy**: Implement regular backups of volumes
5. **Network Security**: Use proper firewall rules and network isolation

## Production Deployment

For production deployment:

1. Use a reverse proxy (nginx/Apache) with SSL
2. Set up proper backup strategies
3. Monitor resource usage
4. Configure log rotation
5. Use secrets management for sensitive data
6. Set up monitoring and alerting

## Support

For issues specific to this Docker setup, please create an issue in the Mautic repository.

For general Mautic support, visit:
- [Mautic Documentation](https://docs.mautic.org/)
- [Mautic Community](https://www.mautic.org/community/)
- [Mautic Forums](https://forum.mautic.org/)
