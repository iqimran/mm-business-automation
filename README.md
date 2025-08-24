# A Full Custom Web Solution for MM Business

Monorepo: **Laravel 11 API** (backend) + **Next.js 14** (frontend) with **MySQL** and **Redis**.  
Fully Dockerized for local development.

## Stack
- Backend: Laravel 11 (PHP 8.2), Sanctum-ready, Redis cache/queue
- Frontend: Next.js 14 (TypeScript, Tailwind, App Router)
- DB: MySQL 8
- Cache/Queue: Redis 7
- Orchestration: Docker Compose

## Run (Local, Docker)
```bash
cp backend/.env.example backend/.env
# edit backend/.env for MySQL/Redis hostnames: mysql, redis

docker compose up -d --build
# API:     http://localhost:8000
# Web:     http://localhost:3000
# MySQL:   localhost:3306  (user: root / pass: root)
# Redis:   localhost:6379
