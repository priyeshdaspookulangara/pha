# Hospital Pharmacy Management System

This project is a comprehensive, web-based pharmacy management system designed for a modern hospital. It consists of a PHP/MySQLi backend API and a jQuery/AJAX frontend.

## Features

-   **RESTful API**: A complete backend API to handle all pharmacy operations.
-   **Single-Page Frontend**: A responsive user interface built with HTML, CSS, and jQuery.
-   **Product and Inventory Management**: Full CRUD operations for products and batch-wise inventory tracking.
-   **Order Processing**: Handling of digital prescriptions and direct walk-in sales.
-   **Patient Management**: CRUD operations for patient records.
-   **Billing and Transactions**: Generation of invoices and recording of payments.
-   **Reporting**: Dynamic reports on sales, stock levels, and expiring products.
-   **Purchase Order Management**: A full module to manage distributors, create manual or automated purchase orders, and receive shipments to update inventory.
-   **Accounting**: A simple ledger system to track revenue from sales and expenses from purchase orders.

## Project Structure

-   `/api`: Contains all the PHP files for the backend API endpoints.
-   `/config`: Contains the database configuration file.
-   `/css`: Contains the stylesheet for the frontend.
-   `/includes`: Contains the database connection script.
-   `/js`: Contains the JavaScript application logic for the frontend.
-   `index.html`: The main entry point for the frontend application.
-   `database.sql`: The database schema.

## Setup and Installation

### Backend Setup

1.  **Database:**
    -   Create a new MySQL database named `pharmacy_db`.
    -   Import the `database.sql` file into your MySQL database. This will create all the necessary tables.

2.  **Configuration:**
    -   Open the `config/config.php` file.
    -   Update the `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_NAME` constants with your database credentials.

3.  **Web Server:**
    -   Place the project files in the root directory of a web server (like Apache or Nginx) that has PHP installed.

### Frontend Usage

1.  **Access:**
    -   Ensure your web server is running.
    -   Open a web browser and navigate to the `index.html` file (e.g., `http://localhost/path/to/project/index.html`).

2.  **Using the Application:**
    -   The application is a single-page app. Use the navigation bar at the top to switch between different sections:
        -   **Dashboard**: The landing page.
        -   **Products**: Manage products (add, edit, delete).
        -   **Patients**: Manage patient records.
        -   **Inventory**: View inventory and add new stock.
        -   **Orders**: View a list of all sales and prescriptions.
        -   **New Sale**: Create a new direct sale to a customer.
        -   **New Prescription**: Create a new prescription order.
        -   **Distributors**: Manage suppliers.
        -   **Purchase Orders**: View purchase orders sent to distributors.
        -   **Reorder Suggestions**: View and approve automatically generated purchase orders for low-stock items.
        -   **New Purchase Order**: Manually create a new purchase order.
        -   **Accounting**: View the financial ledger with revenues and expenses.

## API Documentation

The API is RESTful and uses JSON for all responses. Here is a summary of the available endpoints.

-   **Authentication**:
    -   `POST /api/register.php`: Register a new user.
    -   `POST /api/login.php`: Log in a user.

-   **Products**:
    -   `GET /api/products.php`: Get all products.
    -   `GET /api/products.php?id={id}`: Get a single product.
    -   `POST /api/products.php`: Create a new product.
    -   `PUT /api/products.php?id={id}`: Update a product.
    -   `DELETE /api/products.php?id={id}`: Delete a product.

-   **Patients**:
    -   (Follows the same CRUD pattern as Products)

-   **Inventory**:
    -   `GET /api/inventory.php`: Get all inventory items.
    -   `POST /api/inventory.php`: Add a new stock batch.
    -   `GET /api/inventory_alerts.php`: Get low-stock alerts.

-   **Orders**:
    -   `POST /api/prescriptions.php`: Create a new order from a prescription.
    -   `POST /api/sales.php`: Create a new direct sale order.
    -   `GET /api/orders.php`: Get all orders.
    -   `GET /api/orders.php?id={id}`: Get a single order with its items.
    -   `PUT /api/orders.php?id={id}`: Update an order's status.

-   **Billing**:
    -   `POST /api/invoices.php`: Create an invoice for an order.
    -   `POST /api/transactions.php`: Record a payment for an invoice.

-   **Reports**:
    -   `GET /api/reports.php?type=sales&start_date=...&end_date=...`
    -   `GET /api/reports.php?type=stock`
    -   `GET /api/reports.php?type=expiring&days=...`

-   **Distributors & Purchase Orders**:
    -   `GET, POST, PUT, DELETE /api/distributors.php`: CRUD for distributors.
    -   `GET, POST, PUT /api/purchase_orders.php`: CRUD for purchase orders.
    -   `POST /api/receive_shipment.php`: Receive a shipment against a purchase order.
    -   `POST /api/automated_reorder.php`: Run the automated reordering logic.

-   **Accounting**:
    -   `GET /api/accounting.php`: Get the full ledger and financial summary.
    -   `POST /api/pay_purchase_order.php`: Mark a PO as paid and log the expense.
