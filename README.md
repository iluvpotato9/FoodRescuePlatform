# Food Rescue Platform

A **Food Rescue and Community Food Bank Platform** built with **Laravel (PHP)** supporting **SDG 2: Zero Hunger**. The platform connects food donors, beneficiaries, volunteer drivers, and administrators to reduce food waste and fight hunger.

## Team Members & Responsibilities (BMIT3173 Group 3)

| Student Name | Student ID | Assigned Module | Design Pattern | Web Service Exposed | Web Service Consumed |
|---|---|---|---|---|---|
| **Loo Zi Wei** | 2408082 | Food Request and Reservation Module | **State Pattern** | `GET /api/webservice/requests/approved` | `GET /api/webservice/donations/available` (Module 3) |
| **Loo Zhi Yin** | 2410857 | Pickup and Delivery Scheduling Module | **Observer Pattern** | `GET /api/webservice/deliveries/status` | `GET /api/webservice/requests/approved` (Module 1) |
| **Liang Yun Ci** | 2408076 | Food Donation Management Module | **Strategy Pattern** | `GET /api/webservice/donations/available` | `GET /api/webservice/users/{id}/profile` (Module 4) |
| **Syed Raiz** | 2410921 | User and Authentication Module | **Factory Pattern** | `GET /api/webservice/users/{id}/profile` | `GET /api/webservice/deliveries/status` (Module 2) |

### Architecture & Technical Stack

- **Framework:** Laravel 12 (PHP 8.2+)
- **Database:** MySQL 8 / MariaDB (Database: `food_rescue`, Port: `3306`)
- **Architecture:** Model-View-Controller (MVC) with Eloquent Object-Relational Mapping (ORM)
- **Author Headers:** Included in every source code file with student name, ID, module title, and course code.

## Design Patterns by Module

| Module | Pattern | Purpose |
|---|---|---|
| Food Request & Reservation | **State Pattern** | Manage request lifecycle: Pending → Approved → Reserved → Completed (+ Rejected, Cancelled) |
| Pickup & Delivery Scheduling | **Observer Pattern** | Notify donors, drivers, and beneficiaries on pickup/delivery status changes |
| Food Donation Management | **Strategy Pattern** | Dynamic sorting: expiry date, newest, category, location |
| User & Authentication | **Factory Pattern** | Role-specific user creation: beneficiary, donor, driver, admin |

## Project Structure

```
FoodRescuePlatformClean/
├── app/
│   ├── Http/Controllers/Api/   # API Controllers (REST & Web Services)
│   ├── Models/                 # Eloquent ORM Models (matching Analysis Class Diagram)
│   ├── Services/
│   │   ├── FoodRequest/        # Module 1 (State Pattern & WS Client)
│   │   ├── Scheduling/         # Module 2 (Observer Pattern & WS Client)
│   │   ├── Donation/           # Module 3 (Strategy Pattern & WS Client)
│   │   └── User/               # Module 4 (Factory Pattern & WS Client)
├── database/
│   ├── migrations/             # Database schema migrations
│   ├── seeders/                # Comprehensive initial data seeders
│   └── sql/                    # Submitted SQL scripts
│       ├── create_tables.sql   # MySQL DDL script
│       └── populate_data.sql   # MySQL DML initial data script
├── routes/
│   ├── api.php                 # REST API and IFA Web Service routes
│   └── web.php                 # Web interface routes
└── tests/Feature/PlatformTest.php # Comprehensive test suite (100% pass)
```

## Setup & Execution (MySQL)

### Prerequisites

- PHP 8.2+ (with `pdo_mysql` extension enabled)
- MySQL Server 8.0+ or MariaDB (e.g. XAMPP MySQL on port 3306)
- Composer 2.x

### Database Configuration

The application is pre-configured in `.env` for MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=food_rescue
DB_USERNAME=root
DB_PASSWORD=
```

### Windows Execution

1. Start your MySQL service (e.g., in XAMPP Control Panel click **Start** next to MySQL, or run `C:\xampp\mysql_start.bat`).
2. Run `setup.bat` to install dependencies, run migrations, and seed initial demo data:
   ```cmd
   setup.bat
   ```
3. Run `start.bat` to launch the platform:
   ```cmd
   start.bat
   ```
4. Visit `http://127.0.0.1:8000` in your web browser.

### Importing Submitted SQL Scripts Directly into MySQL

As required by the assignment guidelines (Section 10 & 14), standalone MySQL DDL and DML scripts are located in `database/sql/`:
```bash
# Create database and tables
mysql -u root -p -e "source database/sql/create_tables.sql"

# Populate tables with initial seed data
mysql -u root -p -e "source database/sql/populate_data.sql"
```

### Demo accounts (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@foodrescue.test | FoodBridgeDemo!2026 |
| Beneficiary | beneficiary@foodrescue.test | FoodBridgeDemo!2026 |
| Donor | donor@foodrescue.test | FoodBridgeDemo!2026 |
| Driver | driver@foodrescue.test | FoodBridgeDemo!2026 |

## API Endpoints

### Authentication (Module 4)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register with role |
| POST | `/api/login` | Login (session) |
| POST | `/api/logout` | Logout |
| GET | `/api/users/{id}` | Get user profile |
| PUT | `/api/users/{id}` | Update profile |
| GET | `/api/users/{id}/role` | Get user role |
| PUT | `/api/users/{id}/role` | Change role (admin only) |

### Donations (Module 3)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/donations` | List donations |
| GET | `/api/donations/available` | Available donations (with sorting) |
| GET | `/api/donations/{id}` | Donation details |
| POST | `/api/donations` | Create donation (donor) |
| PUT | `/api/donations/{id}` | Update donation |
| DELETE | `/api/donations/{id}` | Delete donation |
| PUT | `/api/donations/{id}/status` | Update status |

**Sort strategies:** `?sort_by=expiry_date|newest|category|location`

### Food Requests (Module 1)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/requests` | Submit food request |
| GET | `/api/requests/{id}` | Request details |
| GET | `/api/requests/history` | User's request history |
| GET | `/api/requests/approved` | Approved requests (admin/driver) |
| PUT | `/api/requests/{id}/status` | Change status (State Pattern) |
| PUT | `/api/requests/{id}/schedule` | Beneficiary chooses collection or delivery time |
| POST | `/api/reservations` | Admin-only manual reservation endpoint |
| DELETE | `/api/reservations/{id}` | Cancel reservation |

**State transitions:** `approve`, `reject`, `reserve`, `complete`, `cancel`, plus internal `release` when a reservation is cancelled.

**User workflow:** Donors bring listed food to the food bank; their private address is never requested for a donation. A beneficiary selects a specific donation, quantity, and either food bank collection or home delivery. Admin approval reserves the food and notifies the beneficiary to choose a Monday-to-Saturday time between 9:00 AM and 6:00 PM, from the next day through no later than three days or the food expiry date. Food bank collections are confirmed directly; home deliveries return to admin for assignment to a driver who has no assignment within two hours of the chosen time. Drivers can view future assignments immediately but status controls open only on the assignment date.

### Scheduling (Module 2)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/pickups` | Schedule pickup |
| GET | `/api/pickups/{id}` | Pickup details |
| PUT | `/api/pickups/{id}/status` | Update pickup status |
| POST | `/api/deliveries` | Schedule delivery |
| GET | `/api/deliveries/{id}` | Delivery details |
| PUT | `/api/deliveries/{id}/status` | Update delivery status |
| GET | `/api/driver/dashboard` | Driver dashboard |

### Inter-Module Web Services (Interface Agreement - IFA)

All endpoints adhere to the agreed Interface Agreement:
- **Request tracking:** Mandatory `requestID` query parameter for end-to-end traceability.
- **Response envelope:** Unified JSON structure containing `status` (`S` = Success, `F` = Validation Failure, `E` = Server Error), `requestID`, `timeStamp`, and payload data.
- **Bidirectional communication:** Every team member both exposes a functional service and consumes another member's service.

| Source Module | Target Module | Service Method & Endpoint | Description | IFA Request | IFA Response |
|---|---|---|---|---|---|
| **Module 3** (Liang Yun Ci) | **Module 1** (Loo Zi Wei) | `GET /api/webservice/donations/available` | Provides available surplus food donations for beneficiaries to browse | `requestID` (req), `category_id`, `sort_by` | `status`, `requestID`, `timeStamp`, `data` (donations list) |
| **Module 1** (Loo Zi Wei) | **Module 2** (Loo Zhi Yin) | `GET /api/webservice/requests/approved` | Provides approved food requests awaiting pickup & delivery scheduling | `requestID` (req), `limit` | `status`, `requestID`, `timeStamp`, `data` (approved requests) |
| **Module 2** (Loo Zhi Yin) | **Module 4** (Syed Raiz) | `GET /api/webservice/deliveries/status` | Provides real-time delivery and driver dispatch tracking for user profiles | `requestID` (req), `delivery_id`, `reservation_id` | `status`, `requestID`, `timeStamp`, `data` (delivery status) |
| **Module 4** (Syed Raiz) | **Module 3** (Liang Yun Ci) | `GET /api/webservice/users/{id}/profile` | Verifies donor registration authenticity and profile credentials | `requestID` (req), `{id}` (path) | `status`, `requestID`, `timeStamp`, `data` (verified profile) |

**Consumer Integration Test Endpoints:**
- `GET /api/webservice/requests/available-donations` — Module 1 consumes Module 3 via `DonationWebServiceClient`
- `GET /api/webservice/scheduling/approved-requests` — Module 2 consumes Module 1 via `ApprovedRequestsWebServiceClient`
- `GET /api/webservice/donations/donor-profile` — Module 3 consumes Module 4 via `UserProfileWebServiceClient`
- `GET /api/webservice/users/delivery-status` — Module 4 consumes Module 2 via `DeliveryStatusWebServiceClient`

## Entity Classes & ORM Mappings

All entity classes map 1-to-1 to the **Analysis Class Diagram**:
1. `User` (1) — `hasOne` — `BeneficiaryProfile` (0..1)
2. `User` (1) — `hasMany` — `Donation` (0..*)
3. `User` (1) — `hasMany` — `FoodRequest` (0..*)
4. `Donation` (1) — `hasMany` (`donationItems`) — `DonationItem` (1..*)
5. `Donation` (1) — `hasMany` — `Reservation` (0..*)
6. `FoodRequest` (1) — `hasOne` (`reservation`) — `Reservation` (0..1)
7. `Reservation` (0..1) — `hasOne` — `Delivery` (1)
8. `Delivery` (1) — `belongsTo` — `PickupSchedule` (1) & `DeliverySchedule` (1)
9. `PickupSchedule` (1) — `hasOne` (`location`) — `Location` (1)
10. `DeliverySchedule` (1) — `hasOne` (`location`) — `Location` (1)

## Assessed Distinct Software Security Practices

As required by the BMIT3173 guidelines, each team member implements **two unique, distinct security practices** (input validation is compulsory but not counted as either strategy):

### Module 1: Food Request & Reservation (Loo Zi Wei - 2408082)
1. **Pessimistic Row-Level Database Locking (`lockForUpdate`):** Prevents race conditions / TOCTOU over-reservation when multiple beneficiaries simultaneously request limited surplus inventory.
2. **Context-Aware Output Encoding & Escaping:** Blade `{{ }}` automatic `htmlspecialchars` escaping preventing Stored Cross-Site Scripting (XSS) in beneficiary requests and dietary notes.

### Module 2: Pickup and Delivery Scheduling (Loo Zhi Yin - 2410857)
1. **Fine-Grained Insecure Direct Object Reference (IDOR) Protection:** Enforces strict role and ownership policy checks to prevent unauthorized drivers from viewing or modifying other volunteers' delivery tasks.
2. **HTTP Security Hardening Headers Middleware (`SecurityHeadersMiddleware`):** Enforces `X-Frame-Options: SAMEORIGIN/DENY` against clickjacking, `X-Content-Type-Options: nosniff` against MIME sniffing, and strict referrer policy.

### Module 3: Food Donation Management (Liang Yun Ci - 2408076)
1. **Cross-Site Request Forgery (CSRF) Protection Middleware:** Synchronizer token validation (`@csrf`) across all donation create, edit, and status modification requests.
2. **Multi-Layer File Upload Hardening:** Strict MIME-type inspection, image dimension verification, and randomized UUID filename generation stored outside web roots to prevent unrestricted file upload and remote code execution (RCE).

### Module 4: User and Authentication (Syed Raiz - 2410921)
1. **Dynamic Rate Limiting & Throttling (`throttle:5,1`):** Applied on `/api/register` and `/api/login` with uniform error messages to thwart brute-force password guessing and credential stuffing.
2. **Session Fixation & Hijacking Defense:** Session ID regeneration (`$request->session()->regenerate()`) upon login, complete invalidation upon logout, and cookie hardening (`HttpOnly`, `SameSite=Lax`).

## Scheduled Tasks

Auto-expire donations past their expiry date:

```bash
php artisan donations:expire
```

Add to cron for daily execution:
```
0 0 * * * cd /path-to-project && php artisan donations:expire
```

## License

MIT — Built for educational purposes (SDG 2: Zero Hunger).
