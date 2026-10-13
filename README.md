# 📌 ASETS (Enterprise Data Hub)

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

**ASETS (Enterprise Data Hub)** is a modern, high-performance data engineering and analytics management platform built with the TALL stack (**Tailwind CSS, Alpine.js, Laravel, Livewire**) and **Filament v3**.

Designed for data engineering teams, enterprise administrators, and analysts, ASETS streamlines spreadsheet ingestion (Excel/CSV), performs automated schema profiling, calculates data health quality scores, generates dynamic real-time frequency charts, and provides secure RESTful APIs and Webhooks.

---

## 🚀 Key Features & Capabilities

### 1. 📊 Smart Datasets Management & Ingestion

- **Heuristic Header Detection**: Intelligent spreadsheet parser that scans initial rows to automatically detect true data headers, skipping arbitrary document titles/kops.
- **Preserved Excel Column Order**: Maintains original spreadsheet column layout (from leftmost primary keys like `NO`, `NIK`, `NAMA` to rightmost attributes).
- **Interactive Payload Inspector**: Search, filter, and inspect complete tabular dataset payloads without pagination limits.
- **Soft-Delete & Trash Management**: Dedicated tabbed isolation between active datasets and trash, complete with bulk restoration and permanent purge options.

### 2. 🛡️ Data Quality Health Scoring & Profiling

- **Completeness Ratio Indicator**: Real-time health score calculating missing value ratios (`null`), data completeness, and valid cell density.
- **Feature Profiling Metadata**: Automatic detection of data types, unique record counts, missing rate percentages, and statistical summaries.

### 3. 📈 Interactive Analytics & Visual Query Builder

- **Categorical Analytics Visualizer**: Real-time Bar Chart and Doughnut Chart rendering powered by Chart.js for instant demographic and attribute distribution analysis.
- **Interactive Visual Query Builder**: Dynamic playground for mapping target dataset attributes across X/Y axes with configurable aggregation functions (`COUNT`, `SUM`, `AVG`, `MAX`, `MIN`).
- **Isolated Chart Lifecycles**: Modularized Chart components utilizing custom Alpine state managers (`Alpine.data`) and strictly isolated canvas scopes (`x-ref` and `wire:ignore`) to prevent Livewire DOM diffing flicker.

### 4. 🔑 API Tokens & Consumer Access Keys

- **Sanctum-Powered Gateway**: Generate scoped API Access Keys for automated ETL pipelines, external third-party systems, or custom mobile applications.
- **Active Key Lifecycle Management**: Track token creation timestamps, last-used activity logs, and revoke/delete access keys dynamically with immediate effect.

### 5. 🔔 Real-Time Webhook Subscriptions

- Event-driven HTTP POST notifications dispatched on `DatasetCreated`, `DatasetUpdated`, and `DatasetDeleted` trigger points.
- Integrated endpoint health tester to verify remote system responsiveness (`HTTP 200 OK`).

### 6. 📜 Activity Audit Trail & Snapshot Rollbacks

- **Audit Logging**: Comprehensive activity tracking detailing user interactions, timestamps, IP addresses, and mutation details.
- **Version History Rollbacks**: Dataset version history snapshots with single-click restoration capabilities.

---

## 🛠️ Tech Stack & Dependencies

- **Backend Framework**: Laravel 11 / 12 (PHP 8.2+)
- **Admin Panel Engine**: Filament v3
- **Reactivity Layer**: Livewire v3 & Alpine.js v3
- **Charting Engine**: Chart.js v4.4
- **Spreadsheet Parser**: PhpSpreadsheet / Laravel Excel
- **Authentication & API**: Laravel Sanctum
- **Styling**: Tailwind CSS with Native Dark/Light Mode Navbar Toggle

---

## ⚡ Local Setup & Installation

Follow these steps to deploy and run the project locally:

### 1. Clone Repository & Install Dependencies

```bash
# Clone the repository
git clone https://github.com/mhmdhfisali/datahub.git
cd datahub

# Install PHP dependencies
composer install

# Install NPM packages
npm install
```

### 2. Environment Configuration

```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Configure your database credentials inside `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=datahub
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Run Migrations & Seeders

```bash
php artisan migrate --seed
```

### 4. Build Frontend & Serve

```bash
# Compile frontend assets
npm run build

# Start local development server
php artisan serve
```

Access the admin portal at: `http://127.0.0.1:8000/admin`

---

## 🧹 Optimization & Cache Cleanup

Whenever you make changes to views, components, or routes, execute this command to clear all caches:

```bash
php artisan view:clear && php artisan optimize:clear && npm run build
```

---

## 📄 License

This software is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
