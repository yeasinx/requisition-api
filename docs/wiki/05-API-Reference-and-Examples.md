# 05 — API Reference & Examples

All API requests accept JSON payloads and return JSON responses. Protected endpoints require the `Authorization: Bearer <token>` header.

### Common Headers
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <sanctum_token>
```

---

## 1. Authentication

### `POST /api/login`
Authenticates user credentials and issues a Sanctum personal access token.

- **Access**: Public
- **Request Body**:
```json
{
  "email": "admin@company.com",
  "password": "password123"
}
```
- **Response `200 OK`**:
```json
{
  "message": "Login successful",
  "access_token": "1|qWeRtYuIoP1234567890...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "System Admin",
    "email": "admin@company.com",
    "employee_id": "EMP-0001",
    "designation": "Super Administrator",
    "role": "SUPER_ADMIN",
    "created_at": "2026-08-31T05:00:00.000000Z",
    "updated_at": "2026-08-31T05:00:00.000000Z"
  }
}
```

---

### `GET /api/user`
Retrieves the profile of the currently authenticated user.

- **Access**: Authenticated
- **Response `200 OK`**:
```json
{
  "data": {
    "id": 1,
    "name": "System Admin",
    "email": "admin@company.com",
    "employee_id": "EMP-0001",
    "designation": "Super Administrator",
    "role": "SUPER_ADMIN",
    "created_at": "2026-08-31T05:00:00.000000Z",
    "updated_at": "2026-08-31T05:00:00.000000Z"
  }
}
```

---

### `POST /api/logout`
Revokes the current access token used for the request.

- **Access**: Authenticated
- **Response `200 OK`**:
```json
{
  "message": "Successfully logged out"
}
```

---

## 2. User Management

### `GET /api/users`
List and paginate users.

- **Access**: `SUPER_ADMIN`, `HR_ADMIN`
- **Query Parameters**:
  - `page` (integer, default: `1`)
  - `per_page` (integer, default: `15`)
  - `role` (string, optional) — Filter by user role. Values: `SUPER_ADMIN`, `HR_ADMIN`, `EMPLOYEE`, `ACCOUNTS`.
  - `search` (string, optional) — Search by name, email, or employee ID.
- **Response `200 OK`**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "System Admin",
      "email": "admin@company.com",
      "employee_id": "EMP-0001",
      "designation": "Super Administrator",
      "role": "SUPER_ADMIN",
      "created_at": "2026-08-31T05:00:00.000000Z",
      "updated_at": "2026-08-31T05:00:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### `POST /api/users`
Create a new user account.

- **Access**: `SUPER_ADMIN`, `HR_ADMIN`
- **Request Body**:
```json
{
  "name": "Jane Doe",
  "email": "jane.doe@company.com",
  "password": "Password123!",
  "employee_id": "EMP-0042",
  "designation": "Software Engineer",
  "role": "EMPLOYEE"
}
```
- **Response `201 Created`**: Returns the created user resource.

---

### `GET /api/users/{id}`
View user profile by ID.

- **Access**: `SUPER_ADMIN`, `HR_ADMIN`, or Self.
- **Response `200 OK`**: Returns user resource.

---

### `PUT /api/users/{id}`
Update existing user account details or role.

- **Access**: `SUPER_ADMIN`, `HR_ADMIN`
- **Request Body**:
```json
{
  "name": "Jane Smith",
  "email": "jane.smith@company.com",
  "designation": "Senior Software Engineer",
  "role": "EMPLOYEE"
}
```
- **Response `200 OK`**: Returns updated user resource.

---

### `DELETE /api/users/{id}`
Soft-deletes a user account.

- **Access**: `SUPER_ADMIN` only (cannot delete own account).
- **Response `200 OK`**:
```json
{
  "message": "User deleted successfully"
}
```

---

## 3. System Approver Settings

### `GET /api/settings`
Fetch current designated approvers configuration.

- **Access**: `SUPER_ADMIN`
- **Response `200 OK`**:
```json
{
  "data": {
    "id": 1,
    "first_approver": {
      "id": 2,
      "name": "Project Lead",
      "email": "pm@company.com",
      "employee_id": "EMP-0002",
      "designation": "Engineering Manager"
    },
    "second_approver": {
      "id": 3,
      "name": "CEO",
      "email": "ceo@company.com",
      "employee_id": "EMP-0003",
      "designation": "Chief Executive Officer"
    },
    "business_controller": {
      "id": 4,
      "name": "Controller",
      "email": "bc@company.com",
      "employee_id": "EMP-0004",
      "designation": "Business Controller"
    },
    "accounts_approver": {
      "id": 5,
      "name": "Accounts Officer",
      "email": "accounts@company.com",
      "employee_id": "EMP-0005",
      "designation": "Accounts Lead"
    },
    "hr_admin_approver": {
      "id": 6,
      "name": "HR Manager",
      "email": "hr@company.com",
      "employee_id": "EMP-0006",
      "designation": "Head of People"
    },
    "updated_by": {
      "id": 1,
      "name": "System Admin",
      "email": "admin@company.com",
      "employee_id": "EMP-0001",
      "designation": "Super Administrator"
    },
    "updated_at": "2026-08-31T05:30:00.000000Z"
  }
}
```

---

### `PUT /api/settings`
Assign user accounts to approval workflow roles.

- **Access**: `SUPER_ADMIN`
- **Request Body**:
```json
{
  "first_approver_user_id": 2,
  "second_approver_user_id": 3,
  "business_controller_user_id": 4,
  "accounts_approver_user_id": 5,
  "hr_admin_approver_user_id": 6
}
```
- **Response `200 OK`**: Returns updated settings resource.

---

## 4. Requisitions Management

### `GET /api/requisitions`
List requisitions with scoped visibility based on user role and assigned approval steps.

- **Access**: Authenticated
- **Query Parameters**:
  - `status` (string, optional: `PENDING`, `APPROVED`, `DENIED`)
  - `approval` (string, optional: `mine` or `pending`) — `mine`: only requisitions the authenticated user has already approved or denied. `pending`: only `PENDING` requisitions currently waiting at a step assigned to the authenticated user (their approval queue).
  - `submitted` (string, optional: `mine`) — Return only requisitions the authenticated user submitted.
  - `trashed` (string, optional: `only`) — `SUPER_ADMIN` only. Returns only soft-deleted requisitions. Ignored for other roles.
  - `page` (integer, default: `1`)
  - `per_page` (integer, default: `15`)
- **Default scope** (no filters): `SUPER_ADMIN` gets every non-deleted requisition. Everyone else gets requisitions they submitted, requisitions `PENDING` at a step assigned to them, and requisitions they previously approved/denied.
- **Filter rules**: `status`, `approval`, `submitted` and `trashed` only narrow the default scope (they never widen it) and can be combined. Unrecognized values of `approval`, `submitted` and `trashed` are ignored.
- **Examples**:
  - `GET /api/requisitions?approval=pending` — only requisitions waiting for my approval.
  - `GET /api/requisitions?submitted=mine` — only requisitions I submitted.
  - `GET /api/requisitions?approval=mine&status=DENIED` — requisitions I denied.
  - `GET /api/requisitions?trashed=only` — soft-deleted requisitions (`SUPER_ADMIN`).
- **Response `200 OK`**:
```json
{
  "data": [
    {
      "id": 1,
      "requisition_number": "REQ-2026-0001",
      "submitted_by": {
        "id": 10,
        "name": "John Submitter",
        "email": "john@company.com",
        "employee_id": "EMP-0010",
        "designation": "UI Developer"
      },
      "current_step": "APPROVER_1",
      "status": "PENDING",
      "total_expected_price": 2450.00,
      "items": [
        {
          "id": 1,
          "requisition_id": 1,
          "item_name": "Dell 4K Monitor",
          "description": "27-inch 4K USB-C monitor for UI design",
          "quantity": 2,
          "unit_price": 450.00,
          "total_price": 900.00,
          "created_at": "2026-08-31T06:00:00.000000Z",
          "updated_at": "2026-08-31T06:00:00.000000Z"
        },
        {
          "id": 2,
          "requisition_id": 1,
          "item_name": "Ergonomic Office Chair",
          "description": "High-back mesh ergonomic chair",
          "quantity": 2,
          "unit_price": 775.00,
          "total_price": 1550.00,
          "created_at": "2026-08-31T06:00:00.000000Z",
          "updated_at": "2026-08-31T06:00:00.000000Z"
        }
      ],
      "approvals": [],
      "created_at": "2026-08-31T06:00:00.000000Z",
      "updated_at": "2026-08-31T06:00:00.000000Z",
      "deleted_at": null
    }
  ],
  "meta": { ... }
}
```

---

### `POST /api/requisitions`
Create a new requisition with one or more line items.

- **Access**: Authenticated (non-super-admin)
- **Request Body**:
```json
{
  "items": [
    {
      "item_name": "MacBook Pro 16\"",
      "description": "M3 Max, 36GB RAM, 1TB SSD for mobile developer",
      "quantity": 1,
      "unit_price": 3499.00
    },
    {
      "item_name": "Magic Keyboard & Mouse",
      "description": "Wireless Apple accessories",
      "quantity": 1,
      "unit_price": 250.00
    }
  ]
}
```
- **Attachments (optional)**: `attachments` is optional on create. It may be omitted, `null` or empty. To attach files on create, send the request as `multipart/form-data` instead of JSON. Use form fields `items[0][item_name]`, `items[0][description]`, `items[0][quantity]`, `items[0][unit_price]`, … plus `attachments[]` for each file. Allowed types are `pdf`, `doc`, `docx`, `xls`, `xlsx`. Each file may be at most 10 MB, and a requisition can have at most 5 files.
```bash
curl -X POST "$BASE_URL/api/requisitions" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -F "items[0][item_name]=MacBook Pro 16" \
  -F "items[0][description]=M3 Max, 36GB RAM" \
  -F "items[0][quantity]=1" \
  -F "items[0][unit_price]=3499" \
  -F "attachments[]=@quote.pdf" \
  -F "attachments[]=@budget.xlsx"
```
- **CC (optional)**: `cc_contact_ids` is an array of up to 10 contact ids from `GET /api/cc-contacts`, for example `"cc_contact_ids": [3, 7]` in JSON or `cc_contact_ids[]=3` in multipart. Each contact receives an FYI email on submission and another on the final outcome (fully approved or denied). Unknown or deleted ids return `422`.
- **Response `201 Created`**: Returns the created requisition with calculated totals, initial step, `attachments` and `cc_contacts`.

---

### `GET /api/requisitions/{id}`
Retrieve detailed view of a single requisition including items and full approval audit logs.

- **Access**: Authorized (Submitter, Current Approver, any user who previously approved/denied it, or Super Admin). Soft-deleted requisitions return `404`.
- **Response `200 OK`**: Returns the full requisition resource with `items`, `attachments` and `approvals` arrays. Each attachment looks like this:
```json
{
  "id": 1,
  "requisition_id": 12,
  "original_name": "quote.pdf",
  "mime_type": "application/pdf",
  "size": 102400,
  "uploaded_by": { "id": 5, "name": "Jane Doe" },
  "download_url": "http://127.0.0.1:8000/api/requisitions/12/attachments/1",
  "created_at": "2026-10-05T10:00:00+00:00"
}
```

---

### `PUT /api/requisitions/{id}`
Update requisition line items and recalculate totals.

- **Access**: Submitter only (allowed only before any approvals have occurred).
- **Request Body**:
```json
{
  "items": [
    {
      "item_name": "MacBook Pro 16\" Space Black",
      "description": "Updated spec: 48GB RAM, 1TB SSD",
      "quantity": 1,
      "unit_price": 3899.00
    }
  ]
}
```
- **Response `200 OK`**: Returns updated requisition resource.

---

### `DELETE /api/requisitions/{id}`
Soft-delete a pending requisition.

- **Access**: Submitter only (must be in `PENDING` status).
- **Response `200 OK`**:
```json
{
  "message": "Requisition deleted successfully"
}
```

---

## 4a. Requisition Attachments

Files are stored privately and are served only through the authorized download endpoint. Allowed types are `pdf`, `doc`, `docx`, `xls`, `xlsx`. Each file may be at most 10 MB, and a requisition can have at most 5 attachments in total.

### `POST /api/requisitions/{id}/attachments`
Upload one or more files to an existing requisition.

- **Access**: Submitter only, and only before any approval has occurred (same rule as `PUT`).
- **Request**: `multipart/form-data` with one or more `attachments[]` files.
- **Response `201 Created`**: `{"data": [ ...attachment objects ]}` for the newly uploaded files.
- **Response `422`**: The type or size is invalid, or the upload would exceed 5 attachments on the requisition.

### `GET /api/requisitions/{id}/attachments/{attachmentId}`
Download the file under its original name.

- **Access**: Same as viewing the requisition (submitter, current approver, past approvers, Super Admin).
- **Response `200 OK`**: Binary file (`Content-Disposition: attachment`). Returns `404` if the attachment does not belong to the requisition.

### `DELETE /api/requisitions/{id}/attachments/{attachmentId}`
Delete an attachment and its stored file.

- **Access**: Submitter only, and only before any approval has occurred.
- **Response `200 OK`**:
```json
{
  "message": "Attachment deleted successfully"
}
```

---

## 4b. CC Contacts

A directory of people who can be CC'd on requisition emails. They do not need to be system users. Any authenticated user can list contacts, for example to fill a CC picker. Only `SUPER_ADMIN` can create, update or delete them.

### `GET /api/cc-contacts`
List all active contacts ordered by name (not paginated).
```json
{
  "data": [
    { "id": 3, "name": "Head of Ops", "designation": "Director", "email": "ops@example.com" }
  ]
}
```

### `POST /api/cc-contacts`
- **Access**: `SUPER_ADMIN`
- **Request Body**:
```json
{ "name": "Head of Ops", "designation": "Director", "email": "ops@example.com" }
```
`designation` is optional. `email` must be unique among active contacts.
- **Response `201 Created`**: The contact.

### `GET /api/cc-contacts/{id}`
Returns a single contact.

### `PUT /api/cc-contacts/{id}`
- **Access**: `SUPER_ADMIN`. Any of `name`, `designation` or `email` can be sent.

### `DELETE /api/cc-contacts/{id}`
- **Access**: `SUPER_ADMIN`. Soft-deletes the contact. It can no longer be selected, but it still appears on past requisitions.
- **Response `200 OK`**: `{"message": "Contact deleted successfully"}`

---

## 5. Approval & Denial Endpoints

### `POST /api/requisitions/{id}/approve`
Approve the requisition at its current stage, advancing the workflow to the next step (or completing it).

- **Access**: User assigned to `requisition.current_step` in `SystemSettings`.
- **Request Body**:
```json
{
  "remarks": "Approved. Specifications and budget allocation verified."
}
```
- **Response `200 OK`**: Returns updated requisition with advanced `current_step` or `status: "APPROVED"`.

---

### `POST /api/requisitions/{id}/deny`
Deny and immediately terminate the requisition workflow. A reason is **required** when denying.

- **Access**: User assigned to `requisition.current_step` in `SystemSettings`.
- **Request Body**:
```json
{
  "reason": "Rejected: Exceeds quarterly department equipment budget."
}
```
*(Note: `remarks` is also accepted in place of `reason` for backward compatibility. The request will fail with 422 if neither is provided.)*
- **Response `200 OK`**: Returns updated requisition with `status: "DENIED"` and `current_step: null`.

---

## 6. Common Error Responses

### 401 Unauthorized (missing/invalid token)
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden (policy denial)
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Requisition] 999."
}
```

### 422 Validation Error (login)
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

### 422 Validation Error (form request)
```json
{
  "message": "The reason field is required when remarks is not present.",
  "errors": {
    "reason": ["A reason is required when denying a requisition."],
    "remarks": ["A reason is required when denying a requisition."]
  }
}
```

---

## 7. Email Notifications

1. **Pending Approval** — Sent to the next approver in the chain when:
   - A new requisition is submitted (`POST /api/requisitions`)
   - A step is approved and the workflow advances (`POST /api/requisitions/{id}/approve`)

2. **Step Approved** — Sent to the original submitter when any approver approves their step. Includes:
   - The approver's name
   - Optional remarks
   - Whether this is a partial or final (fully approved) approval

3. **Denied** — Sent to the original submitter when any approver denies. Includes:
   - The denier's name
   - The mandatory reason for denial

4. **CC (FYI)**: sent to every contact in the requisition's `cc_contacts`:
   - on submission
   - on the final outcome: fully approved, or denied (with the denier and the reason)

   No email is sent to CC contacts for intermediate approval steps.

Note: All emails are queued (`Mail::queue`) for async processing.
