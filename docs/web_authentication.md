# Web Authentication

This document details the Web-based Authentication controller found in `routes/web.php`.
These routes return HTML views or redirect, unlike the API endpoints.

## AuthController (Web)

### Show Registration Form
Display the user registration form.

- **URL**: `/register`
- **Method**: `GET`
- **View**: `auth.register`
- **Action**: `create`

### Handle Registration
Process the registration form submission.

- **URL**: `/register`
- **Method**: `POST`
- **Action**: `store`
- **Body Parameters (Form Data)**:
  - `full_name`: string
  - `email`: string
  - `phone_number`: string
  - `password`: string (min 8)
  - `password_confirmation`: string
  - `role`: string (ADMIN, USER, SUPERVISOR, SUPPORT)

- **Behavior**: Same validation as API. On success, logs the user in and redirects to `/` with a success message.
