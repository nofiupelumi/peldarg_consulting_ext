# Peldarg Credit Extraction Plan

## 1. Platform Identity
- Project root: `/Users/mac/Desktop/peldargconsulting_version2_ai_agent_secondlevel_verification`
- Owner: `Peldarg Consulting Limited`
- Production domain: `extract.peldargconsulting.com`
- GitHub repository: `https://github.com/nofiupelumi/peldarg_consulting_ext.git`
- Database name (XAMPP/phpMyAdmin): `creditbase_peldarg_extraction`

## 2. Final Business Rules (Locked)
- Credit consumption is based on `pages_processed`.
- `pages_with_results` must still be logged for reporting.
- 1 processed page = 1 credit.
- Paid tier price = `0.06 USD` per credit/page. They should pay in naira currently 1dollar = 1500naira put the notice on the frontend. lets the admin be able to update the price.
- Free-tier uploads still consume credits.
- Users cannot choose free tier unless explicitly enabled by admin.
- Failed upload should not deduct credit.
- Failed/partial page issues should be logged and any adjustment can be manually refunded by admin.
- Credit cap is treated as a hard maximum, including admin top-up operations.
- Invoice proof details should be sent to `peldargconsulting@gmail.com`.

## 3. User and Auth Model
- A user account represents a company name (not an individual personal profile).
- Only admin can create user login credentials.
- Only admin can reset user passwords.
- On admin password reset, system generates a new temporary password and sends it automatically to the user email.
- User self-registration is disabled.

Recommended user fields:
- `company_name` (required, unique)
- `email` (required, unique)
- `password`
- `is_admin` (boolean)
- `can_use_free_tier` (boolean, default false)
- `status` (`active`, `suspended`)
- `credit_balance` (decimal)
- `credit_cap` (decimal)

## 4. Credit Accounting Design
Use append-only ledger plus document-level summaries.

### 4.1 Tables
1. `credit_ledger`
- `user_id`
- `document_id` nullable
- `invoice_id` nullable
- `action_type` (`reserve`, `consume`, `refund`, `admin_add`, `admin_deduct`, `invoice_approved`, `invoice_rejected`, `password_reset`)
- `credits` (signed decimal)
- `balance_before`, `balance_after`
- `api_tier` (`free`, `paid`) nullable
- `unit_price_usd` nullable
- `amount_usd` nullable
- `meta` json
- `created_by_user_id`

2. `credit_audit_logs`
- `actor_user_id`
- `target_user_id`
- `event_key`
- `entity_type`, `entity_id`
- `old_values`, `new_values`
- `ip_address`, `user_agent`, `request_id`

3. `credit_invoices`
- `user_id`
- `invoice_number`
- `requested_credits`
- `unit_price_usd` default `0.0600`
- `requested_amount_usd`
- `payment_reference`
- `proof_path` nullable
- `status` (`pending`, `approved`, `rejected`, `cancelled`)
- `admin_note`, `reviewed_by_user_id`, `reviewed_at`

4. `documents` additional fields
- `user_id`
- `api_tier`
- `pages_requested`
- `pages_processed`
- `pages_with_results`
- `credits_consumed`
- `credits_refunded`
- `credit_status` (`none`, `reserved`, `finalized`, `failed`)
- `failed_reason`

### 4.2 Charging and Refund Rules
- Reserve credits at upload using estimated pages.
- Final consume amount uses `pages_processed`.
- Log `pages_with_results` without using it for charge.
- If upload fails before processing starts, full reservation is released.
- If partial failures happen after processing starts, keep accounting logs and handle manual admin refund when approved.

## 5. Upload and Callback Flow
1. Upload request:
- Validate authenticated company user.
- Determine selected tier.
- If tier is `free`, enforce `can_use_free_tier = true`.
- Compute `pages_requested` and reserve equal credits.

2. Dispatch workflow payload:
- Include `doc_id`, `user_id`, `api_tier`, page range, and request id.

3. Callback finalization:
- Receive `pages_processed`, `pages_with_results`, and status.
- Consume credits from `pages_processed`.
- Store document metrics and tier.
- Write ledger and audit entries.

4. Failure handling:
- Upload failure: do not deduct credits.
- Processing anomalies: log events and allow admin manual refund entry.

## 6. Admin-Only Credential Controls
Required endpoints/actions:
- Admin create user with company name + email.
- Admin reset password for a user.
- System sends password email automatically on reset.

Email content on reset:
- Company name
- Login URL (`https://extract.peldargconsulting.com`)
- Temporary password
- Force-change-password instruction on next login


## 7. paystack,Invoice and Payment Workflow
1. User submits top-up request with:
- requested credits
- payment reference
- optional receipt file
 paystackflow to be added

2. System immediately emails invoice proof/request details to:
- `peldargconsulting@gmail.com`

3. Admin reviews and approves/rejects:
- Approve: add credits (must not exceed cap)
- Reject: keep balance unchanged and store reason

Payment instruction shown in UI and mail:
- Account Name: `Peldarg Consulting Limited`
- Bank: `Moniepoint Bank`
- Account Number: `8107837073`

## 8. Security and Compliance
- Protect admin routes with `is_admin` middleware.
- Use transactions with row locking for all balance mutations.
- Keep ledger append-only.
- Add callback idempotency key to prevent duplicate charging.
- Store all important events in audit logs.
- google 2fa for admin and should be able to activate and deactivate in admin settings

## 9. Testing Plan
1. Unit tests
- Credit math from `pages_processed`.
- Cap enforcement for admin add/invoice approval.
- Free-tier access gate using `can_use_free_tier`.

2. Feature tests
- Admin creates user; user cannot self-register.
- Admin password reset triggers email with temporary password.
- Upload with insufficient credit fails.
- Failed upload produces no credit deduction.
- Callback consumes by `pages_processed` and logs `pages_with_results`.
- Free tier denied when `can_use_free_tier = false`.
- Invoice submission sends mail to `peldargconsulting@gmail.com`.
- Invoice approve/reject performs correct ledger and balance actions.

3. Concurrency tests
- Simultaneous uploads do not overspend balance.
- Callback replay does not double-consume.

4. Audit tests
- Every admin/manual refund action has actor, reason, and timestamp.

## 10. Implementation Order
1. Migrations and models for users/documents/ledger/audit/invoices.
2. Service layer for credit reserve/consume/refund/manual refund.
3. Admin user management (create user, reset password + mail).
4. Upload/callback credit integration.
5. Invoice submission + email + admin approval flow.
6. UI updates for balances, tier permissions, and audit visibility.
