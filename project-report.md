# KissanSure — Complete Project Analysis Report

## PROJECT TITLE
**KissanSure: An AI-Powered Crop Insurance Platform for Pakistani Farmers**

---

## WHAT THE PROJECT DOES

KissanSure is a full-stack web application that digitizes crop insurance for Pakistani farmers. It allows farmers to **register, browse insurance plans, apply for coverage, and file damage claims** — all online. What makes it unique is the integration of a **custom-trained AI/ML model** that analyzes uploaded crop images to determine whether the crop is damaged, eliminating the need for manual field inspection. Admins manage the entire lifecycle: farmer records, insurance plans, applications, premium calculation, and claim adjudication.

---

## PROBLEM STATEMENT

Pakistani farmers face severe financial losses due to crop damage from disease outbreaks, floods, drought, and pest attacks. Traditional crop insurance processes are paper-based, slow, and require physical farm inspections that are expensive and time-consuming. Small farmers often cannot access or afford insurance, and when they can, claims take weeks or months to process. KissanSure solves this by providing a digital, AI-assisted crop insurance platform that speeds up damage assessment and brings transparency to the claims process.

---

## TECH STACK

| Layer | Technology | Purpose |
|---|---|---|
| Backend | PHP (procedural, MySQLi) | Server-side logic, form handling, DB queries |
| Frontend | Bootstrap 5, HTML5, CSS3 | Responsive UI |
| Database | MySQL (via XAMPP) | Data persistence |
| AI/ML | Python 3, FastAPI, PyTorch | Crop damage classification microservice |
| ML Model | MobileNetV2 (pretrained, fine-tuned) | Binary image classification (damaged/healthy) |
| Image Processing | Pillow (PIL) | Image loading and transformation |
| Animation | WOW.js + Animate.css | Scroll-based UI animations |
| Carousel | OwlCarousel | Insurance plan display carousel |
| Email Service | EmailJS | Contact form email delivery |
| Session Management | PHP Sessions | Farmer and Admin authentication |
| Password Security | PHP bcrypt (password_hash) | Credential hashing |
| AJAX | Fetch API (JavaScript) | Async AI analysis calls |
| Model Serving | Uvicorn (ASGI server) | Runs the FastAPI server on port 8000 |

---

## SYSTEM ARCHITECTURE

The system follows a **3-Tier Architecture**:

**Presentation Tier (Frontend):**
HTML5, Bootstrap 5, CSS3, JavaScript, WOW.js, OwlCarousel — rendered in the browser.

**Application Tier (Backend):**
PHP handles all business logic: authentication, form processing, premium calculation, and bridging the web frontend with the AI microservice.

**Data Tier:**
MySQL database (`crop_insurance_one`) stores all farmers, plans, applications, claims, and admin accounts.

Additionally, a **separate AI Microservice** (Python FastAPI) runs independently on `http://127.0.0.1:8000`, receiving crop images from PHP via HTTP POST and returning classification results.

```
Browser (HTML/CSS/JS)
        ↓ HTTP
PHP Application Server (XAMPP)
        ↓ MySQLi         ↓ HTTP POST (JSON)
MySQL Database      FastAPI AI Server (port 8000)
                          ↓
                    PyTorch MobileNetV2 Model
```

---

## DATABASE SCHEMA (4 Core Tables)

### 1. `register_farmer` — Farmer Accounts
Stores: farmer ID, name, father's name, phone (login credential), CNIC (13 digits), address, city, field size (acres), email, bcrypt password.

### 2. `insurance_plan` — Insurance Products
Stores: plan ID, plan name, status (Active/Draft/Inactive), applicable crops (comma-separated), description, base premium rate (%), coverage level (%), deductible rate (%), coverage type (Yield Loss Only / Yield+Revenue / Yield+Revenue+Quality), unit structure (Basic/Optional/Enterprise).

### 3. `farmer_applications` — Insurance Applications
Stores: application ID, farmer ID (FK), plan ID (FK), policy duration (Kharif/Rabi), CNIC, full name, district, bank account (for payout), insured area (acres), irrigation type, crop insured, expected yield (Maunds/acre), historical yield, sowing date, certified seed flag, status, sum insured, final premium, guaranteed production, application date.

**Status Lifecycle:** `Pending Premium` → `Premium Calculated` → `Active` → `Rejected`

### 4. `insurance_claims` — Damage Claims
Stores: claim ID, application ID (FK), plan ID (FK), loss date, reason (Disease Outbreak/Flood/Drought/Pest Attack), 6 evidence image filenames (CSV), damaged area, estimated loss (PKR), AI status (Analyzed/Not Analyzed), AI result (damaged/healthy), AI confidence score (0–100%), damage percentage, damage loss (PKR), final payout (PKR), claim status (Pending/AI Analyzed/Approved/Rejected).

### 5. `users` — Admin Accounts
Stores: admin ID, username, bcrypt password, role.

---

## KEY FEATURES

### Farmer-Facing Portal (`/user/`)

| Feature | Description |
|---|---|
| **Self-Registration** | Farmers register with name, phone (11 digits), CNIC (13 digits), address, field size. Duplicate phone/CNIC detection. bcrypt password hashing. |
| **Secure Login** | Phone + password login. PHP sessions. All pages session-guarded. |
| **Insurance Plan Browser** | Public page listing all active plans with premium rate, coverage %, applicable crops, deductible. |
| **Plan Application** | 4-section form: plan selection, farmer auto-fill (read-only from DB), land details, crop details. Submitted as "Pending Premium". |
| **Application Tracker** | View all applications with colour-coded status badges and "View Invoice" button when premium is calculated. |
| **Invoice Viewer** | Printable/downloadable premium invoice showing sum insured, coverage details, final premium amount. |
| **AI-Assisted Claim Filing** | Farmer selects active policy, uploads exactly 6 crop images from different angles. SHA-256 hash checks for duplicate images client-side. AI analyzes all 6 images instantly before submission. |
| **Claim Tracker** | Cards showing claim status, AI result badge, damage %, evidence image thumbnail. |
| **AI Report Viewer** | Full damage assessment report: all 6 images, per-image classification, damage %, eligibility verdict, printable. |
| **Farmer Profile** | Profile card with photo, personal details, farm info, total application/claim count, latest AI result. |
| **Contact Page** | Form with EmailJS integration (sends real email) + WhatsApp button. Server also saves messages to DB via `contact_handler.php`. |

### Admin Panel (`/admin/`)

| Feature | Description |
|---|---|
| **Dashboard** | 4 KPI cards: Total Farmers, Total Policies, Pending Claims, Approved Claims — all live-counted from DB. |
| **Farmer Management** | CRUD: Add (manual onboarding), View (table), Update, Delete farmers. Same duplicate detection as self-registration. |
| **Plan Management** | CRUD: Create/Edit/Delete insurance plans with all parameters. Status control (Active/Draft/Inactive). |
| **Application Management** | View all applications. Approve (triggers premium calculation) or Reject. View printable invoice. |
| **Automated Premium Calculation** | On approval: Sum Insured = Area × Yield × PKR 1,500; Gross Premium = SI × Base Rate; Final Premium = Gross Premium × Risk Factor (1.0). |
| **Claims Management** | View all claims. Trigger AI analysis per claim (sends images to FastAPI). Approve or Reject after AI analysis. View full AI report. |
| **AI-Assisted Claim Review** | Admin can trigger AI on any unanalyzed claim; results stored in DB. Admin makes final approval/rejection decision. |

---

## AI / ML INTEGRATION

### How It Works

1. Farmer (or admin) uploads **6 crop images** through the web form.
2. PHP (`analyze_ai.php`) receives images via AJAX `fetch()` call.
3. For each of the 6 images, PHP sends an HTTP POST to the **FastAPI server** at `http://127.0.0.1:8000/analyze` with the image binary and crop type.
4. FastAPI passes the image to PyTorch MobileNetV2 model.
5. Model returns `{"class": "damaged"|"healthy", "confidence": float}` per image.
6. PHP aggregates: counts damaged images, calculates damage percentage.
7. Final JSON response returns to the browser showing per-image verdicts and overall verdict.
8. Results are stored in the database with the claim record.

### Model Details

| Attribute | Value |
|---|---|
| Architecture | MobileNetV2 (pretrained on ImageNet, fine-tuned) |
| Task | Binary classification: damaged / healthy |
| Input size | 224 × 224 pixels |
| Normalization | ImageNet standard (mean=[0.485,0.456,0.406], std=[0.229,0.224,0.225]) |
| Supported crops | Wheat (`wheat_classification_model.pth`), Rice (`rice_classification_model.pth`) |
| Framework | PyTorch (torch, torchvision) |
| Deployment | FastAPI + Uvicorn |
| Hardware | CPU or CUDA (auto-detected) |

### Decision Logic

| Damaged Images (out of 6) | Damage % | System Decision |
|---|---|---|
| 0–2 | 0–33% | Low/No damage — claim flagged |
| 3–4 | 50–67% | Moderate damage |
| 5–6 | 83–100% | Severe damage |

**Eligibility Threshold:** 3 or more damaged images out of 6 (≥50%) = eligible.

### Client-Side Duplicate Detection
Before sending images to AI, the browser computes **SHA-256 hash** of each image file using the Web Crypto API (`crypto.subtle.digest`). If any two images share the same hash, upload is rejected — ensuring 6 genuinely different angles.

---

## PREMIUM CALCULATION LOGIC

Triggered automatically when admin approves an application:

```
Fixed Crop Price   = PKR 1,500 per Maund

Sum Insured (SI)   = Insured Area (acres) × Expected Yield (Maunds/acre) × 1,500
Gross Premium      = SI × (Base Premium Rate / 100)
Risk Factor        = 1.0 (default)
Final Premium      = Gross Premium × Risk Factor
Guaranteed Production = Expected Yield × (Coverage Level / 100)
```

**Example:**
- Area: 5 acres, Yield: 40 Maunds/acre, Rate: 4%, Coverage: 80%
- SI = 5 × 40 × 1,500 = PKR 300,000
- Final Premium = 300,000 × 0.04 = PKR 12,000
- Guaranteed Production = 40 × 0.80 = 32 Maunds/acre

---

## FULL SYSTEM WORKFLOW

### Farmer Journey
```
Register → Login → Browse Plans → Apply for Plan → Wait for Admin
→ View Invoice (if approved) → File Claim (upload 6 images)
→ Run AI Analysis → Submit Claim → Track Claim Status → View AI Report
```

### Admin Journey
```
Login → Dashboard → Manage Plans → Manage Farmers
→ Review Applications (Approve/Reject + auto-calculate premium)
→ Review Claims (Trigger AI / Approve / Reject)
→ Print Invoice / View AI Reports
```

### Application Status Lifecycle
```
Pending Premium → Premium Calculated → Active → Rejected
```

### Claim Status Lifecycle
```
Pending → AI Analyzed → Approved / Rejected
```

---

## VALIDATION & SECURITY

| Check | Where |
|---|---|
| Phone must be 11 digits | Registration (client + server) |
| CNIC must be 13 digits | Registration (client + server) |
| Duplicate phone/CNIC detection | Registration (DB query) |
| bcrypt password hashing | Registration and login |
| Session-based route protection | Every authenticated page |
| Damaged area cannot exceed insured area | Claim form (client + server) |
| Estimated loss proportional to coverage | Claim form (client + server) |
| Max 2 claims per policy | Server-side business rule |
| No second claim if one is approved | Server-side business rule |
| Exactly 6 images required | Client + server validation |
| No duplicate images (SHA-256) | Client-side Crypto API |
| `mysqli_real_escape_string` | All DB inserts |

---

## SYSTEM ACTORS (Stakeholders)

| Actor | Role |
|---|---|
| **Farmer** | Registers, applies for insurance, files claims, views reports |
| **Admin** | Manages farmers, plans, applications, claims; triggers AI; approves/rejects |
| **AI System** | MobileNetV2 model that classifies crop damage from images |
| **EmailJS Service** | External email delivery for contact form |

---

## FUNCTIONAL REQUIREMENTS

| ID | Requirement |
|---|---|
| FR01 | System shall allow farmers to self-register with phone and CNIC |
| FR02 | System shall detect duplicate phone/CNIC at registration |
| FR03 | System shall authenticate farmers via phone + password (bcrypt) |
| FR04 | System shall allow farmers to browse all active insurance plans |
| FR05 | System shall allow authenticated farmers to apply for an insurance plan |
| FR06 | System shall auto-fill farmer details from the database on the application form |
| FR07 | System shall allow admin to approve or reject insurance applications |
| FR08 | System shall automatically calculate Sum Insured and Final Premium on approval |
| FR09 | System shall generate a printable premium invoice for approved applications |
| FR10 | System shall allow farmers to file damage claims on active policies |
| FR11 | System shall require exactly 6 crop images per claim submission |
| FR12 | System shall detect and reject duplicate uploaded images using SHA-256 hashing |
| FR13 | System shall send crop images to the AI microservice for damage classification |
| FR14 | System shall display per-image AI results and overall damage verdict |
| FR15 | System shall store AI analysis results (result, confidence, damage %) in the database |
| FR16 | System shall allow admin to manually approve or reject claims after AI analysis |
| FR17 | System shall allow admin to perform full CRUD on farmer records and insurance plans |
| FR18 | System shall display a dashboard with live KPI counts for the admin |
| FR19 | System shall generate a printable AI damage report for each claim |
| FR20 | System shall limit claims to a maximum of 2 per policy, with no reapplication after approval |

---

## NON-FUNCTIONAL REQUIREMENTS

| ID | Requirement |
|---|---|
| NFR01 | System shall be available 24/7 via web browser |
| NFR02 | All passwords shall be stored as bcrypt hashes (never plain text) |
| NFR03 | All authenticated pages shall redirect unauthenticated users to login |
| NFR04 | AI analysis shall complete within a reasonable response time (<30 seconds for 6 images) |
| NFR05 | System shall be responsive and usable on mobile, tablet, and desktop browsers |
| NFR06 | System shall support at least two crop types (Wheat, Rice) for AI classification |
| NFR07 | Uploaded evidence images shall be stored server-side with unique timestamped filenames |
| NFR08 | The system shall use Bootstrap 5 for consistent, accessible UI components |
| NFR09 | The AI microservice shall run independently and be replaceable without changing the PHP layer |
| NFR10 | All database queries shall use `mysqli_real_escape_string` to prevent SQL injection |

---

## HARDWARE REQUIREMENTS (User Side)

- Processor: Intel Core i3 or equivalent
- RAM: 4 GB or more
- Hard Disk: 10 GB free space
- Camera/Scanner: Required for uploading crop images
- Internet Connection: Required

## SOFTWARE REQUIREMENTS (User Side)

- Operating System: Windows 10 / Linux / macOS
- Browser: Google Chrome, Mozilla Firefox, Microsoft Edge (latest versions)
- No additional software installation required on user side

## SOFTWARE REQUIREMENTS (Server/Development Side)

- XAMPP (Apache + MySQL + PHP 8.x)
- Python 3.9+
- PyTorch, FastAPI, Uvicorn, Pillow, python-multipart
- VS Code or any code editor

---

## TOOLS & TECHNOLOGIES SUMMARY

**Backend:** PHP 8.x (procedural), MySQLi extension, PHP Sessions, password_hash/password_verify (bcrypt)

**Frontend:** HTML5, CSS3, Bootstrap 5.x, JavaScript (ES6+), Web Crypto API, Fetch API, Font Awesome icons, WOW.js, Animate.css, OwlCarousel, CounterUp.js, Easing.js, Waypoints.js

**Database:** MySQL 8.x (via XAMPP), database name: `crop_insurance_one`

**AI Microservice:** Python 3, FastAPI, Uvicorn, PyTorch, TorchVision, Pillow (PIL), python-multipart

**ML Architecture:** MobileNetV2 (pretrained on ImageNet, final classifier layer replaced with Linear(1280→2)), trained separately for Rice and Wheat

**Development Environment:** XAMPP (Apache + MySQL), VS Code, Python virtual environment

**External Services:** EmailJS (contact form email), WhatsApp Business API link

**Process Model:** Agile (iterative development — farmer portal, admin panel, and AI microservice developed as parallel modules)

---

## DIRECTORY STRUCTURE

```
KissanSure/
├── user/                        # Farmer-facing portal
│   ├── include/
│   │   ├── connection.php       # DB connection
│   │   ├── navbar.php           # Top navigation bar
│   │   ├── footer.php
│   │   └── carasol.php          # Carousel widget
│   ├── index.php                # Public home page
│   ├── login.php                # Farmer login (phone + password)
│   ├── logout.php
│   ├── farmer_register.php      # Farmer self-registration
│   ├── insurance_plans.php      # Browse available plans
│   ├── apply_plan.php           # Apply for a plan
│   ├── apply_claim.php          # Submit a damage claim (with AI)
│   ├── analyze_ai.php           # AJAX endpoint: send images to AI server
│   ├── ai_report.php            # View detailed AI assessment
│   ├── farmer_profile.php       # Farmer profile & stats
│   ├── view_application_plan.php
│   ├── view_application_claim.php
│   ├── view_application_invoice.php
│   └── contact.php
│
├── admin/                       # Admin dashboard
│   ├── include/
│   │   ├── connection.php
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   ├── images/                  # Uploaded claim evidence images
│   ├── login.php
│   ├── logout.php
│   ├── signup.php
│   ├── dashboard.php
│   ├── add_register.php / view_register.php / update_register.php / delete_register.php
│   ├── add_plan.php / view_plan.php / update_plan.php / delete_plan.php
│   ├── add_insurance_application.php / view_insurance_application.php
│   ├── approve_application.php / reject_application.php
│   ├── add_farmer_invoice.php
│   ├── add_file_claim.php / view_claims.php
│   ├── analyze_ai.php
│   ├── approve_claim.php / reject_claim.php
│   └── ai_report.php
│
└── ai_model/                    # Python ML microservice
    ├── main.py                  # FastAPI server (routes)
    ├── predict.py               # Model loading & inference
    ├── requirements.txt
    ├── rice_classification_model.pth
    ├── wheat_classification_model.pth
    └── rice_segmentation_model.pth  (disabled/unused)
```

---

## KEY BUSINESS RULES & CONSTANTS

| Constant | Value |
|---|---|
| Database Name | `crop_insurance_one` |
| AI Server URL | `http://127.0.0.1:8000/analyze` |
| Crop Price (per Maund) | PKR 1,500 |
| AI Eligibility Threshold | 3 of 6 images damaged (≥50%) |
| Risk Factor | 1.0 (default) |
| Image Upload Path | `admin/images/` |
| Image Naming Convention | `{timestamp}_{1-6}.{ext}` |
| Evidence Images per Claim | Exactly 6 |
| Supported Crops (AI) | Wheat, Rice |
| Supported Crops (Plans) | Wheat, Rice, Cotton, Sugarcane, Maize |
| Insurance Seasons | Kharif (summer/monsoon), Rabi (winter) |
| Damage Reasons | Disease Outbreak, Flood, Drought, Pest Attack |
| Coverage Types | Yield Loss Only / Yield+Revenue / Yield+Revenue+Quality |
| Irrigation Types | Canal/River, Tube-well, Rainfed (Barani) |
| Max Claims per Policy | 2 |

---

## TEAM

| Name | Role |
|---|---|
| Dr. Mushhad Mustuzhar Gilani | Supervisor |
| Izzah Khursheed | AI Engineer |
| Ushba Zahid | Website Developer |

---

## FYP REPORT MAPPING (UAF Guidelines)

This project falls under the combined category:
- **Applications & Web/Mobile Development** (Ecommerce/Web platform)
- **AI & Data-Driven Projects** (AI-powered chatbot / ML system)

### Required Diagrams Checklist (Applications & Web/Mobile)
- [ ] Use Case Diagram — Required
- [ ] ERD (Entity Relationship Diagram) — Required
- [ ] Data Dictionary — Required
- [ ] Data Flow Diagram (Level 0 and Level 1) — Required
- [ ] Architecture Diagram — Required (3-Tier)
- [ ] Class Diagram — Required
- [ ] Sequence Diagram — Required
- [ ] Activity Diagram — Required
- [ ] Component Diagram — Required
- [ ] Deployment Diagram — Required
- [ ] Database Architecture — Required
- [ ] Testing (Black-box + some white-box) — Required

---

*Generated: 2026-05-17 | Project: KissanSure FYP 2026 | University of Agriculture, Faisalabad*
