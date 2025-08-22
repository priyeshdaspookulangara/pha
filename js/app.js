$(document).ready(function() {
    // --- API HELPER ---
    function apiCall(method, url, data = null) {
        return $.ajax({
            method: method,
            url: `/api/${url}`, // Prepends /api/ to all requests
            contentType: 'application/json',
            data: data ? JSON.stringify(data) : null,
        });
    }

    // --- PRODUCTS PAGE LOGIC ---
    function loadProducts() {
        apiCall('GET', 'products.php').done(function(products) {
            const tableBody = $('#products-table tbody');
            tableBody.empty(); // Clear existing rows
            products.forEach(function(product) {
                tableBody.append(`
                    <tr>
                        <td>${product.id}</td>
                        <td>${product.name}</td>
                        <td>${product.category}</td>
                        <td>${product.reorder_level}</td>
                        <td>
                            <button class="btn-edit-product" data-id="${product.id}">Edit</button>
                            <button class="btn-delete-product" data-id="${product.id}">Delete</button>
                        </td>
                    </tr>
                `);
            });
        });
    }

    $('#btn-show-add-product-form').on('click', function() {
        $('#product-form').removeClass('hidden');
        $('#product-form')[0].reset();
        $('#product-id').val('');
    });

    $('#btn-cancel-edit').on('click', function() {
        $('#product-form').addClass('hidden');
    });

    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        const productId = $('#product-id').val();
        const productData = {
            name: $('#product-name').val(),
            description: $('#product-description').val(),
            category: $('#product-category').val(),
            reorder_level: parseInt($('#product-reorder-level').val(), 10)
        };

        let method = 'POST';
        let url = 'products.php';
        if (productId) {
            method = 'PUT';
            url += `?id=${productId}`;
        }

        apiCall(method, url, productData).done(function(response) {
            alert(response.message);
            $('#product-form').addClass('hidden');
            loadProducts();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    });

    $('#products-table').on('click', '.btn-edit-product', function() {
        const productId = $(this).data('id');
        apiCall('GET', `products.php?id=${productId}`).done(function(product) {
            $('#product-id').val(product.id);
            $('#product-name').val(product.name);
            $('#product-description').val(product.description);
            $('#product-category').val(product.category);
            $('#product-reorder-level').val(product.reorder_level);
            $('#product-form').removeClass('hidden');
        });
    });

    $('#products-table').on('click', '.btn-delete-product', function() {
        const productId = $(this).data('id');
        if (confirm('Are you sure you want to delete this product?')) {
            apiCall('DELETE', `products.php?id=${productId}`).done(function(response) {
                alert(response.message);
                loadProducts();
            }).fail(function(xhr) {
                alert('Error: ' + xhr.responseJSON.message);
            });
        }
    });

    // --- PATIENTS PAGE LOGIC ---
    function loadPatients() {
        apiCall('GET', 'patients.php').done(function(patients) {
            const tableBody = $('#patients-table tbody');
            tableBody.empty();
            patients.forEach(function(patient) {
                tableBody.append(`
                    <tr>
                        <td>${patient.id}</td>
                        <td>${patient.name}</td>
                        <td>${patient.contact_number}</td>
                        <td>${patient.address}</td>
                        <td>
                            <button class="btn-edit-patient" data-id="${patient.id}">Edit</button>
                            <button class="btn-delete-patient" data-id="${patient.id}">Delete</button>
                        </td>
                    </tr>
                `);
            });
        });
    }

    $('#btn-show-add-patient-form').on('click', function() {
        $('#patient-form').removeClass('hidden');
        $('#patient-form')[0].reset();
        $('#patient-id').val('');
    });

    $('#btn-cancel-patient-edit').on('click', function() {
        $('#patient-form').addClass('hidden');
    });

    $('#patient-form').on('submit', function(e) {
        e.preventDefault();
        const patientId = $('#patient-id').val();
        const patientData = {
            name: $('#patient-name').val(),
            contact_number: $('#patient-contact').val(),
            address: $('#patient-address').val()
        };

        let method = 'POST';
        let url = 'patients.php';
        if (patientId) {
            method = 'PUT';
            url += `?id=${patientId}`;
        }

        apiCall(method, url, patientData).done(function(response) {
            alert(response.message);
            $('#patient-form').addClass('hidden');
            loadPatients();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    });

    $('#patients-table').on('click', '.btn-edit-patient', function() {
        const patientId = $(this).data('id');
        apiCall('GET', `patients.php?id=${patientId}`).done(function(patient) {
            $('#patient-id').val(patient.id);
            $('#patient-name').val(patient.name);
            $('#patient-contact').val(patient.contact_number);
            $('#patient-address').val(patient.address);
            $('#patient-form').removeClass('hidden');
        });
    });

    $('#patients-table').on('click', '.btn-delete-patient', function() {
        const patientId = $(this).data('id');
        if (confirm('Are you sure you want to delete this patient?')) {
            apiCall('DELETE', `patients.php?id=${patientId}`).done(function(response) {
                alert(response.message);
                loadPatients();
            }).fail(function(xhr) {
                alert('Error: ' + xhr.responseJSON.message);
            });
        }
    });

    // --- INVENTORY PAGE LOGIC ---
    function loadInventory() {
        apiCall('GET', 'inventory.php').done(function(inventory) {
            const tableBody = $('#inventory-table tbody');
            tableBody.empty();
            inventory.forEach(function(item) {
                tableBody.append(`
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.batch_number}</td>
                        <td>${item.quantity}</td>
                        <td>${item.manufacturing_date}</td>
                        <td>${item.expiry_date}</td>
                        <td>${item.price}</td>
                    </tr>
                `);
            });
        });
    }

    function loadProductsForSelect() {
        apiCall('GET', 'products.php').done(function(products) {
            const select = $('#inventory-product-id');
            select.empty();
            select.append('<option value="" disabled selected>Select a Product</option>');
            products.forEach(function(product) {
                select.append(`<option value="${product.id}">${product.name}</option>`);
            });
        });
    }

    $('#btn-show-add-stock-form').on('click', function() {
        $('#inventory-form').removeClass('hidden');
        $('#inventory-form')[0].reset();
        loadProductsForSelect();
    });

    $('#btn-cancel-stock-add').on('click', function() {
        $('#inventory-form').addClass('hidden');
    });

    $('#inventory-form').on('submit', function(e) {
        e.preventDefault();
        const stockData = {
            product_id: parseInt($('#inventory-product-id').val(), 10),
            batch_number: $('#inventory-batch-number').val(),
            quantity: parseInt($('#inventory-quantity').val(), 10),
            manufacturing_date: $('#inventory-mfg-date').val(),
            expiry_date: $('#inventory-expiry-date').val(),
            price: parseFloat($('#inventory-price').val())
        };

        apiCall('POST', 'inventory.php', stockData).done(function(response) {
            alert(response.message);
            $('#inventory-form').addClass('hidden');
            loadInventory();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    });

    // --- NEW SALE PAGE LOGIC ---
    let saleCart = [];

    function loadNewSalePage() {
        apiCall('GET', 'products.php').done(function(products) {
            const select = $('#new-sale-product-id');
            select.empty();
            select.append('<option value="" disabled selected>Select a Product</option>');
            products.forEach(function(product) {
                select.append(`<option value="${product.id}" data-name="${product.name}">${product.name}</option>`);
            });
        });
        saleCart = [];
        renderCart();
    }

    function renderCart() {
        const tableBody = $('#sale-cart-table tbody');
        tableBody.empty();
        saleCart.forEach(function(item, index) {
            tableBody.append(`
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td><button class="btn-remove-from-cart" data-index="${index}">Remove</button></td>
                </tr>
            `);
        });
    }

    $('#btn-add-to-cart').on('click', function() {
        const productId = $('#new-sale-product-id').val();
        const productName = $('#new-sale-product-id').find(':selected').data('name');
        const quantity = parseInt($('#new-sale-quantity').val(), 10);

        if (productId && quantity > 0) {
            saleCart.push({
                product_id: parseInt(productId, 10),
                product_name: productName,
                quantity: quantity
            });
            renderCart();
            $('#new-sale-form')[0].reset();
        } else {
            alert('Please select a product and enter a valid quantity.');
        }
    });

    $('#sale-cart-table').on('click', '.btn-remove-from-cart', function() {
        const indexToRemove = $(this).data('index');
        saleCart.splice(indexToRemove, 1);
        renderCart();
    });

    $('#btn-submit-sale').on('click', function() {
        if (saleCart.length === 0) {
            alert('Cannot submit an empty sale.');
            return;
        }

        const saleData = {
            products: saleCart.map(item => ({ product_id: item.product_id, quantity: item.quantity }))
        };

        apiCall('POST', 'sales.php', saleData).done(function(response) {
            alert(response.message);
            window.location.hash = '#orders';
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    });

    // --- ORDERS PAGE LOGIC ---
    function loadOrders() {
        apiCall('GET', 'orders.php').done(function(orders) {
            const tableBody = $('#orders-table tbody');
            tableBody.empty();
            orders.forEach(function(order) {
                tableBody.append(`
                    <tr>
                        <td>${order.id}</td>
                        <td>${order.order_type}</td>
                        <td>${new Date(order.created_at).toLocaleDateString()}</td>
                        <td>${order.total_amount}</td>
                        <td>${order.status}</td>
                        <td>
                            <button class="btn-view-order" data-id="${order.id}">View Details</button>
                        </td>
                    </tr>
                `);
            });
        });
    }

    $('#orders-table').on('click', '.btn-view-order', function() {
        const orderId = $(this).data('id');
        apiCall('GET', `orders.php?id=${orderId}`).done(function(order) {
            // Store the current order ID on the details view for other buttons to use
            $('#order-details-view').data('current-order-id', order.id);
            $('#details-order-id').text(order.id);
            $('#details-order-status').text(order.status);
            $('#details-order-total').text(order.total_amount);

            const itemsTableBody = $('#order-items-table tbody');
            itemsTableBody.empty();
            order.items.forEach(function(item) {
                itemsTableBody.append(`
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.price_per_unit}</td>
                        <td>${item.total_price}</td>
                    </tr>
                `);
            });

            $('#order-details-view').removeClass('hidden');
        });
    });

    $('#btn-close-order-details').on('click', function() {
        $('#order-details-view').addClass('hidden');
    });

    function updateOrderStatus(status) {
        const orderId = $('#order-details-view').data('current-order-id');
        if (!orderId) return;

        const confirmationText = status === 'cancelled'
            ? 'Are you sure you want to cancel this order? This will return items to stock.'
            : 'Are you sure you want to mark this order as completed?';

        if (confirm(confirmationText)) {
            apiCall('PUT', `orders.php?id=${orderId}`, { status: status }).done(function(response) {
                alert(response.message);
                $('#order-details-view').addClass('hidden');
                loadOrders();
            }).fail(function(xhr) {
                alert('Error: ' + xhr.responseJSON.message);
            });
        }
    }

    $('#btn-mark-completed').on('click', function() {
        updateOrderStatus('completed');
    });

    $('#btn-cancel-order').on('click', function() {
        updateOrderStatus('cancelled');
    });

    $('#btn-print-bill').on('click', function() {
        const orderId = $('#order-details-view').data('current-order-id');
        if (!orderId) return;

        // First, ensure an invoice exists.
        apiCall('POST', 'invoices.php', { order_id: orderId }).always(function() {
            // Then, fetch the full order details to print.
            apiCall('GET', `orders.php?id=${orderId}`).done(function(order) {
                let printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Print Bill</title>');
                printWindow.document.write('<style>body{font-family:monospace;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:8px;}</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(`<h1>Invoice for Order #${order.id}</h1>`);
                printWindow.document.write(`<p>Date: ${new Date(order.created_at).toLocaleString()}</p>`);
                printWindow.document.write(`<p>Status: ${order.status}</p>`);
                printWindow.document.write('<h3>Items:</h3>');
                printWindow.document.write('<table><thead><tr><th>Product</th><th>Qty</th><th>Price/Unit</th><th>Total</th></tr></thead><tbody>');
                order.items.forEach(item => {
                    printWindow.document.write(`<tr><td>${item.product_name}</td><td>${item.quantity}</td><td>${item.price_per_unit}</td><td>${item.total_price}</td></tr>`);
                });
                printWindow.document.write('</tbody></table>');
                printWindow.document.write(`<h2>Total Amount: ${order.total_amount}</h2>`);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
        });
    });


    // --- NEW PRESCRIPTION PAGE LOGIC ---
    let prescriptionCart = [];

    function loadNewPrescriptionPage() {
        // Fetch doctors, patients, and products
        const doctorsCall = apiCall('GET', 'doctors.php');
        const patientsCall = apiCall('GET', 'patients.php');
        const productsCall = apiCall('GET', 'products.php');

        $.when(doctorsCall, patientsCall, productsCall).done(function(doctorsRes, patientsRes, productsRes) {
            const doctors = doctorsRes[0];
            const patients = patientsRes[0];
            const products = productsRes[0];

            const doctorSelect = $('#new-prescription-doctor-id');
            doctorSelect.empty().append('<option value="" disabled selected>Select Doctor</option>');
            doctors.forEach(doc => doctorSelect.append(`<option value="${doc.id}">${doc.name}</option>`));

            const patientSelect = $('#new-prescription-patient-id');
            patientSelect.empty().append('<option value="" disabled selected>Select Patient</option>');
            patients.forEach(p => patientSelect.append(`<option value="${p.id}">${p.name}</option>`));

            const productSelect = $('#new-prescription-product-id');
            productSelect.empty().append('<option value="" disabled selected>Select Product</option>');
            products.forEach(p => productSelect.append(`<option value="${p.id}" data-name="${p.name}">${p.name}</option>`));
        });

        // Set current date
        $('#new-prescription-date').val(new Date().toISOString().slice(0, 10));

        // Reset cart
        prescriptionCart = [];
        renderPrescriptionCart();
    }

    function renderPrescriptionCart() {
        const tableBody = $('#prescription-cart-table tbody');
        tableBody.empty();
        prescriptionCart.forEach(function(item, index) {
            tableBody.append(`
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td><button class="btn-remove-from-prescription-cart" data-index="${index}">Remove</button></td>
                </tr>
            `);
        });
    }

    $('#btn-add-to-prescription-cart').on('click', function() {
        const productId = $('#new-prescription-product-id').val();
        const productName = $('#new-prescription-product-id').find(':selected').data('name');
        const quantity = parseInt($('#new-prescription-quantity').val(), 10);

        if (productId && quantity > 0) {
            prescriptionCart.push({
                product_id: parseInt(productId, 10),
                product_name: productName,
                quantity: quantity
            });
            renderPrescriptionCart();
            $('#new-prescription-product-id').val('');
            $('#new-prescription-quantity').val('');
        } else {
            alert('Please select a product and enter a valid quantity.');
        }
    });

    $('#prescription-cart-table').on('click', '.btn-remove-from-prescription-cart', function() {
        const indexToRemove = $(this).data('index');
        prescriptionCart.splice(indexToRemove, 1);
        renderPrescriptionCart();
    });

    $('#btn-submit-prescription').on('click', function() {
        const prescriptionData = {
            doctor_id: parseInt($('#new-prescription-doctor-id').val(), 10),
            patient_id: parseInt($('#new-prescription-patient-id').val(), 10),
            prescription_date: $('#new-prescription-date').val(),
            products: prescriptionCart.map(item => ({ product_id: item.product_id, quantity: item.quantity }))
        };

        if (!prescriptionData.doctor_id || !prescriptionData.patient_id || !prescriptionData.prescription_date || prescriptionData.products.length === 0) {
            alert('Please fill out all fields and add at least one product.');
            return;
        }

        apiCall('POST', 'prescriptions.php', prescriptionData).done(function(response) {
            alert(response.message);
            window.location.hash = '#orders';
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    });


    // --- ROUTER ---
    function router() {
        const hash = window.location.hash || '#dashboard';

        $('.page').hide();
        $(hash).show();

        $('nav a').removeClass('active');
        $(`nav a[href="${hash}"]`).addClass('active');

        // Load data for the specific page
        switch(hash) {
            case '#products':
                loadProducts();
                break;
            case '#patients':
                loadPatients();
                break;
            case '#inventory':
                loadInventory();
                break;
            case '#new-sale':
                loadNewSalePage();
                break;
            case '#orders':
                loadOrders();
                break;
            case '#new-prescription':
                loadNewPrescriptionPage();
                break;
            // Add cases for other pages here
        }
    }

    $(window).on('hashchange', router);
    router(); // Initial route call

    console.log("App loaded and router initialized.");
});
