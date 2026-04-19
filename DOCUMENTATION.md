# SharpCuts — System Documentation  
### A thesis-oriented technical description of the web application

---

## Abstract

This document presents a structured description of **SharpCuts**, a barbershop management and customer-facing web system developed using **PHP**, **MySQL**, and conventional **HTML/CSS/JavaScript**. Its purpose is to serve both **practitioners** (developers, system administrators, and students documenting a capstone or thesis project) and **readers who require conceptual clarity** alongside operational detail.

The documentation explains the **business intent** of the software, the **technical architecture** in which it operates, the **configuration** of local and remote environments, the **continuous integration and deployment** pipeline, the **functional modules** corresponding to each class of user, the **authentication and authorization** design, **validation rules**, and the role of **application programming interfaces (APIs)** in connecting the browser to server-side logic. Cross-references are provided to companion materials—[SETUP_GUIDE.md](SETUP_GUIDE.md), [RBAC_GUIDE.md](RBAC_GUIDE.md), and others—where procedural depth would otherwise overwhelm this narrative.

---

## 1. Product overview

This chapter establishes *what* SharpCuts is, *for whom* it is built, and *why* it matters from both a business and a systems perspective.

### 1.1 Goal and purpose

SharpCuts is a **web application** intended to represent and support the operations of a **premium barbershop**. According to the project’s public-facing copy, the business is situated conceptually in **Pitogo, Zamboanga del Sur**, Philippines. The software therefore functions not merely as a static brochure but as a **lightweight operational system**: it exposes the shop’s brand and services to the public, allows **registered customers** to reserve time with a chosen barber and service, and provides **administrative staff** with tools to maintain master data (barbers, services, schedules) and to oversee the appointment ledger.

In academic terms, the system addresses a **coordination problem** between supply (barber time) and demand (customer appointments) while reducing friction compared to informal channels such as unstructured phone calls or walk-in-only policies.

### 1.2 Target audience

The following table summarizes stakeholder groups. Each row should be read as a *persona* whose goals the documentation later maps to concrete pages and modules.

| Audience | Needs met by the product | Notes for thesis readers |
|----------|---------------------------|---------------------------|
| **Customers** | Discover services and staff, create an account, authenticate, reserve a slot, review upcoming visits, and cancel bookings that are still eligible. | Primary beneficiaries of the customer-facing “front office” of the system. |
| **Shop staff / owner (administrators)** | Observe aggregate and pending workload, curate barber profiles (including imagery), define services and pricing, perform full **create, read, update, delete (CRUD)** operations on appointments, and manage user accounts including **roles**. | Maps to the `admin/` area and privileged APIs. |
| **Barbers (as user accounts)** | The **database** and **role-based access control (RBAC)** layer recognize a `barber` role; administrators can assign it via user management. | The **current** PHP user interface does **not** expose a dedicated barber-only workspace; this limitation is analyzed in **Section 7.3** so that a thesis may honestly describe “implemented scope” versus “intended future scope.” |

### 1.3 Value proposition

The value of the system can be articulated along four dimensions, each of which could be expanded in a thesis discussion chapter:

1. **Convenience** — Customers may initiate a booking through a web browser rather than depending solely on synchronous communication with staff.
2. **Clarity** — Services, Philippine peso (**₱**) prices, estimated durations, and barber biographies are consolidated in structured pages, which reduces ambiguity prior to the visit.
3. **Operational efficiency** — A single relational database holds appointments and catalog data, which supports reporting-style views (counts, filters) in the admin dashboard.
4. **Trust and fairness** — Booking logic consults **weekly availability** and attempts to prevent **double booking** of the same barber at the same date and time, which communicates predictable rules to the customer and protects staff from scheduling conflicts.

---

## 2. Technical stack

This chapter defines *how* the application is constructed at each layer of a simplified three-tier model: **presentation**, **application logic**, and **persistence**.

### 2.1 Server-side runtime

The application executes on a **web server** that interprets **PHP** scripts. In a typical student or small-business laboratory environment, **XAMPP** bundles **Apache** (the HTTP server), **PHP**, and **MySQL** so that a single workstation can mimic production conditions.

PHP maintains **server-side session state** using the `$_SESSION` superglobal after `session_start()` is invoked. Sessions are essential for **authentication**: once a user proves identity via login, the server remembers that fact across subsequent HTTP requests by associating a **session identifier** (usually stored in a browser cookie) with server-side session data.

**MySQLi** (“MySQL improved”) is the PHP extension used throughout this project to connect to **MySQL** or **MariaDB**. It provides functions and object methods for executing SQL, handling results, and—where used—**prepared statements**, which separate SQL structure from user-supplied values and thereby mitigate **SQL injection** attacks.

### 2.2 Database

**MySQL** (or a compatible fork such as **MariaDB**) stores all durable entities: users, barbers, services, weekly availability windows, and appointments. The choice of a **relational** database reflects the need for **referential relationships** (for example, an appointment row referencing both a barber and a service) and **integrity constraints** such as unique keys on desirable slot combinations.

### 2.3 Client-side presentation

The **frontend** in this project is not a compiled single-page application (SPA) such as those built with React or Vue. Instead, **PHP files emit HTML** at request time. **Cascading Style Sheets (CSS)** files—principally `style.css` for the public site and `admin/styles.css` for the administrative shell—control typography, color, layout, and responsive behavior. **JavaScript** enhances interactivity without replacing server rendering: `main.js` powers navigation affordances (for example, a mobile “hamburger” menu), scroll-driven effects, and carousels; administrative screens additionally load `admin/js/admin.js` where forms and tables interact with backend scripts.

Because there is **no mandatory build step** (no Webpack, Vite, or similar bundler required for the core application), the project remains approachable for readers whose thesis emphasis is on **information systems** rather than on modern front-end toolchain complexity.

### 2.4 Hosting and automation

Deployment automation is introduced through **GitHub Actions**, described fully in **Chapter 6**. In brief, **Continuous Integration / Continuous Deployment (CI/CD)** refers to practices in which code changes trigger automated workflows—here, an **FTP-based file upload** to a shared host—so that human error in manual copying is reduced.

---

## 3. Repository layout (high level)

This chapter maps the **filesystem** to **responsibilities**. Understanding this mapping is prerequisite to maintenance, extension, and thesis chapters on “system design.”

| Path | Role | Expanded explanation |
|------|------|----------------------|
| `config.php` | Database bootstrap | Establishes `$conn`, a MySQLi connection object. Credentials may be supplied through **environment variables** (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`), which is the preferred pattern on shared hosts where secrets should not live in version control. |
| `functions.php` | Cross-cutting application logic | Starts the session when needed, includes `config.php`, defines authentication and booking functions, ensures a minimal `users` table exists, and handles a global **POST logout** for non-API scripts. Most pages `require_once` this file as their entry bootstrap. |
| `index.php`, `about.php`, `services.php`, `ourBarbers.php`, `contact.php` | Public marketing and catalog | These endpoints are reachable without authentication and perform primarily **read** operations against barbers and services tables for display. |
| `login.php`, `register.php` | Authentication user interface | Collect credentials or registration fields and delegate validation and persistence to functions in `functions.php` (or, for API consumers, to `api/auth.php`). |
| `book.php` | Authenticated booking workflow | Implements server-side checks before inserting an appointment, complementing any client-side UX. |
| `dashboard.php` | Customer appointment summary | Restricted to logged-in users who are **not** administrators (admins are redirected to the admin dashboard). |
| `api/` | JSON-oriented backend endpoints | Explained at length in **Chapter 10**; these scripts return **JavaScript Object Notation (JSON)**, a lightweight text format that JavaScript in the browser can parse natively. |
| `admin/` | Administrative shell | PHP pages and assets forming the back office; `admin/config.php` reuses the root database configuration to avoid duplication. |
| `setup_database.php` | Schema and seed initializer | Intended for controlled execution during installation; creates tables and demonstration records. |
| `MIGRATION_RBAC.sql` | Optional SQL migration | Supplies additional **RBAC** seed users; documented in [RBAC_GUIDE.md](RBAC_GUIDE.md). |
| `assets/`, `uploads/barbers/` | Static and user-generated media | Icons, illustrations, and uploaded barber photographs respectively. |
| `.github/workflows/deploy.yml` | CI/CD workflow definition | Declarative YAML consumed by GitHub Actions; see **Chapter 6**. |

---

## 4. Frontend setup

This chapter clarifies how the **user interface** is produced, delivered, and cached.

### 4.1 Meaning of “frontend” in this project

In software engineering discourse, **frontend** usually denotes everything that executes in the user’s browser or that directly shapes what the browser renders. In SharpCuts, the boundary is blended: **PHP runs on the server** and outputs HTML, so part of the “presentation logic” lives server-side. Still, **CSS** and **client-side JavaScript** constitute the classic frontend technologies applied *after* the document is sent.

There is **no separate single-page application** and **no mandatory bundling pipeline**. That design choice trades cutting-edge front-end modularity for **simplicity, traceability, and ease of deployment** on inexpensive PHP hosting—an acceptable and often rational constraint for a thesis prototype or a small local business.

### 4.2 Assets and typography

Most screens load **Google Fonts** (specifically **DM Sans**) to unify typography with contemporary web aesthetics. Global layout and component styling for the public experience live in **`style.css`**. The administrative area uses **`admin/styles.css`** to present a distinct visual shell (sidebar, cards, tables) appropriate for staff workflows.

### 4.3 Local URLs under XAMPP

When the project directory is placed at `C:\xampp\htdocs\SharpCuts\`, Apache typically maps that folder to the URL prefix `/SharpCuts/`. Therefore:

- The marketing home page is reached at `http://localhost/SharpCuts/` (often implemented as `index.php` by default directory index rules).
- The administrative home is reached at `http://localhost/SharpCuts/admin/dashboard.php`.

These URLs assume default Apache listening on port **80**; if port remapping is used, the prefix must be adjusted accordingly.

### 4.4 Cache busting for stylesheets

Browsers aggressively **cache** static files to improve performance. When a stylesheet is updated but its URL remains identical, users may continue to see stale rules. SharpCuts mitigates this by appending a **query string version** (for example, `style.css?v=1.0.1`). Incrementing that version after substantive CSS edits **invalidates** naive cache keys and encourages the browser to refetch the file.

---

## 5. Backend and server setup

This chapter explains how the **server environment**, **database connection**, and **session lifecycle** cooperate to deliver dynamic pages.

### 5.1 Software requirements

The following minimums should be stated explicitly in any thesis methodology section on environment:

- **PHP 7.4 or newer** (PHP 8.x is preferable for performance and language features). The **mysqli** extension must be enabled so that database calls succeed. **Session** support must likewise be available.
- **MySQL 5.7+** or **MariaDB 10.3+**, compatible with the SQL dialect used in setup scripts.
- **Apache** (or a compatible HTTP server) configured to execute PHP for `.php` resources.

### 5.2 Database configuration and environment variables

The file [config.php](config.php) instantiates a MySQLi connection and assigns it to `$conn`. Default literals target a typical XAMPP installation (`localhost`, user `root`, empty password, database name `sharpcuts_db`). For **staging** or **production**, the same file reads **environment variables**—external named values injected by the operating system or hosting panel—so that credentials need not be edited in source code for each machine.

If the connection fails, the script responds with HTTP status code **500** (“Internal Server Error”) and terminates with a short message. From a thesis standpoint, this behavior should be discussed under **reliability** and **operator experience**: a generic message avoids leaking secrets but may complicate debugging unless server logs are consulted.

### 5.3 Initializing the relational schema

Relational structure—tables, keys, and seed rows—is created through **`setup_database.php`**, which should be executed in a browser once the Apache and MySQL services are running. Detailed expected output and troubleshooting appear in [SETUP_GUIDE.md](SETUP_GUIDE.md).

Separately, **`functions.php`** invokes `createUsersTable()` on every include to guarantee that a minimal **`users`** table exists. This defensive pattern supports incremental development but does **not** replace the full schema creation performed by `setup_database.php`; booking still requires barbers, services, availability, and appointments tables.

### 5.4 Session bootstrap pattern

Most PHP scripts begin with:

```php
require_once 'functions.php';   // or '../functions.php' from nested folders
```

That single include performs three conceptual duties: it ensures a **session** is started when appropriate; it loads **database connectivity**; and it defines **pure PHP functions** that encapsulate recurring business rules (registration, login, booking). This pattern exemplifies **procedural reuse** rather than object-oriented frameworks such as Laravel; both styles are academically legitimate to describe.

### 5.5 Logout semantics

Logout must destroy **server-side session state** and, when cookies are in use, expire the **session cookie** so the browser does not present a stale identifier. SharpCuts implements logout through:

- A **global POST handler** in `functions.php` for ordinary pages posting `action=logout`, redirecting afterward to `index.php`.
- An **`api/auth.php`** branch that returns JSON for programmatic clients.

Thesis writers may contrast these paths under a heading on **multiple client types** (traditional form posts versus **asynchronous JavaScript** requests).

---

## 6. CI/CD and deployment (`deploy.yml`)

This chapter introduces **automation**, **secrets management**, and the limits of **file-based deployment**.

### 6.1 What CI/CD means in this repository

**Continuous Integration (CI)** generally means that each change to the codebase is automatically built and tested in a clean environment. **Continuous Deployment (CD)** extends that idea by automatically releasing passing artifacts to a server. In SharpCuts, the workflow file [.github/workflows/deploy.yml](.github/workflows/deploy.yml) implements a **practical subset** of CD: on each push to the **`main`** branch, GitHub Actions checks out the repository and uploads files via **FTP** (File Transfer Protocol).

FTP is an older protocol but remains common on **shared PHP hosts** (the workflow name references **InfinityFree** as an example class of provider). A thesis should acknowledge both the **accessibility** of FTP hosting for student budgets and the **security trade-offs** of FTP versus **SFTP** or **SSH-based** deployment.

### 6.2 Trigger condition

The workflow is configured to run **only** when commits are pushed to **`main`**. Other branches do not trigger deployment by default, which protects experimental work from accidentally overwriting production files.

### 6.3 Steps performed by the workflow

1. **Checkout** — The GitHub-hosted runner downloads the repository snapshot associated with the triggering commit.
2. **FTP deploy** — The action [SamKirkland/FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action) synchronizes the local working tree to the remote `server-dir`.

### 6.4 GitHub Secrets

**Secrets** are encrypted key–value pairs stored in GitHub repository settings. They are injected into the workflow at runtime but are **not** printed in logs. This project expects:

| Secret | Purpose |
|--------|---------|
| `FTP_SERVER` | Hostname of the FTP endpoint supplied by the hosting provider. |
| `FTP_USERNAME` | Account name authorized to write beneath the web root. |
| `FTP_PASSWORD` | Authentication secret; must never appear in YAML in plain text. |

### 6.5 Paths and exclusions

The workflow uploads from **`./`** (repository root) into **`/htdocs/`** on the remote system, which aligns with common shared-hosting layouts. Certain paths are **excluded** from synchronization—`.git` metadata, `node_modules`, editor folders, and optional `database/**`—to reduce upload time and avoid publishing irrelevant or sensitive development artifacts.

### 6.6 Operational caveats (important for academic honesty)

- **FTP deploys files, not databases.** MySQL schema and data must be migrated through separate procedures (phpMyAdmin import, remote execution of setup scripts, or manual SQL).
- **Environment parity** — PHP version and extensions on the host should mirror local assumptions.
- **`config.php` on the server** should rely on **`DB_*` environment variables** or host-specific configuration rather than checked-in defaults whenever possible.

---

## 7. Dashboards and modules

This chapter corresponds to the **functional requirements** traceability that many theses require: each module is tied to files and behaviors.

### 7.1 Administrative dashboard (`admin/`)

**Access control** is enforced at the top of each administrative PHP page: the script includes `functions.php`, then evaluates `isAdmin()`. If the current session does not represent an administrator, the user is redirected to the public **`login.php`**. This is an example of **discretionary access control** embedded in application code rather than declared solely in the database.

| Module | File | Purpose (narrative) |
|--------|------|---------------------|
| **Overview** | `admin/dashboard.php` | Presents **key performance indicators** at a glance—counts of appointments by coarse status and a preview list of upcoming reservations—so that staff can orient themselves before drilling into detail screens. |
| **Appointments** | `admin/appointments.php` | Provides filtering, tabular inspection, and modal or scripted interactions that call **`api/appointments.php`** to create, modify, or remove rows in the `appointments` table. |
| **Barbers** | `admin/barbers.php` | Maintains the public roster: names, titles, imagery, biographical text, and **availability templates** per weekday. Backed by **`api/barbers.php`**. |
| **Services** | `admin/services.php` | Defines the service catalog—descriptions, monetary price, duration—which feeds both marketing pages and booking pick lists. Backed by **`api/services.php`**. |
| **Users (RBAC)** | `admin/users.php` | Administrative user lifecycle: search, pagination, filters by role, creation, updates, and deletion through **`api/users.php`**, which itself refuses non-admin callers with HTTP **403 Forbidden**. Some admin sidebars may not link to this page; direct navigation to `admin/users.php` is still valid. |

Administrative styling is centralized in **`admin/styles.css`**; shared behaviors live in **`admin/js/admin.js`** where referenced.

### 7.2 Customer dashboard (`dashboard.php`)

After authentication, **non-administrator** users land on **`dashboard.php`**. The page executes a SQL query joining **`appointments`**, **`barbers`**, and **`services`**, filtered so that **`customer_email`** equals the logged-in user’s email address. Thus the dashboard answers the question, “What have I reserved, with whom, for which service, and in what state?”

The interface currently labels the user as **“Customer”** in the header regardless of underlying `role`, which is accurate for the primary persona but potentially misleading for **`barber`** accounts (see next subsection). Cancellation of a pending appointment is initiated in JavaScript through **`fetch()`** toward **`api/appointments.php`** with action **`delete-appointment`**; thesis discussions of **semantic HTTP verbs** may note that “delete” is used here even when the business operation is conceptually a **cancellation**—a naming issue worth documenting rather than hiding.

Sidebar navigation includes a link to **`services.php`**, which is primarily a **marketing catalog**; the actual **booking** action remains associated with **`book.php`** in the global navigation when authenticated.

### 7.3 Barber role versus a dedicated barber dashboard

**Role-based access control** assigns each `users` row a **`role`** drawn from the enumeration **`customer`**, **`barber`**, **`admin`**. Administrators may promote accounts to **`barber`** using **`admin/users.php`**. The companion document [RBAC_GUIDE.md](RBAC_GUIDE.md) articulates a **vision** in which barbers manage their own availability and appointments.

**As implemented in the PHP pages reviewed for this documentation**, there is **no** separate route such as `barber/dashboard.php`, nor middleware that routes `role === 'barber'` to specialized views. Non-admin users—including barbers—are sent to **`dashboard.php`**, which filters appointments by **customer email**, not by **`barber_id`**. Consequently, unless a barber’s login email coincidentally matches how appointments were stored (which would be an accidental alignment, not a designed join), **barber accounts do not automatically see their professional schedule**.

A rigorous thesis should present this as a **known limitation** or as **future work**, and may propose a schema extension (for example, a `barber_user_id` foreign key linking staff logins to `barbers.id`) plus filtered queries and a dedicated UI.

---

## 8. Authentication setup

This chapter explains **identity**, **proof of identity**, and **authorization** primitives used by SharpCuts.

### 8.1 Distinction between authentication and authorization

**Authentication** answers the question, “Who is this user?” It is satisfied after a successful login when the server stores identifiers in the session. **Authorization** answers, “What may this user do?” SharpCuts implements a coarse authorization layer through the **`role`** field (`isAdmin()` is true only when the role equals **`admin`**).

### 8.2 Session variables after login

Upon success, **`loginUser()`** in [functions.php](functions.php) populates, among others:

- `user_id` — Surrogate primary key from the `users` table.  
- `username` and `email` — Human-readable identifiers.  
- `full_name` — Display name, possibly empty for some accounts.  
- `role` — Drives authorization branching.  
- `logged_in` — Boolean convenience flag tested by **`isLoggedIn()`**.

### 8.3 Helper functions (conceptual definitions)

| Function | Behavior explained in prose |
|----------|-----------------------------|
| `isLoggedIn()` | Returns true when the session asserts an authenticated principal via the `logged_in` flag. |
| `isAdmin()` | Conjoins authentication with **role equality** against `admin`, yielding a predicate used to guard administrative routes and APIs. |
| `getCurrentUser()` | Projects session fields into an associative array suitable for templating, or returns **`null`** if no session user exists. |

### 8.4 Entry points for authentication flows

| Flow | Mechanism |
|------|-----------|
| **Traditional form login** | `login.php` accepts POSTed email and password, invokes **`loginUser()`**, then performs an HTTP redirect: administrators to **`admin/dashboard.php`**, all other roles to **`dashboard.php`**. |
| **Traditional form registration** | `register.php` collects credentials, calls **`registerUser()`**, which defaults new accounts to **`customer`**. |
| **API-driven authentication** | **`api/auth.php`** exposes JSON actions such as **`register`**, **`login`**, **`logout`**, **`check-auth`**, **`check-username`**, and **`check-email`**, enabling asynchronous validation from JavaScript without a full page reload. |

### 8.5 Password storage and verification

Passwords are never stored reversibly. **`password_hash()`** with the **`PASSWORD_BCRYPT`** algorithm derives a salted one-way **hash** stored in `password_hash`. **`password_verify()`** recomputes the comparison at login time. This design follows contemporary **OWASP-aligned** guidance for password databases.

---

## 9. Validation and data rules

This chapter enumerates **business rules** and connects them to **security** and **data quality**.

### 9.1 Registration (`registerUser`)

Usernames must contain at least **three** characters to avoid trivially ambiguous handles. Email addresses must satisfy PHP’s **`FILTER_VALIDATE_EMAIL`** predicate, which approximates Internet email syntax rules. Passwords must meet a minimum length of **six** characters (a baseline that a production thesis might critique as short compared to modern **NIST**-influenced policies). The system rejects duplicates on **username** or **email**. Optional full names are escaped before inclusion in SQL strings.

### 9.2 Login (`loginUser`)

Email format is validated again. Only users marked **`is_active = TRUE`** may authenticate, supporting **account suspension** without row deletion.

### 9.3 Booking (`book.php` and `bookAppointment`)

All human-entered dimensions of a reservation—name, telephone, email, barber identifier, service identifier, calendar date, and clock time—are treated as mandatory. **Availability** requires a matching **`barber_availability`** row for the computed weekday with **`is_available` true**. **Double booking** is prevented by checking for existing appointments sharing barber, date, and time; `book.php` further narrows conflicting rows to statuses **`pending`** or **`confirmed`**, which reflects the idea that **cancelled** or **completed** historical rows should not block new commerce indefinitely.

### 9.4 Administrative user API (`api/users.php`)

Every action is prefaced by an **`isAdmin()`** guard. Failure yields HTTP **403** with JSON, which is the conventional response for **authenticated but insufficient privilege**. Listing code paths demonstrate **prepared statements**, **pagination**, and optional textual **search**—patterns that should be highlighted positively in a thesis security discussion.

### 9.5 Security posture (balanced assessment)

Not every query in the codebase uses prepared statements; some rely on **`real_escape_string`** or integer casting. A thesis **limitations** section should cite this mixed maturity and reference hardening guidance in [SETUP_GUIDE.md](SETUP_GUIDE.md). Transport security (**HTTPS**) and hardened **session cookie flags** (`Secure`, `HttpOnly`) should be mandated in any public deployment narrative.

---

## 10. Application Programming Interface (API) layer

This chapter defines **APIs** in general and SharpCuts’ concrete endpoints in particular—suitable for copying or adapting into a thesis **“System APIs”** subsection.

### 10.1 Definition: what an API is

An **Application Programming Interface (API)** is a **contract** through which one software component requests services from another. On the web, **HTTP APIs** are ubiquitous: the browser (or a mobile app) sends an **HTTP request**—often **POST** for mutations or **GET** for reads—to a URL; the server executes PHP logic; and the response body typically contains **JSON**.

**JSON (JavaScript Object Notation)** is a text serialization format built from objects (`{ "key": "value" }`) and arrays (`[ ... ]`) that both humans and machines can read. Because JavaScript includes `JSON.parse()`, it is especially convenient for **AJAX** or **`fetch()`** programming patterns where a page updates partially without reload.

SharpCuts APIs generally set the response header **`Content-Type: application/json`** and **`echo` a JSON-encoded array or object**, often including at least **`success`** (boolean) and **`message`** (human-readable explanation) keys—an informal convention that simplifies client-side branching.

### 10.2 Why APIs exist alongside traditional PHP pages

Traditional PHP forms perform **full round trips**: the browser submits, the server renders a new HTML document. APIs instead return **structured data**, enabling **administrative grids** and **JavaScript-driven booking aids** to refresh only the portions of the interface that changed. This separation also foreshadows how the same backend could someday serve a **mobile application** reusing identical endpoints.

### 10.3 Base URL and local examples

Relative to the web root, endpoints live under **`/api/`**. Under XAMPP, a representative base is:

`http://localhost/SharpCuts/api/`

### 10.4 Endpoint-by-endpoint summary

| Endpoint | Authentication expectations | Representative actions | Thesis-level commentary |
|----------|-----------------------------|------------------------|-------------------------|
| **`api/auth.php`** | Mixed | `register`, `login`, `logout`, `check-auth`, `check-username`, `check-email` | Centralizes **identity** operations for JSON clients; supports both JSON bodies and form-encoded posts by merging `php://input` into `$_POST`. |
| **`api/appointments.php`** | **Not uniformly session-checked inside the file** | `create-appointment`, `update-appointment`, `delete-appointment` | Relies on **caller discipline** today; a hardening recommendation is to enforce **`isAdmin()`** (or appropriate barber/customer rules) inside the endpoint to prevent **unauthorized tampering** if URLs are discovered. |
| **`api/barbers.php`** | Included via admin bootstrap in many paths | Barber CRUD, availability queries, uploads | Bridges **`barbers`** master data with administrative UI complexity. |
| **`api/services.php`** | Similar | Service CRUD | Maintains catalog integrity feeding both marketing and booking. |
| **`api/users.php`** | **Administrator session required** | `get-users`, `create-user`, `update-user`, `delete-user`, `get-user` | Demonstrates **defense in depth** for sensitive user administration. |
| **`api/get-data.php`** | Read-oriented, broadly accessible patterns | `get-appointments`, `get-barbers`, `get-services`, `get-available-slots` | Supports **slot generation** logic (for example, 30-minute increments) based on availability minus existing bookings. |

### 10.5 Integration pattern

Many administrative JavaScript snippets issue **`fetch()`** calls with header **`Content-Type: application/json`** and a JSON body. Server scripts often execute:

```php
$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;
```

which **unifies** form posts and JSON into one parsing path—a pragmatic pattern that should be documented so future maintainers do not assume only one content type is possible.

---

## 11. Data model (conceptual)

This chapter supports **entity–relationship** discussion without duplicating full DDL (which remains authoritative in `setup_database.php` and [SETUP_GUIDE.md](SETUP_GUIDE.md)).

- **`users`** — Stores authentication credentials, profile fields, **`role`**, and **`is_active`**. Roles implement **RBAC** at a coarse granularity.  
- **`barbers`** — Public-facing professional profiles distinct from login rows.  
- **`barber_availability`** — Encodes recurring weekly windows when each barber accepts bookings.  
- **`services`** — Sellable units with monetary **`price`** and **`duration_minutes`**.  
- **`appointments`** — Fact table linking customers (denormalized name/phone/email), a **`barber_id`**, a **`service_id`**, temporal columns, **`status`**, and optional **`notes`**.

**Important conceptual gap:** the default setup does **not** declare a foreign key between **`users.id`** and **`barbers.id`**. Therefore **staff user accounts** and **directory barber rows** are not automatically coupled—a point that matters when designing future barber dashboards.

---

## 12. Related documents

The following companion files provide **procedural** or **role-specific** depth. This thesis-style document intentionally stays narrative; readers performing installation should interleave chapters here with the checklists below.

| Document | When to consult it |
|----------|--------------------|
| [SETUP_GUIDE.md](SETUP_GUIDE.md) | Step-by-step installation, database creation alternatives, troubleshooting, and architecture diagrams. |
| [RBAC_GUIDE.md](RBAC_GUIDE.md) | Extended explanation of roles, migration SQL, and intended barber capabilities. |
| [RBAC_QUICK_REFERENCE.md](RBAC_QUICK_REFERENCE.md) | Condensed RBAC reminders. |
| [QUICK_REFERENCE.md](QUICK_REFERENCE.md) | Short project facts if maintained in the repository. |
| [LOGOUT_TESTING_GUIDE.md](LOGOUT_TESTING_GUIDE.md) | Quality assurance scenarios for session teardown. |

---

## 13. Document maintenance and versioning

Software evolves; documentation must evolve with it. The present file should be revised when:

- The **deployment target**, **branch strategy**, or **secrets** change (**Chapter 6**).  
- A **dedicated barber dashboard** or **`users`–`barbers` linkage** is implemented (**Chapter 7.3** and **Chapter 11**).  
- **API authentication** is centralized or strengthened (**Chapter 10.4**).

Each thesis submission should record the **commit hash** or **release tag** of the codebase to which this documentation corresponds, so that examiners may reproduce findings.

---

*This documentation reflects the SharpCuts repository structure and observed behavior at the time of writing. Operators must independently verify credentials, host policies, and legal requirements (including data privacy law) before any production deployment.*
