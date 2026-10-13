# 📌 ASETS (Enterprise Data Hub)

**ASETS (Enterprise Data Hub)** is an enterprise-grade data engineering, ingestion, and analytics management platform built with Laravel 11/12, Filament v3, Livewire 3, Alpine.js, and Chart.js.

Designed for data engineering teams, enterprise administrators, and analysts, ASETS streamlines complex spreadsheet ingestion (Excel/CSV) with heuristic header detection, calculates data health scores, renders real-time frequency distribution charts, and provides secure RESTful API gateways and Webhooks.

---

## 🚀 Key Features & Enterprise Capabilities

### 1. 📊 Smart Datasets Ingestion & Management

- **Heuristic Header Detection**: Intelligent parser that scans initial rows to automatically locate true column headers, skipping arbitrary document titles, logos, or kops.
- **Preserved Excel Layout Order**: Maintains original spreadsheet column sequences (from primary keys like `NO`, `NIK`, `NAMA` to rightmost attributes).
- **Interactive Payload Inspector**: Search, filter, and inspect complete tabular dataset payloads without pagination limits.
- **Soft-Delete & Trash Management**: Tabbed isolation between active datasets and trash, complete with bulk restoration and permanent purge options.

### 2. 🛡️ Data Quality Profiling & Health Index

- **Completeness Ratio Indicator**: Real-time health score calculating missing value ratios (`null`), data completeness percentages, and valid cell density.
- **Feature Profiling**: Automatic schema detection, unique record counts, and null-rate analytics per column attribute.

### 3. 📈 Interactive Analytics & Visual Query Builder

- **Categorical Analytics Visualizer**: Real-time Bar Chart and Doughnut Chart rendering powered by Chart.js for instant demographic and attribute distribution analysis.
- **Interactive Visual Query Builder**: Dynamic playground for mapping target dataset attributes across X/Y axes with configurable aggregation functions (`COUNT`, `SUM`, `AVG`, `MAX`, `MIN`).
- **Isolated Chart Lifecycles**: Modularized Chart components utilizing custom Alpine state managers (`Alpine.data`) and strictly isolated canvas scopes (`x-ref` and `wire:ignore`) to prevent Livewire hydration flicker.

### 4. 🎨 Modern UX/UI & Global Data-Flow Loader

- **Custom Split-Screen Authentication**: Futuristic dark-themed login interface showcasing real-time data hub stats and brand highlights.
- **Global Interstitial Data Overlay**: Instant, zero-delay solid dark loading overlay with animated data orbits, pulsing database icons, and dynamic status messages.
- **Navbar Theme Switcher**: Dedicated Dark/Light mode toggle positioned directly on the top navbar for immediate access.
- **Enhanced Sidebar**: Dynamic dataset count badges, active route highlights, and a real-time system status widget.

### 5. 🔑 API Tokens & Access Keys

- **Sanctum API Gateway**: Scoped API Access Keys for automated ETL pipelines, external third-party systems, or custom mobile applications.
- **Active Key Lifecycle & Revocation**: Track token creation timestamps, last-used activity logs, and revoke/delete access keys dynamically with immediate effect.

### 6. 🔔 Real-Time Webhooks & Audit Trail

- **Event Dispatches**: Automated HTTP POST notifications sent to remote endpoints on `DatasetCreated`, `DatasetUpdated`, and `DatasetDeleted` trigger points.
- **Audit Logging**: Comprehensive activity tracking detailing user interactions, timestamps, IP addresses, and mutation details.

---

## 🛠️ Tech Stack

- **Backend Framework**: Laravel 11 / 12 (PHP 8.2+)
- **Admin Panel Engine**: Filament v3
- **Reactivity Layer**: Livewire v3 & Alpine.js v3
- **Charting Engine**: Chart.js v4.4
- **Spreadsheet Parser**: PhpSpreadsheet / Laravel Excel
- **Authentication & API**: Laravel Sanctum
- **Styling**: Tailwind CSS

---

## ⚡ Quick Setup & Installation

### 1. Clone & Install Dependencies

```bash
git clone https://github.com/mhmdhfisali/datahub.git
cd datahub

composer install
npm install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database connection inside `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=datahub
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Run Migrations & Build Frontend

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Access the admin portal at: `http://127.0.0.1:8000/admin`

---

## 🧹 Optimization & Cache Clear

Whenever you modify views, routes, or components, execute:

```bash
php artisan view:clear && php artisan optimize:clear && npm run build
```

---

## 📄 License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
