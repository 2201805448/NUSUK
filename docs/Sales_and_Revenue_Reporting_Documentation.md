# Sales and Revenue Reporting Documentation

## Overview
This document outlines the implementation of the **Sales and Revenue Reporting** feature, which enables administrators to view sales operations and revenue generated from bookings within a specific time period.

## Changes Implemented

### 1. API Changes
**Endpoint:** `GET /api/reports/revenue`

- **Access Level:** Admin only (protected by `auth:sanctum` and role middleware).
- **Parameters:**
  - `start_date` (Required, Date format YYYY-MM-DD): The beginning of the reporting period.
  - `end_date` (Required, Date format YYYY-MM-DD): The end of the reporting period.

### 2. Controller Logic
**File:** `app/Http/Controllers/Api/ReportController.php`

A new method `revenueReport` was added to handle the reporting logic:

1.  **Validation:** Ensures `start_date` and `end_date` are provided and valid.
2.  **Querying:** Fetches all `Payment` records within the specified date range, eager loading related `Booking` and `User` data.
3.  **Aggregation:**
    - **Total Revenue:** Sum of `amount` for all payments with status `PAID`.
    - **Total Transactions:** Count of all attempted payments (PAID, PENDING, FAILED).
    - **Counts:** Breakdown of successful, failed, and pending transactions.
4.  **Transformation:** Maps the raw data into a structured JSON response containing a summary and a detailed list of records.

### 3. Response Structure

The API returns a JSON object with the following structure:

```json
{
    "meta": {
        "start_date": "2026-01-01",
        "end_date": "2026-01-31",
        "generated_at": "2026-01-22 12:00:00"
    },
    "summary": {
        "total_revenue": 50000.00,
        "total_transactions": 25,
        "successful_transactions": 20,
        "failed_transactions": 2,
        "pending_transactions": 3
    },
    "records": [
        {
            "payment_id": 101,
            "booking_ref": "BK-2026-001",
            "booking_id": 55,
            "user_name": "Pilgrim Name",
            "amount": 2500.00,
            "payment_date": "2026-01-15",
            "pay_method": "CARD",
            "status": "PAID"
        },
        ...
    ]
}
```

## Verification
- Validated using a custom test script `test_revenue_report.php`.
- Verified that:
    - Only payments within the `start_date` and `end_date` are included.
    - `total_revenue` correctly sums only `PAID` payments.
    - Transaction counts match the database records.
