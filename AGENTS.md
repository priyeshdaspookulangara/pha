# Agent Instructions

This document provides instructions for AI agents on how to interact with and modify the Pharmacy Management System project.

## Project Overview

The project is composed of two main parts:
1.  A **PHP backend API** located in the `/api` directory.
2.  A **jQuery-based frontend** located in `index.html` and the `/js` and `/css` directories.

The backend and frontend communicate via AJAX calls. All data is exchanged in JSON format.

## Development Workflow

### Backend Modifications

-   **API Endpoints**: Each endpoint is a separate PHP file in the `/api` directory. To add a new endpoint, create a new file. To modify an existing one, edit the corresponding file.
-   **Database**: The database schema is defined in `database.sql`. If you make changes to the database structure, you **must** update this file to reflect those changes. All database interaction in the PHP code should use prepared statements (`$conn->prepare()`) to prevent SQL injection.
-   **Transactions**: For operations that involve multiple database writes (e.g., creating an order and updating inventory), you **must** use database transactions (`$conn->begin_transaction()`, `$conn->commit()`, `$conn->rollback()`) to ensure data integrity.

### Frontend Modifications

-   **HTML Structure**: The main HTML structure is in `index.html`. The application uses a single-page model, where different sections are shown/hidden. Each section is a `<section>` element with a unique ID and the class `.page`.
-   **JavaScript Logic**: All frontend logic is contained in `js/app.js`.
    -   **Routing**: The application uses a simple hash-based router. To add a new page, you must:
        1.  Add the HTML `<section>` to `index.html`.
        2.  Add a link to the navigation bar in `index.html`.
        3.  Add a `case` to the `switch` statement inside the `router()` function in `js/app.js`. This case should call a function to load the data for your new page.
    -   **API Calls**: Use the `apiCall(method, url, data)` helper function for all AJAX requests to the backend.
    -   **Code Organization**: When adding logic for a new page, group all related functions and event handlers together and use comments to label the section (e.g., `// --- NEW SALE PAGE LOGIC ---`).
-   **Purchase Orders**: The purchase order module is a core part of the system. It involves creating purchase orders, and then receiving shipments against them, which updates the main inventory. Be careful when modifying this workflow. The `api/receive_shipment.php` script is particularly critical.
-   **Accounting**: The accounting module is tied to customer transactions and purchase order payments. When a payment is made or received, an entry should be logged in the `accounting_ledger` table. Ensure that any changes to payment-related APIs (`transactions.php`, `pay_purchase_order.php`) maintain this link.

## Testing

This project does not currently have an automated test suite. After making any changes, you **must** manually verify them by:
1.  Opening `index.html` in a browser.
2.  Navigating to the page you modified.
3.  Testing the functionality to ensure it works as expected and has not introduced any regressions in other parts of the application.
4.  Checking the browser's developer console for any errors.
5.  Checking the web server's error logs if an API call fails unexpectedly.
