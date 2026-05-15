# KissanSure — Crop Insurance Platform: Complete Project Flow

## Table of Contents
1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Directory Structure](#3-directory-structure)
4. [Database Schema](#4-database-schema)
5. [Authentication System](#5-authentication-system)
6. [Farmer (User) Flow](#6-farmer-user-flow)
7. [Admin Flow](#7-admin-flow)
8. [AI/ML Integration Flow](#8-aiml-integration-flow)
9. [Premium Calculation Logic](#9-premium-calculation-logic)
10. [Full End-to-End Journey](#10-full-end-to-end-journey)
11. [API Endpoints Reference](#11-api-endpoints-reference)
12. [Key Constants & Business Rules](#12-key-constants--business-rules)

---

## 1. Project Overview

**KissanSure** is an AI-powered crop insurance platform for Pakistani farmers.  
- Farmers apply for insurance plans, file damage claims, and get AI-based assessments.  
- Admins manage farmers, insurance plans, applications, claims, and premium calculations.  
- An embedded Python (FastAPI) ML server classifies crop images as **damaged** or **healthy** using MobileNetV2.

**Database:** `crop_insurance_one` (MySQL via XAMPP, localhost, root, no password)  
**AI Server:** Python FastAPI on `http://127.0.0.1:8000`

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP (procedural, MySQLi) |
| Frontend | Bootstrap 5, HTML5, custom CSS |
| Database | MySQL (via XAMPP) |
| AI Inference | Python 3, FastAPI, PyTorch (MobileNetV2) |
| Animation | WOW.js + Animate.css |
| Carousel | OwlCarousel |

---

## 3. Directory Structure

```
FYP_2026/
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
│   ├── analyze_ai.php           # AJAX endpoint: send images → AI server
│   ├── ai_report.php            # View detailed AI assessment
│   ├── farmer_profile.php       # Farmer profile & stats
│   ├── view_application_plan.php    # View submitted applications
│   ├── view_application_claim.php   # View submitted claims
│   ├── view_application_invoice.php # View premium invoice/quote
│   └── contact.php              # Contact page
│
├── admin/                       # Admin dashboard
│   ├── include/
│   │   ├── connection.php
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   ├── images/                  # Uploaded claim evidence images (stored here)
│   ├── login.php                # Admin login (username + password)
│   ├── logout.php
│   ├── signup.php               # Admin account creation
│   ├── dashboard.php            # Stats overview
│   ├── add_register.php         # Manually add farmer
│   ├── view_register.php        # List all farmers
│   ├── update_register.php      # Edit farmer record
│   ├── delete_register.php      # Delete farmer
│   ├── add_plan.php             # Create insurance plan
│   ├── view_plan.php            # List all plans
│   ├── update_plan.php          # Edit plan
│   ├── delete_plan.php          # Delete plan
│   ├── add_insurance_application.php  # Create application on farmer's behalf
│   ├── view_insurance_application.php # View all applications
│   ├── approve_application.php  # Approve + calculate premium
│   ├── reject_application.php   # Reject application
│   ├── add_farmer_invoice.php   # View/print premium invoice
│   ├── add_file_claim.php       # Admin-side claim filing
│   ├── view_claims.php          # View all claims
│   ├── analyze_ai.php           # AJAX endpoint: AI analysis (admin)
│   ├── approve_claim.php        # Approve a claim
│   ├── reject_claim.php         # Reject a claim
│   └── ai_report.php            # View AI damage report (admin)
│
└── ai_model/                    # Python ML microservice
    ├── main.py                  # FastAPI server (routes)
    ├── predict.py               # Model loading & inference
    ├── requirements.txt
    ├── rice_classification_model.pth
    ├── wheat_classification_model.pth
    └── rice_segmentation_model.pth  # (disabled/unused)
```

---

## 4. Database Schema

### `register_farmer` — Farmer accounts
| Column | Type | Notes |
|--------|------|-------|
| farmerid | INT PK | Auto-increment |
| name | VARCHAR | Full name |
| father_name | VARCHAR | |
| phone | VARCHAR(11) | Used as login credential |
| cnic | VARCHAR(13) | National ID |
| address | TEXT | |
| city | VARCHAR | |
| field_size | FLOAT | Total farm size |
| email | VARCHAR | |
| password | VARCHAR | bcrypt hash |

### `insurance_plan` — Insurance products
| Column | Type | Notes |
|--------|------|-------|
| plan_id | INT PK | Auto-increment |
| plan_name | VARCHAR | |
| plan_status | ENUM | Active / Draft / Inactive |
| applicable_crops | VARCHAR | Comma-separated |
| description | TEXT | |
| base_premium_rate | DECIMAL | Percentage |
| coverage_level | INT | Percentage |
| deductible_rate | INT | Percentage |
| coverage_type | VARCHAR | Yield Loss Only / Yield+Revenue / etc. |
| unit_structure | VARCHAR | Basic / Optional / Enterprise Unit |

### `farmer_applications` — Insurance applications
| Column | Type | Notes |
|--------|------|-------|
| application_id | INT PK | Auto-increment |
| farmer_id | INT FK | → register_farmer.farmerid |
| plan_id | INT FK | → insurance_plan.plan_id |
| policy_duration | VARCHAR | Kharif / Rabi |
| cnic_number | VARCHAR | Copied from farmer record |
| full_name | VARCHAR | |
| father_name | VARCHAR | |
| mobile_number | VARCHAR | |
| district | VARCHAR | |
| bank_account | VARCHAR | For claim payout |
| insured_area | FLOAT | In acres/kanals |
| irrigation_type | VARCHAR | Canal/River, Tube-well, Rainfed |
| crop_insured | VARCHAR | Wheat / Rice / Cotton / Sugarcane |
| expected_yield | FLOAT | Maunds per acre |
| historical_yield | VARCHAR | Comma-separated values |
| seed_variety | VARCHAR | |
| sowing_date | DATE | |
| certified_seed | TINYINT | 0 or 1 |
| **status** | VARCHAR | See status flow below |
| sum_insured | DECIMAL | Calculated by admin |
| final_premium | DECIMAL | Calculated by admin |
| guaranteed_production | FLOAT | Calculated by admin |
| application_date | TIMESTAMP | |

**Application Status Flow:**  
`Pending Premium` → `Premium Calculated` → `Active` → (if rejected at any point) → `Rejected`

### `insurance_claims` — Damage claims
| Column | Type | Notes |
|--------|------|-------|
| claim_id | INT PK | Auto-increment |
| application_id | INT FK | → farmer_applications |
| plan_id | INT FK | → insurance_plan |
| plan_name | VARCHAR | Copied at claim time |
| loss_date | DATE | When damage occurred |
| reason | VARCHAR | Disease Outbreak / Flood / Drought / Pest Attack |
| evidence_image | VARCHAR | Comma-separated filenames (6 images) |
| damaged_area | FLOAT | Acres affected |
| estimated_loss | DECIMAL | PKR |
| ai_status | VARCHAR | Analyzed / Not Analyzed |
| ai_result | VARCHAR | damaged / healthy |
| ai_confidence | FLOAT | Damage % (0–100) |
| claim_status | VARCHAR | Pending / Approved / Rejected |
| description | TEXT | |
| submitted_at | TIMESTAMP | |

### `users` — Admin accounts
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | Auto-increment |
| name | VARCHAR | Used as username |
| password | VARCHAR | bcrypt hash |
| role | VARCHAR | Default: 'admin' |

---

## 5. Authentication System

### Farmer Login (`user/login.php`)
```
POST: phone + password
  ↓
SELECT * FROM register_farmer WHERE phone = '$phone'
  ↓
password_verify($password, $farmer['password'])
  ↓ success
$_SESSION['farmer_id'] = farmerid
$_SESSION['farmer_name'] = name
  ↓
Redirect → user/index.php
```

### Farmer Registration (`user/farmer_register.php`)
```
POST: name, father_name, phone, cnic, email, address, city, field_size, password
  ↓
Validate: phone (11 digits), cnic (13 digits)
  ↓
Check duplicate: phone OR cnic already in register_farmer?
  ↓ unique
password_hash($password, PASSWORD_BCRYPT)
  ↓
INSERT into register_farmer
  ↓
Redirect → login.php
```

### Admin Login (`admin/login.php`)
```
POST: username + password
  ↓
SELECT * FROM users WHERE name = '$username'
  ↓
password_verify($password, $user['password'])
  ↓ success
$_SESSION['user_id'] = id
$_SESSION['name'] = name
$_SESSION['role'] = role
  ↓
Redirect → dashboard.php
```

### Session Guards
- Every protected farmer page checks: `if (!isset($_SESSION['farmer_id'])) → redirect login.php`
- Every admin page checks: `if (!isset($_SESSION['user_id'])) → redirect login.php`

---

## 6. Farmer (User) Flow

### 6.1 Public Access (No Login Required)
```
user/index.php          → Home page with plan carousel, about section
user/insurance_plans.php → Browse all Active plans
user/contact.php        → Contact form + map
user/farmer_register.php → Self-registration
user/login.php          → Login
```

### 6.2 Authenticated Farmer Journey

#### Step 1 — Browse & Apply for a Plan
```
insurance_plans.php
  ↓  (click "Apply Now" on plan card)
apply_plan.php?plan_id=X
  ↓
Section 1: Plan + policy duration (Kharif/Rabi)
Section 2: Farmer details auto-filled (readonly) from register_farmer
Section 3: Land info — district, bank account, insured area, irrigation type
Section 4: Crop info — crop type, sowing date, expected yield, historical yield,
           seed variety, certified seed checkbox
  ↓ submit
INSERT farmer_applications (status = "Pending Premium")
  ↓
Redirect → view_application_plan.php?success=1
```

#### Step 2 — Wait for Admin Approval
```
view_application_plan.php
  ↓
Shows all applications as cards
Status badges:
  🟡 Pending Premium   → waiting for admin to review
  🔵 Premium Calculated → admin approved, invoice ready
  🔴 Rejected           → application declined
  🟢 Active             → fully approved and active policy

If "Premium Calculated": shows "View Invoice" button
  ↓
view_application_invoice.php?id=X
  ↓
Displays: sum insured, premium rate, final premium amount, coverage details
Print/Download button available
```

#### Step 3 — File a Damage Claim
```
apply_claim.php
  ↓
Dropdown: select policy (only "Premium Calculated" applications shown)
  → auto-fills crop type
  ↓
Fill: loss date, damaged area, estimated loss, reason
  ↓
Upload exactly 6 crop images:
  - Client-side SHA-256 hash detects duplicates
  - Preview grid shows thumbnails
  - Status: "6 unique images selected" → enables "Run AI Analysis"
  ↓
[Run AI Analysis] button → AJAX POST to analyze_ai.php:
  - Sends: 6 images + crop_type
  - PHP → FastAPI: POST /analyze per image
  - Response: damaged count, damage %, per-image verdict
  - UI shows: 🟢 APPROVED or 🔴 REJECTED verdict box
  - Per-image badges: "Damaged 87.3%" or "Healthy 94.1%"
  - Stores result in hidden inputs
  ↓
[Submit Claim] button → form POST:
  - Saves 6 images to admin/images/ as {timestamp}_{1-6}.{ext}
  - INSERT insurance_claims with:
      ai_status = "Analyzed"
      ai_result = "damaged" or "healthy"
      ai_confidence = damage %
      claim_status = auto-set by AI result
  ↓
Alert shown with result
```

#### Step 4 — Track Claims & View Reports
```
view_application_claim.php
  ↓
Cards per claim showing:
  - Thumbnail of first evidence image
  - Farmer name, CNIC, plan, loss date
  - Damaged area, reason, estimated loss
  - AI result badge (Damaged/Healthy)
  - Damage severity %
  - Claim status badge (Pending/Approved/Rejected)
  - "View AI Report" button

  ↓ (click View AI Report)

ai_report.php?id=X
  ↓
Full assessment report:
  - Farmer info + loss details
  - All 6 uploaded evidence images
  - AI verdict: damage %, eligibility status
  - Eligibility: ELIGIBLE (≥50% damage) / NOT ELIGIBLE (<50%)
  - Per-image breakdown (class + confidence)
  - Print button
```

#### Farmer Profile
```
farmer_profile.php
  ↓
Profile card: photo, name, CNIC, phone, email, city, address
Farm details: field size, crop, district, irrigation type
Stats: total applications count, total claims count
Latest AI analysis result (if exists)
```

### 6.3 Farmer Navigation (Navbar)
```
KissanSure
├── Home
├── Insurance Plans
├── My Application       [logged in only]
├── Apply for Claim      [logged in only]
├── My Claims            [logged in only]
└── Profile dropdown     [logged in only]
    ├── My Profile
    └── Logout
    
[Not logged in]: Login button + Register button
```

---

## 7. Admin Flow

### 7.1 Dashboard (`admin/dashboard.php`)
Shows 4 summary cards:
- **Total Farmers** — COUNT(*) from register_farmer
- **Total Policies** — COUNT(*) from farmer_applications
- **Pending Claims** — COUNT where claim_status = 'Pending'
- **Approved Claims** — COUNT where claim_status = 'Approved'

### 7.2 Farmer Management
```
add_register.php     → Admin manually creates farmer record
                       Same fields as self-registration
                       Duplicate phone/CNIC check

view_register.php    → Table: farmerid, name, phone, cnic, city, address
                       Actions: Update | Delete

update_register.php  → Edit any farmer field

delete_register.php  → Hard delete from register_farmer
```

### 7.3 Insurance Plan Management
```
add_plan.php         → Create plan:
                         name, status (Active/Draft/Inactive)
                         applicable_crops (multi-select)
                         description
                         base_premium_rate (%)
                         coverage_level (%)
                         deductible_rate (%)
                         coverage_type
                         unit_structure

view_plan.php        → Table of all plans with status badges
                       Actions: Update | Delete

update_plan.php      → Edit plan parameters
delete_plan.php      → Remove plan
```

### 7.4 Application Management

#### Creating Applications
```
add_insurance_application.php
  ↓
Farmer dropdown → auto-fills CNIC, name, father_name, phone, address (readonly)
Select plan, policy duration (Kharif/Rabi)
Fill: district, bank account, insured area, crop, sowing date,
      irrigation type, expected yield, historical yield, seed variety
  ↓
INSERT farmer_applications (status = "Pending Premium")
```

#### Processing Applications
```
view_insurance_application.php
  ↓
Table: application_id, farmer name, CNIC, plan, crop, area, season, status
  ↓
Per status, different action buttons:

  🟡 "Pending Premium":
      [Approve] → approve_application.php?id=X
        Premium Calculation:
          Sum Insured (SI) = Area × Expected Yield × 1500
          Gross Premium    = SI × (Base Rate / 100)
          Final Premium    = Gross Premium × Risk Factor (1.0)
          Guaranteed Prod  = Expected Yield × (Coverage Level / 100)
        UPDATE status → "Premium Calculated"
        
      [Reject] → reject_application.php?id=X
        UPDATE status → "Rejected"

  🔵 "Premium Calculated":
      [View Invoice] → add_farmer_invoice.php?id=X
        Shows: farmer details, coverage summary, financial breakdown
        SI, premium rate, final premium, print button

  🔴 "Rejected": text label only

  🟢 "Active": view only
```

### 7.5 Claims Management

#### Filing Claims (Admin)
```
add_file_claim.php
  ↓
Select active application (from farmer_applications)
Upload 6 crop images
Fill: loss date, damaged area, estimated loss, reason, description
  ↓
INSERT insurance_claims
```

#### Processing Claims
```
view_claims.php
  ↓
Table: claim_id, farmer, crop, loss date, estimated loss, reason,
       first image thumbnail, AI status, claim status
  ↓
Per status & AI status, different buttons:

  Pending + Not Analyzed:
      [Analyze (AI)] → analyze_ai.php (AJAX)
        → 6 images sent to FastAPI /analyze endpoint
        → per-image results returned
        → UPDATE insurance_claims:
            ai_status = "Analyzed"
            ai_result = "damaged"/"healthy"
            ai_confidence = damage %

  Pending + Analyzed:
      [Approve Claim] → approve_claim.php?id=X
        UPDATE claim_status = "Approved"
      [Reject Claim]  → reject_claim.php?id=X
        UPDATE claim_status = "Rejected"
      [View Report]   → ai_report.php?id=X

  Approved or Rejected:
      [View Report] only → ai_report.php?id=X
```

### 7.6 Admin Sidebar Navigation
```
Admin Panel
├── Dashboard
├── Farmer Management
│   ├── Add Farmer
│   └── View Farmers
├── Insurance Plans
│   ├── Add Plan
│   └── View Plans
├── Applications
│   ├── Add Application
│   └── View Applications
├── Claims
│   ├── File Claim
│   └── View Claims
└── Logout
```

---

## 8. AI/ML Integration Flow

### Architecture
```
PHP (User or Admin) → analyze_ai.php → FastAPI (port 8000) → PyTorch Model → JSON response
```

### FastAPI Server (`ai_model/main.py`)
- Framework: FastAPI
- Start: `uvicorn main:app --reload` on port 8000
- Endpoint: `POST /analyze`
  - Input: `crop_type` (form field) + `file` (image)
  - Output: `{"class": "damaged"|"healthy", "confidence": 0.0–1.0}`

### Model Details (`ai_model/predict.py`)
| Attribute | Value |
|-----------|-------|
| Architecture | MobileNetV2 (pretrained on ImageNet) |
| Output | Binary: damaged / healthy |
| Input size | 224 × 224 px |
| Normalization | mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225] |
| Supported crops | Wheat, Rice |
| Models | rice_classification_model.pth, wheat_classification_model.pth |

### PHP Analysis Endpoint (`analyze_ai.php`) — Called via AJAX
```
Receive: 6 images (multipart form) + crop_type
  ↓
For each of the 6 images:
  POST to http://127.0.0.1:8000/analyze
    Form: file=<image_binary>, crop_type=<crop>
    Response: {"class": "damaged"|"healthy", "confidence": float}
  Store: image index, class, confidence
  ↓
Count damaged images (class == "damaged")
  ↓
damage_percent = (damaged_count / 6) × 100
is_eligible    = damaged_count >= 3   (50% threshold)
  ↓
Return JSON:
{
  "ai_result":       "damaged" | "healthy",
  "ai_confidence":   damage_percent,
  "is_eligible":     true | false,
  "damage_percent":  float,
  "verdict":         "Crop shows significant damage..." | "Crop appears healthy...",
  "image_results":   [ {image, class, confidence}, ... ],
  "segmentation_used": false
}
```

### Decision Logic
| Damaged Images (out of 6) | Damage % | Decision |
|--------------------------|----------|----------|
| 0–2 | 0–33% | NOT ELIGIBLE — claim rejected |
| 3–4 | 50–67% | ELIGIBLE — claim approved |
| 5–6 | 83–100% | ELIGIBLE — claim approved |

**Threshold:** 3 or more damaged images (≥50%) = eligible for payout.

---

## 9. Premium Calculation Logic

Triggered when admin approves an application in `approve_application.php`:

```
Fixed Crop Price   = 1,500 PKR per Maund

Sum Insured (SI)   = insured_area × expected_yield × 1500
Gross Premium      = SI × (base_premium_rate / 100)
Risk Factor        = 1.0  (default, hardcoded)
Final Premium      = Gross Premium × Risk Factor
Guaranteed Prod.   = expected_yield × (coverage_level / 100)
```

**Example:**
```
Area:            5 acres
Expected Yield:  40 Maunds/acre
Base Rate:       4%
Coverage Level:  80%

SI            = 5 × 40 × 1500 = PKR 300,000
Gross Premium = 300,000 × 0.04 = PKR 12,000
Final Premium = 12,000 × 1.0  = PKR 12,000
Guaranteed Prod = 40 × 0.80   = 32 Maunds/acre
```

---

## 10. Full End-to-End Journey

```
═══════════════════════════════════════════════════════════════════
                     FARMER JOURNEY
═══════════════════════════════════════════════════════════════════

[1] REGISTER
    farmer_register.php
    → Enters: name, father, phone, CNIC, address, city, area, password
    → System: validates uniqueness, hashes password, saves to register_farmer

[2] LOGIN
    login.php (phone + password)
    → Session: farmer_id, farmer_name

[3] BROWSE PLANS
    insurance_plans.php
    → Sees: all Active plans with premium rate, coverage %, crop types

[4] APPLY FOR PLAN
    apply_plan.php?plan_id=X
    → Farmer details auto-filled
    → Enters: district, bank account, area, crop, yield, irrigation...
    → Record created in farmer_applications (status: Pending Premium)

      ─────────── WAIT FOR ADMIN ACTION ───────────

[5] VIEW APPLICATION STATUS
    view_application_plan.php
    → Premium Calculated → View Invoice → print/download

[6] FILE CLAIM (when crop is damaged)
    apply_claim.php
    → Select active policy
    → Upload 6 crop images
    → Run AI Analysis (instant result)
    → Submit claim (auto-approved or auto-rejected by AI)

[7] TRACK CLAIM
    view_application_claim.php
    → See status + AI report

[8] VIEW AI REPORT
    ai_report.php?id=X
    → All 6 images, per-image classification, eligibility verdict


═══════════════════════════════════════════════════════════════════
                      ADMIN JOURNEY
═══════════════════════════════════════════════════════════════════

[1] SETUP
    signup.php → create admin account
    login.php  → session: user_id, name, role

[2] MANAGE PLANS
    add_plan.php       → define crop, premium rate, coverage %
    view/update/delete → maintain plan catalog

[3] MANAGE FARMERS
    view_register.php  → see all registered farmers
    add_register.php   → manually onboard farmers
    update/delete      → maintain records

[4] PROCESS APPLICATIONS
    view_insurance_application.php
    ├── Pending Premium:
    │     Approve → auto-calculates SI + premium
    │     Reject  → marks rejected
    └── Premium Calculated:
          View Invoice → printable quote

[5] PROCESS CLAIMS
    view_claims.php
    ├── Pending + Not Analyzed:
    │     Analyze AI → FastAPI runs classification on 6 images
    │     Result stored in DB (ai_result, ai_confidence)
    ├── Pending + Analyzed:
    │     Approve Claim → payout approved
    │     Reject Claim  → claim denied
    └── Any status:
          View AI Report → full damage assessment


═══════════════════════════════════════════════════════════════════
              APPLICATION STATUS LIFECYCLE
═══════════════════════════════════════════════════════════════════

farmer_applications.status:

  [Submit]          → "Pending Premium"
  [Admin Approve]   → "Premium Calculated"
  [Admin Reject]    → "Rejected"
  (future: payment) → "Active"


═══════════════════════════════════════════════════════════════════
              CLAIM STATUS LIFECYCLE
═══════════════════════════════════════════════════════════════════

insurance_claims.claim_status:

  [Farmer Submits]                       → "Pending"
  [AI Analysis: ≥3 of 6 damaged]         → auto → "Approved" (or stays Pending for admin)
  [AI Analysis: <3 of 6 damaged]         → auto → "Rejected" (or stays Pending for admin)
  [Admin manually Approves]              → "Approved"
  [Admin manually Rejects]              → "Rejected"
```

---

## 11. API Endpoints Reference

### User Portal (`/user/`)
| File | Method | Auth | Purpose |
|------|--------|------|---------|
| index.php | GET | No | Home page |
| login.php | GET/POST | No | Farmer login |
| logout.php | GET | Yes | Destroy session |
| farmer_register.php | GET/POST | No | Self-register |
| insurance_plans.php | GET | No | Browse plans |
| apply_plan.php | GET/POST | Yes | Apply for plan |
| view_application_plan.php | GET | Yes | My applications |
| view_application_invoice.php | GET | Yes | Invoice (id=X) |
| apply_claim.php | GET/POST | Yes | File claim |
| analyze_ai.php | POST | Yes | AI analysis (JSON) |
| view_application_claim.php | GET | Yes | My claims |
| ai_report.php | GET | Yes | Claim AI report (id=X) |
| farmer_profile.php | GET | Yes | Profile & stats |
| contact.php | GET | No | Contact page |

### Admin Panel (`/admin/`)
| File | Method | Auth | Purpose |
|------|--------|------|---------|
| login.php | GET/POST | No | Admin login |
| signup.php | GET/POST | No | Create admin |
| logout.php | GET | Yes | Destroy session |
| dashboard.php | GET | Yes | Stats overview |
| add_register.php | GET/POST | Yes | Add farmer |
| view_register.php | GET | Yes | List farmers |
| update_register.php | GET/POST | Yes | Edit farmer |
| delete_register.php | GET | Yes | Delete farmer (id=X) |
| add_plan.php | GET/POST | Yes | Create plan |
| view_plan.php | GET | Yes | List plans |
| update_plan.php | GET/POST | Yes | Edit plan |
| delete_plan.php | GET | Yes | Delete plan (id=X) |
| add_insurance_application.php | GET/POST | Yes | Create application |
| view_insurance_application.php | GET | Yes | List applications |
| approve_application.php | GET | Yes | Approve + calculate premium (id=X) |
| reject_application.php | GET | Yes | Reject application (id=X) |
| add_farmer_invoice.php | GET | Yes | View invoice (id=X) |
| add_file_claim.php | GET/POST | Yes | File claim |
| view_claims.php | GET | Yes | List claims |
| analyze_ai.php | POST | Yes | AI analysis (JSON) |
| approve_claim.php | GET | Yes | Approve claim (id=X) |
| reject_claim.php | GET | Yes | Reject claim (id=X) |
| ai_report.php | GET | Yes | View AI report (id=X) |

### Python FastAPI (`http://127.0.0.1:8000`)
| Endpoint | Method | Input | Output |
|----------|--------|-------|--------|
| /analyze | POST | crop_type (form), file (image) | `{"class": str, "confidence": float}` |
| /segment | POST | (DISABLED) | — |

---

## 12. Key Constants & Business Rules

| Constant | Value | File |
|----------|-------|------|
| DB Name | crop_insurance_one | connection.php |
| DB Host | localhost | connection.php |
| DB User | root (no password) | connection.php |
| AI Server URL | http://127.0.0.1:8000/analyze | analyze_ai.php |
| Crop Price (per Maund) | PKR 1,500 | approve_application.php |
| AI Eligibility Threshold | 3 of 6 images damaged (50%) | analyze_ai.php |
| Risk Factor | 1.0 (default) | approve_application.php |
| Image Upload Path | admin/images/ | apply_claim.php |
| Image Naming | {timestamp}_{1-6}.{ext} | apply_claim.php |
| Max Claim Evidence Images | 6 (exactly) | apply_claim.php |
| Supported Crop Models | Wheat, Rice | predict.py |
| Model Input Size | 224 × 224 px | predict.py |

### Supported Crop Types
- Wheat, Rice, Cotton, Sugarcane, Maize

### Insurance Seasons
- **Kharif** — summer/monsoon crop season
- **Rabi** — winter crop season

### Damage Reasons (Claim)
- Disease Outbreak, Flood, Drought, Pest Attack

### Coverage Types (Plan)
- Yield Loss Only
- Yield + Revenue Loss
- Yield + Revenue + Quality Loss

### Unit Structures (Plan)
- Basic Unit, Optional Unit, Enterprise Unit

### Irrigation Types
- Canal/River, Tube-well, Rainfed (Barani)

---

*Generated: 2026-05-15 | Project: KissanSure FYP 2026*
