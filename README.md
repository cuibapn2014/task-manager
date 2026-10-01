# Team Task Manager

A full-stack monorepo web application for team task management built with **Laravel 11** (backend API) and **Vue 3 + Vuetify 3 + TypeScript** (frontend SPA).

---

## Architecture & Project Structure

```
project-root/
├── backend/                  # Laravel 11 PHP 8.3 REST API
│   ├── app/
│   │   ├── Enums/            # Backed Enums: Role, TaskStatus
│   │   ├── Http/
│   │   │   ├── Controllers/  # AuthController, TaskController, UserController
│   │   │   ├── Requests/     # LoginRequest, StoreTaskRequest, UpdateTaskRequest
│   │   │   └── Resources/    # UserResource, TaskResource
│   │   ├── Models/           # User, Task
│   │   └── Policies/         # TaskPolicy (Admin vs User authorization)
│   ├── database/
│   │   ├── factories/        # UserFactory, TaskFactory
│   │   ├── migrations/       # Schema for users, tasks, personal_access_tokens
│   │   └── seeders/          # DatabaseSeeder (admin, users, ~30 tasks)
│   └── tests/Feature/        # PHPUnit tests (AuthTest, TaskTest)
├── frontend/                 # Vue 3 + Vite + TypeScript SPA
│   ├── src/
│   │   ├── api/              # Axios instance with Bearer interceptor & API modules
│   │   ├── components/       # TaskForm, TaskStatusChip, AppBar
│   │   ├── router/           # vue-router with auth navigation guards
│   │   ├── stores/           # Pinia auth & task stores
│   │   ├── types/            # TypeScript interfaces & enums matching API
│   │   └── views/            # LoginView, TasksView
│   └── package.json
└── README.md                 # Project setup & architecture overview
```

---

## Setup Instructions

### 1. Backend Setup

```bash
cd backend

# Copy environment config & install PHP dependencies
cp .env.example .env
composer install

# Generate application key
php artisan key:generate

# Run migrations and seed database (creates tables + admin & demo users + sample tasks)
php artisan migrate --seed

# Start Laravel development server (http://localhost:8000)
php artisan serve
```

### 2. Frontend Setup

```bash
cd frontend

# Install Node dependencies
npm install

# Copy environment config
cp .env.example .env.local

# Start Vite development server (http://localhost:5173)
npm run dev
```

---

## Test Credentials & Automated Testing

### Test Accounts

| Role  | Email | Password | Permissions |
|-------|-------|----------|-------------|
| Admin | `admin@example.com` | `password` | Manage all tasks, assign to any user |
| Alice  | `alice@example.com` | `password` | Manage only tasks assigned to self |
| Bob  | `bob@example.com` | `password` | Manage only tasks assigned to self |

### Running Backend Tests

The backend includes a comprehensive PHPUnit feature test suite covering authentication, task CRUD, authorization policies, validation, and filtering.

```bash
cd backend
php artisan test
```

### Running Frontend Type-Check & Production Build

```bash
cd frontend
npm run build
```

---

## Assumptions & Simplifications

- **Sanctum Bearer Tokens in `localStorage`**: Tokens are stored in browser `localStorage` for simplicity in this take-home exercise. In a high-security production app, HttpOnly + SameSite cookies via Sanctum SPA authentication are preferred to mitigate XSS risks.
- **Hard Deletes**: Hard deletion was selected for tasks because soft deletes add query complexity (e.g., handling trashed relationships in filters) without explicit domain necessity.
- **Non-Admin Task Assignment**: Non-admin users cannot assign tasks to other users. Upon task creation by a regular user, the server forces `assigned_to` to match the authenticated user's ID, ignoring any client payload.
- **Cascade Delete Choice**: Foreign key `tasks.assigned_to` uses `cascadeOnDelete()`. If a user account is deleted, their assigned tasks are automatically removed from the database to maintain referential integrity without leaving orphaned records.
- **Server-side Pagination**: The API returns full collections for demo dataset size, structured so `paginate()` / `simplePaginate()` can be enabled without breaking response schema contracts.

---

## What I Would Do Next

1. **Expiring Tokens & Refresh Token Rotation**: Implement short-lived Sanctum access tokens combined with secure refresh token rotation stored in HttpOnly cookies.
2. **Soft Deletes & Trash Recovery**: Add `SoftDeletes` trait to `Task` model with restore and permanent delete permissions for audit and accidental deletion recovery.
3. **Cursor Pagination**: Replace default list fetches with cursor-based pagination (`cursorPaginate()`) for optimal SQL performance on large dataset scrolling.
4. **End-to-End Testing**: Add Playwright or Cypress E2E test suites verifying the full authentication and task management workflows.
5. **Containerization with Docker**: Provide a `docker-compose.yml` file configuring containerized services for PHP 8.3-FPM, Nginx, MySQL 8, and Node.js.
6. **Audit Logs & Activity Stream**: Implement an event listener recording task state mutations (status changes, reassignment) into an `audit_logs` table.
7. **Per-User Rate Limiting**: Enforce fine-grained Sanctum API rate limiters (`RateLimiter::for('api', ...)`) based on authenticated user IDs.
