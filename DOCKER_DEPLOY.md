# Docker Deployment Guide for Render

This guide explains how to deploy your Laravel application to Render using Docker.

## Files Created

1. **Dockerfile** - Multi-stage Docker image configuration
2. **.dockerignore** - Files to exclude from Docker build
3. **docker/nginx/default.conf** - Nginx web server configuration
4. **docker/supervisor/supervisord.conf** - Process manager configuration
5. **docker/start.sh** - Startup script for the container
6. **render.yaml** - Render deployment configuration (Blueprint)

## Local Testing

Before deploying to Render, test your Docker image locally:

```bash
# Build the Docker image
docker build -t pharmedice-customer-area-be .

# Run the container
docker run -p 8080:8080 \
  -e APP_KEY=base64:your-app-key-here \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=pharmedice \
  -e DB_USERNAME=postgres \
  -e DB_PASSWORD=your-password \
  pharmedice-customer-area-be

# Access the application at http://localhost:8080
```

## Deploying to Render

### Option 1: Using Render Blueprint (render.yaml)

1. Commit all changes to your Git repository:
   ```bash
   git add .
   git commit -m "Add Docker configuration for Render"
   git push
   ```

2. Go to [Render Dashboard](https://dashboard.render.com/)

3. Click "New +" → "Blueprint"

4. Connect your GitHub/GitLab repository

5. Render will automatically detect `render.yaml` and configure:
   - Web service with Docker
   - PostgreSQL database
   - Environment variables

6. Update the environment variables in Render dashboard:
   - `APP_URL` - Your Render service URL
   - `FRONTEND_URL` - Your frontend URL
   - `RESEND_KEY` - Your Resend API key
   - `AWS_ACCESS_KEY_ID` - Your AWS access key
   - `AWS_SECRET_ACCESS_KEY` - Your AWS secret key

### Option 2: Manual Setup

1. **Create PostgreSQL Database:**
   - Go to Render Dashboard → New → PostgreSQL
   - Name: `pharmedice-db`
   - Save the connection details

2. **Create Web Service:**
   - Go to Render Dashboard → New → Web Service
   - Connect your repository
   - Settings:
     - **Name:** pharmedice-customer-area-be
     - **Runtime:** Docker
     - **Region:** Oregon (or your preferred region)
     - **Branch:** main
     - **Root Directory:** Leave empty or specify if in monorepo
     - **Dockerfile Path:** Dockerfile

3. **Configure Environment Variables:**
   Add all variables from `.env` as environment variables in Render

4. **Health Check:**
   - Path: `/`
   - This ensures Render knows when your app is ready

## Important Notes

### Auto-Migrations

By default, migrations are commented out in `docker/start.sh`. To enable automatic migrations on each deployment, uncomment this line:

```bash
# php artisan migrate --force
```

**⚠️ Warning:** Be cautious with auto-migrations in production!

### Port Configuration

Render expects applications to listen on the port specified in the `PORT` environment variable. This Docker setup uses port **8080** by default.

### Storage and Logs

- Laravel storage is ephemeral on Render
- Use S3 (already configured) for file uploads
- Logs are available through Render's dashboard

### Queue Workers

The supervisor configuration includes a Laravel queue worker that will process jobs automatically.

### Database Connection

The application waits for the database to be ready before starting. The connection timeout is 30 seconds.

## Troubleshooting

### Container won't start
Check Render logs for errors. Common issues:
- Missing required environment variables
- Database connection issues
- Permission problems

### Database connection timeout
Ensure the database is in the same region as your web service for better connectivity.

### Performance issues
Consider upgrading your Render plan or optimizing your Laravel caching strategy.

## Commands

Run artisan commands in Render:

```bash
# Via Render Shell (Dashboard → Shell tab)
php artisan migrate
php artisan cache:clear
php artisan config:clear
```

## Monitoring

- View logs in Render Dashboard → Logs tab
- Monitor performance in Metrics tab
- Set up custom metrics if needed

## Scaling

To handle more traffic:
1. Upgrade your Render plan
2. Increase `numprocs` for queue workers in `supervisord.conf`
3. Consider Redis for caching and sessions

## Security Checklist

- ✅ APP_DEBUG set to false in production
- ✅ APP_KEY is properly generated
- ✅ JWT_SECRET is secure
- ✅ Database credentials are secure
- ✅ AWS credentials are secure
- ✅ CORS configuration is set properly
- ✅ Rate limiting is enabled

## Support

For issues related to:
- **Render Platform:** [Render Support](https://render.com/docs)
- **Laravel:** [Laravel Documentation](https://laravel.com/docs)
- **Docker:** [Docker Documentation](https://docs.docker.com)
