# FarsiFahr API Documentation v1

Base URL: `https://farsifahr.com/api/v1`

## Authentication
Most endpoints require a Bearer Token in the `Authorization` header.
Header: `Authorization: Bearer <your_token>`

### 1. Login
- **URL:** `/auth/login`
- **Method:** `POST`
- **Body (JSON):**
  ```json
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```
- **Response:** User object including `api_token`.

### 2. Register
- **URL:** `/auth/register`
- **Method:** `POST`
- **Body (JSON):**
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "Password123",
    "password_confirm": "Password123"
  }
  ```

### 3. Forgot Password
- **URL:** `/auth/forgot-password`
- **Method:** `POST`
- **Body (JSON):** `{"email": "user@example.com"}`

### 4. Get Profile (Me)
- **URL:** `/auth/me`
- **Method:** `GET`
- **Auth Required:** Yes

---

## Dashboard
### 1. Stats
- **URL:** `/dashboard/stats`
- **Method:** `GET`
- **Auth Required:** Yes

---

## Practice
### 1. Categories
- **URL:** `/practice/categories`
- **Method:** `GET`
- **Auth Required:** Yes

### 2. Questions by Category
- **URL:** `/practice/questions?category_id=X`
- **Method:** `GET`
- **Auth Required:** Yes

### 3. Question Details
- **URL:** `/practice/question-details?question_id=X`
- **Method:** `GET`
- **Auth Required:** Yes

### 4. Submit Answer
- **URL:** `/practice/submit-answer`
- **Method:** `POST`
- **Auth Required:** Yes
- **Body (JSON):**
  ```json
  {
    "question_id": 123,
    "is_correct": true
  }
  ```

---

## Exam Simulator
### 1. Generate Exam
- **URL:** `/exam/simulator`
- **Method:** `GET`
- **Auth Required:** Yes (VIP)

### 2. Submit Exam History
- **URL:** `/exam/submit`
- **Method:** `POST`
- **Auth Required:** Yes

---

## Vocabulary
### 1. Categories
- **URL:** `/vocabulary/categories`
- **Method:** `GET`
- **Auth Required:** Yes

### 2. Words by Category
- **URL:** `/vocabulary/words?category_id=X`
- **Method:** `GET`
- **Auth Required:** Yes

### 3. Save/Update Word
- **URL:** `/vocabulary/save`
- **Method:** `POST`
- **Auth Required:** Yes

---

## Subscriptions
### 1. Plans
- **URL:** `/subscriptions/plans`
- **Method:** `GET`
- **Auth Required:** Yes

### 2. Current Status
- **URL:** `/subscriptions/status`
- **Method:** `GET`
- **Auth Required:** Yes
