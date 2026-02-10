# EDN - Escuela de Negocios Digital

This project consists of a Laravel Backend API and a Vue.js Frontend Client.

## Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- PostgreSQL

## Setup Instructions

### 1. Database
Make sure you have a PostgreSQL database named `edn` created.
Credentials should match `backend/.env` (Default: user `postgres`, empty password).

### 2. Backend (Laravel)
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

### 3. Frontend (Vue.js)
cd frontend
npm install
npm run dev

## Architecture
- **Backend**: Laravel 11 (API)
- **Frontend**: Vue 3 + Vite (SPA)
- **Database**: PostgreSQL (JSONB support)
