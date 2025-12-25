# Authentication Feature

## Authentication

The API uses Laravel Sanctum for authentication.

### Base URL
All API endpoints are prefixed with `/api`.

### Endpoints

#### 1. Register User
Create a new user account and receive an authentication token.

- **URL**: `/register`
- **Method**: `POST`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `full_name` | string | Yes | Full name of the user (max 150 chars) |
  | `email` | string | Yes | Valid email address (must be unique) |
  | `phone_number` | string | Yes | Phone number |
  | `password` | string | Yes | Password (min 8 chars) |
  | `role` | string | Yes | One of: `ADMIN`, `USER`, `SUPERVISOR`, `SUPPORT` |

- **Success Response (201 Created)**:
  ```json
  {
    "message": "User created successfully",
    "user": {
      "user_id": 1,
      "full_name": "John Doe",
      "email": "john@example.com",
      "role": "USER",
      "account_status": "ACTIVE",
      ...
    },
    "token": "1|laravel_sanctum_token_string..."
  }
  ```

- **Error Response (422 Unprocessable Entity)**:
  ```json
  {
    "message": "The email field must be a valid email address.",
    "errors": {
      "email": [
        "The email field must be a valid email address."
      ]
    }
  }
  ```

#### 2. Login
Authenticate an existing user and receive a new token.

- **URL**: `/login`
- **Method**: `POST`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
- **Body Parameters**:
  | Parameter | Type | Required | Description |
  |-----------|------|----------|-------------|
  | `email` | string | Yes | User's email |
  | `password` | string | Yes | User's password |

- **Success Response (200 OK)**:
  ```json
  {
    "message": "Login successful",
    "user": { ... },
    "token": "2|new_token_string..."
  }
  ```

- **Error Response (401 Unauthorized)**:
  ```json
  {
    "message": "Invalid login credentials"
  }
  ```

#### 3. Logout
Revoke the current access token.

- **URL**: `/logout`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer <your_token>`
  - `Accept: application/json`

- **Success Response (200 OK)**:
  ```json
  {
    "message": "Logged out successfully"
  }
  ```

#### 4. Get User Profile
Retrieve the currently authenticated user's details.

- **URL**: `/user`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer <your_token>`
  - `Accept: application/json`

- **Success Response (200 OK)**:
  Returns the user object.
