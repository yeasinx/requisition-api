# 03 — Authentication & Authorization

This document covers the authentication mechanism, role-based access control (RBAC), and policy-driven security rules within the system.

---

## 1. Authentication (Laravel Sanctum)

Authentication uses **Laravel Sanctum** personal access tokens.

### Token Flow
1. The client sends credentials (`email`, `password`) to `POST /api/login`.
2. [`AuthService::attemptLogin()`](file:///home/zero/Documents/pc/requisition-api/app/Services/AuthService.php) validates credentials against bcrypt-hashed passwords.
3. Upon success, a plain text Sanctum token is generated (`$user->createToken('auth_token')->plainTextToken`) and returned.
4. For all subsequent requests, the client passes this token in the `Authorization` HTTP header:
   ```http
   Authorization: Bearer <sanctum_token>
   ```
5. Calling `POST /api/logout` revokes the active token via `$user->currentAccessToken()->delete()`.

---

## 2. User Roles & Personas

The system classifies users via the `UserType` enum into four distinct roles:

| Role | Description | Core Responsibilities |
| :--- | :--- | :--- |
| **`SUPER_ADMIN`** | System Administrator | Full access to assign system approvers, manage users, and view all requisitions across the organization. |
| **`HR_ADMIN`** | Human Resources Administrator | Manages user accounts (create, update, view) and acts as the final stage approval step in the requisition workflow. |
| **`ACCOUNTS`** | Financial / Accounting Staff | Reviews requisitions for budgetary compliance at the `ACCOUNTS` step. |
| **`EMPLOYEE`** | Standard Team Member | Submits purchase and requisition requests, tracks approval status. |

> [!NOTE]
> Specific approval stage responsibilities (e.g. `APPROVER_1`, `APPROVER_2`, `BUSINESS_CONTROLLER`) are dynamically assigned to specific user accounts via `SystemSettings`, allowing any user account to be designated as a functional approver regardless of their underlying base role.

---

## 3. Authorization Policies Matrix

Laravel Policies guard all resource mutations and queries.

### 3.1 Requisition Policy (`RequisitionPolicy`)

| Action | Gate Method | Authorized Users / Conditions |
| :--- | :--- | :--- |
| **List All / Scoped** | `viewAny` | All authenticated users. (Results are automatically scoped based on role and assigned steps in `RequisitionController@index`). |
| **View Details** | `view` | `SUPER_ADMIN`, Submitter of the requisition, or the designated approver for the requisition's `current_step`. |
| **Create Requisition** | `create` | Any authenticated user *except* `SUPER_ADMIN` (Admins manage settings and users). |
| **Update Requisition** | `update` | Submitter only, and **only before any approvals have taken place** (`approvals()->count() === 0`). |
| **Delete Requisition** | `delete` | Submitter only, and **only while status is still `PENDING`**. |
| **Approve Requisition** | `approve` | Requisition must be `PENDING`, and authenticated user **must match the designated approver for `current_step`** in `SystemSettings`. |
| **Deny Requisition** | `deny` | Requisition must be `PENDING`, and authenticated user **must match the designated approver for `current_step`** in `SystemSettings`. |

### 3.2 User Policy (`UserPolicy`)

| Action | Gate Method | Authorized Users / Conditions |
| :--- | :--- | :--- |
| **List Users** | `viewAny` | `SUPER_ADMIN`, `HR_ADMIN`. |
| **View User** | `view` | `SUPER_ADMIN`, `HR_ADMIN`, or the user viewing their own profile. |
| **Create User** | `create` | `SUPER_ADMIN`, `HR_ADMIN`. |
| **Update User** | `update` | `SUPER_ADMIN`, `HR_ADMIN`. |
| **Delete User** | `delete` | `SUPER_ADMIN` only. (Cannot delete own account). |

### 3.3 System Settings Policy (`SystemSettingsPolicy`)

| Action | Gate Method | Authorized Users / Conditions |
| :--- | :--- | :--- |
| **View Settings** | `view` | `SUPER_ADMIN` only. |
| **Update Settings** | `update` | `SUPER_ADMIN` only. |

---

## 4. Query Visibility Scoping

When a user calls `GET /api/requisitions`, query scoping is automatically applied in `RequisitionController@index`:

1. **`SUPER_ADMIN`**: Can view all requisitions in the system across all departments and statuses.
2. **Other Users**:
   - Requisitions created/submitted by themselves (`submitted_by_user_id = user.id`).
   - Requisitions that are currently `PENDING` and waiting at an approval step assigned to this user in `SystemSettings`.
   - Requisitions that this user previously reviewed/acted upon (`whereHas('approvals', ...)`) in the past.
