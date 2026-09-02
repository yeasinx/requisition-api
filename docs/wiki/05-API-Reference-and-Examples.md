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
  "token": "1|qWeRtYuIoP1234567890...",
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
  "message": "Logged out successfully"
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
  - `page` (integer, default: `1`)
  - `per_page` (integer, default: `15`)
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
      "updated_at": "2026-08-31T06:00:00.000000Z"
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
- **Response `201 Created`**: Returns created requisition with calculated totals and initial step.

---

### `GET /api/requisitions/{id}`
Retrieve detailed view of a single requisition including items and full approval audit logs.

- **Access**: Authorized (Submitter, Current Approver, or Super Admin)
- **Response `200 OK`**: Returns full requisition resource with `items` and `approvals` arrays.

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
Deny and immediately terminate the requisition workflow.

- **Access**: User assigned to `requisition.current_step` in `SystemSettings`.
- **Request Body**:
```json
{
  "remarks": "Rejected: Exceeds quarterly department equipment budget."
}
```
- **Response `200 OK`**: Returns updated requisition with `status: "DENIED"` and `current_step: null`.
