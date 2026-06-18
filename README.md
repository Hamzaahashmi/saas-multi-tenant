# Laravel SaaS Starter

A multi-tenant SaaS boilerplate built with Laravel 12. Each company that registers gets their own isolated MySQL database, a 14-day trial, and a full-featured workspace with team management, billing, and an activity log.

## What's included

- **Multi-tenancy** — per-tenant database isolation using [stancl/tenancy](https://tenancyforlaravel.com/)
- **Authentication** — Sanctum token auth with a custom middleware that works correctly after the DB connection switches per-tenant
- **Roles** — admin, manager, member using Spatie Laravel Permission
- **Team management** — invite by email, change roles, remove members
- **Invitation system** — tokenised invite links with expiry
- **Activity log** — every action recorded per tenant (settings changes, invites, logins)
- **Tenant settings** — timezone, date format, company info stored per tenant
- **Billing** — Stripe checkout sessions, customer portal, subscription management
- **Super admin API** — central-domain endpoints for managing tenants, protected by a header key
- **Blade + Bootstrap 5 frontend** — full UI included, no build step required

## Requirements

- PHP 8.2+
- MySQL / MariaDB
- Composer

## Local setup

```bash
git clone <repo-url>
cd saas
composer install
cp .env.example .env
php artisan key:generate
```

Create the central database:

```sql
CREATE DATABASE saas_starter;
```

Update `.env` with your DB credentials, then run migrations:

```bash
php artisan migrate
php artisan serve --port=8001
```

Visit `http://127.0.0.1:8001` to see the landing page.

## Registering a tenant

Go to `/register`, fill in your company name, pick a subdomain (e.g. `acme`), and submit. This will:

1. Create a `tenant_<uuid>` database
2. Run all tenant migrations
3. Seed the default roles (admin, manager, member)
4. Create your admin account
5. Redirect you to `http://acme.localhost:8001/dashboard`

Add the subdomain to your hosts file first:

```
127.0.0.1  acme.localhost
```

## How multi-tenancy works

When a request comes in on a subdomain, the `InitializeTenancyByDomain` middleware reads the host, looks up the matching tenant, and switches the MySQL connection to that tenant's database. Every model query after that point hits the correct isolated database automatically.

The central database (`saas_starter`) only holds `tenants` and `domains` records. All user data, roles, tokens, and activity logs live in the per-tenant databases.

## Role access

| Feature | Admin | Manager | Member |
|---|---|---|---|
| Dashboard | ✅ | ✅ | ✅ |
| Team — view | ✅ | ✅ | ✅ |
| Team — invite | ✅ | ✅ | ❌ |
| Team — change roles / remove | ✅ | ❌ | ❌ |
| Activity log | ✅ | ✅ | ❌ |
| Settings | ✅ | ❌ | ❌ |
| Billing | ✅ | ❌ | ❌ |

## Stripe billing

The billing endpoints are wired up but need real Stripe keys to function. Add to `.env`:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_PLAN_STARTER=price_...
STRIPE_PLAN_PRO=price_...
STRIPE_PLAN_ENTERPRISE=price_...
```

Without these, the billing page still loads and shows subscription status, but checkout/portal will return an error.

## API overview

All tenant API endpoints require `Authorization: Bearer <token>` and must be called on the tenant's subdomain.

| Method | Endpoint | Access |
|---|---|---|
| POST | `/api/register` | Public (central) |
| POST | `/api/login` | Public (tenant) |
| POST | `/api/logout` | Auth |
| GET/PUT | `/api/me` | Auth |
| PUT | `/api/me/password` | Auth |
| GET | `/api/dashboard` | Auth + subscription |
| GET/PUT | `/api/settings` | Admin |
| GET | `/api/activity` | Admin, Manager |
| GET/POST | `/api/team` | Auth + subscription |
| PUT/DELETE | `/api/team/{user}` | Admin |
| POST | `/api/team/invite` | Admin, Manager |
| GET/DELETE | `/api/team/invitations` | Admin, Manager |
| POST | `/api/invite/{token}/accept` | Public (tenant) |
| GET/POST | `/api/billing/*` | Auth |

Super admin endpoints (central domain, `X-Admin-Key` header required):

| Method | Endpoint |
|---|---|
| GET | `/api/admin/tenants` |
| GET | `/api/admin/tenants/{id}` |
| DELETE | `/api/admin/tenants/{id}` |

## Tech stack

- Laravel 12
- stancl/tenancy v3
- Laravel Sanctum
- Spatie Laravel Permission
- Stripe PHP SDK
- Bootstrap 5 (CDN)
