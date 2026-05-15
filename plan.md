# KissanSure — Complete Financial & Payment Workflow Plan

## Context

The existing system handles farmer registration, plan browsing, application submission, and a partial premium calculation step. However the workflow is incomplete:
- Premium calculation uses a hardcoded crop price (1500 PKR/maund) instead of admin-managed dynamic rates
- Coverage Amount is never calculated or stored
- Premium formula is wrong (should be on Coverage Amount, not Sum Insured)
- No payment upload, verification, or policy activation system exists
- Claims can never be filed because applications never reach "Active"
- Claim approve/reject logic has a reversed-status bug
- No payout calculation, payout receipt, or claim completion workflow

This plan extends the existing workflow with all missing pieces to make KissanSure a complete AI-powered crop insurance ecosystem without rebuilding anything that already works.

---

## Phase 1 — Database Changes

### 1A. New Table: `crop_market_rates`

```sql
CREATE TABLE crop_market_rates (
    rate_id         INT AUTO_INCREMENT PRIMARY KEY,
    crop_name       VARCHAR(100) NOT NULL,
    price_per_maund DECIMAL(10,2) NOT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO crop_market_rates (crop_name, price_per_maund) VALUES
('Wheat', 3200.00),
('Rice',  5400.00);
```

### 1B. Extend `farmer_applications`

```sql
ALTER TABLE farmer_applications
    ADD COLUMN coverage_amount DECIMAL(15,2) DEFAULT NULL AFTER guaranteed_production,
    ADD COLUMN payment_proof   VARCHAR(255)  DEFAULT NULL,
    ADD COLUMN transaction_id  VARCHAR(100)  DEFAULT NULL,
    ADD COLUMN payment_date    DATE          DEFAULT NULL,
    ADD COLUMN payment_status  VARCHAR(30)   DEFAULT 'Pending',
    ADD COLUMN payment_notes   TEXT          DEFAULT NULL,
    ADD COLUMN activation_date DATE          DEFAULT NULL;
```

`payment_status` values: `Pending` | `Submitted` | `Verified` | `Rejected`

### 1C. Extend `insurance_claims`

```sql
ALTER TABLE insurance_claims
    ADD COLUMN damage_percentage DECIMAL(5,2)  DEFAULT NULL,
    ADD COLUMN damage_loss       DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN final_payout      DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN payout_status     VARCHAR(30)   DEFAULT 'Pending',
    ADD COLUMN payout_receipt    VARCHAR(255)  DEFAULT NULL,
    ADD COLUMN payout_date       DATE          DEFAULT NULL,
    ADD COLUMN payout_notes      TEXT          DEFAULT NULL;
```

`payout_status` values: `Pending` | `Approved` | `Sent` | `Completed`

---

## Phase 2 — Application Status Lifecycle (Updated)

```
Pending Premium
  → [Admin Approves]  → Premium Calculated
  → [Farmer Pays]     → Payment Submitted
  → [Admin Verifies]  → Active
  → [Admin Rejects]   → Rejected
```

`claim_status` on insurance_claims:
```
Pending → AI Analyzed → Under Review → Approved → Payout Sent → Completed
```

---

## Phase 3 — Fix Premium Calculation Logic

**File:** `admin/approve_application.php`

### Current (wrong):
```
SI      = area × yield × 1500  (hardcoded)
Premium = SI × (premium_rate / 100)
```

### New (correct):
```
Market Rate       = SELECT price_per_maund FROM crop_market_rates WHERE crop_name = crop_insured
SI                = area × yield × market_rate
Coverage Amount   = SI × (coverage_level / 100)
Premium           = Coverage Amount × (base_premium_rate / 100)
Guaranteed Prod.  = yield × (coverage_level / 100)
```

**Example — Standard Plan, 5 acres, 40 maund/acre, Wheat @ 3200:**
- SI               = 5 × 40 × 3200 = 640,000 PKR
- Coverage Amount  = 640,000 × 75% = 480,000 PKR
- Premium          = 480,000 × 10% =  48,000 PKR

---

## Phase 4 — New Files

### 4A. `admin/manage_rates.php`
- Table showing Wheat and Rice price per maund
- Edit form to update each rate
- Shows last updated timestamp
- Linked from admin navbar

### 4B. `admin/verify_payment.php`
- Triggered from application list for "Payment Submitted" rows
- Shows: Farmer name, CNIC, Application ID, Premium Due, Transaction ID, payment screenshot
- Actions: **Verify** → status=Active, payment_status=Verified | **Reject** → payment_status=Rejected + note

### 4C. `user/submit_payment.php`
- Farmer uploads payment proof (image) from invoice page
- Fields: Transaction ID, Payment Date, Screenshot
- Uploads to `user/uploads/payments/`
- Sets status = "Payment Submitted", payment_status = "Submitted"

### 4D. `admin/claim_payout.php`
- Admin finalises payout for Approved claims
- Damage % mapped from ai_confidence: 0–34% → 20%, 35–67% → 50%, 68–100% → 80%
- Formula: `Damage Loss = Coverage Amount × damage%` | `Payout = Damage Loss × (1 - deductible%)`
- Admin uploads payout receipt; sets payout_status = "Sent"

### 4E. `user/view_claim_payout.php`
- Farmer views approved payout: coverage amount, damage %, damage loss, deductible, final payout
- Shows receipt image, payout date, download link

---

## Phase 5 — Modified Files

| File | Change |
|------|--------|
| `admin/approve_application.php` | Dynamic market rate query + correct formula + store coverage_amount |
| `admin/view_insurance_application.php` | Payment status column + "Verify Payment" button |
| `admin/add_farmer_invoice.php` | Show Coverage Amount row |
| `admin/view_claims.php` | Payout status column + "Issue Payout" button |
| `admin/add_file_claim.php` | Require status = 'Active' (not 'Premium Calculated') |
| `user/view_application_invoice.php` | Coverage Amount row + Pay Premium button |
| `user/view_application_plan.php` | New status badges (Payment Submitted, Active) |
| `user/apply_claim.php` | Require status = 'Active' |
| `user/view_application_claim.php` | Payout status badge + "View Payout" link |

---

## Phase 6 — Bug Fixes

### `admin/approve_claim.php` line 16
```php
// Was:   SET claim_status = 'Rejected'
// Fixed: SET claim_status = 'Approved'
```

### `admin/reject_claim.php` line 16
```php
// Was:   SET claim_status = 'Approved'
// Fixed: SET claim_status = 'Rejected'
```

---

## Phase 7 — Upload Directories

```
user/uploads/payments/    ← farmer premium payment screenshots
admin/uploads/payouts/    ← admin claim payout receipts
```

---

## Implementation Order

1. DB migrations (foundation)
2. Fix approve/reject claim bug (quick win)
3. Fix approve_application.php (dynamic rates + coverage amount)
4. Build admin/manage_rates.php
5. Build user/submit_payment.php + update invoice page
6. Build admin/verify_payment.php + update application list
7. Update view_application_plan.php status badges
8. Update apply_claim.php Active check
9. Build admin/claim_payout.php + update view_claims.php
10. Build user/view_claim_payout.php + update view_application_claim.php
11. Create upload directories + update admin invoice + add navbar links

---

## Verification Checklist

1. Admin updates Wheat rate to 3200 in manage_rates.php
2. Farmer applies → Admin approves → Premium uses dynamic rate, coverage_amount stored
3. Farmer views invoice → Coverage Amount visible → "Pay Premium" button present
4. Farmer submits payment proof → Status = "Payment Submitted"
5. Admin verifies → Status = "Active"
6. Farmer can now access apply_claim.php
7. Claim submitted → AI runs → damage % mapped (20/50/80)
8. Admin approves claim (status = 'Approved' — bug fixed)
9. Admin opens claim_payout.php → Payout calculated → Receipt uploaded → Status = "Payout Sent"
10. Farmer opens view_claim_payout.php → Sees final payout and receipt
