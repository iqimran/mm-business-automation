# A Full Custom Web Solution for MM Business

Monorepo: **Laravel 11 API** (backend) + **Next.js 14** (frontend) with **MySQL** and **Redis**.  
Fully Dockerized for local development.

## 🏗️ System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   React Frontend │───▶│  Laravel API    │───▶│     MySQL       │
│   (Port 3000)   │    │  (Port 8000)    │    │   (Port 3306)   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │     Redis       │
                       │ (Cache & Queue) │
                       │   (Port 6379)   │
                       └─────────────────┘
```

## Tech Stack

### Backend
- **Framework**: Laravel 11 (PHP 8.2)
- **Authentication**: Laravel JWT Authentication
- **Authorization**: Spatie Laravel Permission
- **Database**: MySQL 8.0
- **Cache & Queue**: Redis 7
- **Security**: CSRF protection, Rate limiting, SQL injection prevention

### Frontend
- **Framework**: Next.js 14 (TypeScript, App Router)
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **Icons**: Lucide React
- **HTTP Client**: Axios

### DevOps
- **Containerization**: Docker & Docker Compose
- **Environment**: Development & Production ready

## Run (Local, Docker)
```bash
# Clone the repository
git clone https://github.com/iqimranbd/mm-business-automation.git
cd mm-business-automation

cp backend/.env.example backend/.env
# edit backend/.env for MySQL/Redis hostnames: mysql, redis

# Start all services
docker compose up -d --build

# Run Laravel migrations
docker-compose exec backend php artisan migrate --seed

# Access the application
# API:     http://localhost:8000
# Web:     http://localhost:3000

# MySQL:   localhost:3306  (user: root / pass: root)
# Redis:   localhost:6379

### Manual Setup

#### Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

#### Frontend Setup
```bash
cd frontend
npm install
npm run dev
```

## 🔒 Security Features

- **Authentication**: Token-based authentication with Laravel Sanctum
- **Authorization**: Role-based permissions (Admin, Manager, User)
- **CSRF Protection**: Built-in Laravel CSRF protection
- **Rate Limiting**: API rate limiting for security
- **SQL Injection Prevention**: Eloquent ORM with parameter binding
- **Password Security**: bcrypt hashing

## ⚡ Performance Optimizations

- **Database Indexing**: Optimized indexes on frequently searched fields
- **Eager Loading**: Prevent N+1 query problems
- **Redis Caching**: Cache frequently accessed data
- **Queue System**: Background job processing for reports
- **API Response Caching**: Cache API responses for better performance

## 🐳 Docker Services

- **backend**: Laravel API application
- **frontend**: React application
- **mysql**: MySQL 8.0 database
- **redis**: Redis for caching and queues
- **nginx**: Reverse proxy (production)

## 📝 Git Workflow

The project follows a structured git workflow with meaningful commit messages:

## 🧪 Testing

```bash
# Backend tests
cd backend
php artisan test

# Frontend tests
cd frontend
npm run test
```

## 📚 API Documentation

API documentation is available at `/api/documentation` when the backend is running.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request