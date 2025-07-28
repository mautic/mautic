# Security Guidelines for Mautic Docker Setup

## ⚠️ IMPORTANT SECURITY NOTICE

**Never use default passwords in production environments.**

## Pre-deployment Security Checklist

### 1. Password Security
```bash
# Generate secure passwords before first run
export MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32)
export MYSQL_PASSWORD=$(openssl rand -base64 32)
export MAUTIC_ADMIN_PASSWORD=$(openssl rand -base64 16)

# Add these to your .env file
echo "MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD" >> .env
echo "MYSQL_PASSWORD=$MYSQL_PASSWORD" >> .env
echo "MAUTIC_ADMIN_PASSWORD=$MAUTIC_ADMIN_PASSWORD" >> .env
```

### 2. Environment File Security
```bash
# Ensure .env file has proper permissions
chmod 600 .env

# Verify .env is in .gitignore
grep -q "^\.env$" .gitignore || echo ".env" >> .gitignore
```

### 3. Production Deployment
- Use Docker secrets instead of environment variables
- Implement proper SSL/TLS termination
- Use a reverse proxy (nginx/Apache) with security headers
- Enable fail2ban or similar intrusion prevention
- Regular security updates and monitoring

### 4. Container Security
```bash
# Scan images for vulnerabilities
docker scan mautic:latest

# Use security scanning in CI/CD
trivy image mautic:latest
```

## Secure Docker Compose Example

```yaml
version: '3.9'
services:
  mautic:
    build: .
    environment:
      - MAUTIC_DB_PASSWORD_FILE=/run/secrets/db_password
      - MAUTIC_ADMIN_PASSWORD_FILE=/run/secrets/admin_password
    secrets:
      - db_password
      - admin_password

secrets:
  db_password:
    file: ./secrets/db_password.txt
  admin_password:
    file: ./secrets/admin_password.txt
```

## Security Monitoring

Monitor these security aspects:
- Failed login attempts
- Unusual database access patterns
- Container resource usage anomalies
- Network traffic patterns
- File system changes

For production deployments, consider professional security auditing and compliance requirements.
