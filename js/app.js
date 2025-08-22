$(document).ready(function() {
    // --- API HELPER ---
    function apiCall(method, url, data = null) {
        return $.ajax({
            method: method,
            url: `/api/${url}`,
            contentType: 'application/json',
            data: data ? JSON.stringify(data) : null,
        });
    }

    // --- PRODUCTS PAGE LOGIC ---
    function loadProducts() {
        apiCall('GET', 'products.php').done(function(products) {
            const tableBody = $('#products-table tbody');
            tableBody.empty();
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
    $('#btn-cancel-edit').on('click', function() { $('#product-form').addClass('hidden'); });
    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        const productId = $('#product-id').val();
        const productData = { name: $('#product-name').val(), description: $('#product-description').val(), category: $('#product-category').val(), reorder_level: parseInt($('#product-reorder-level').val(), 10) };
        let method = productId ? 'PUT' : 'POST';
        let url = productId ? `products.php?id=${productId}` : 'products.php';
        apiCall(method, url, productData).done(function(response) {
            alert(response.message);
            $('#product-form').addClass('hidden');
            loadProducts();
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
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
        if (confirm('Are you sure?')) {
            apiCall('DELETE', `products.php?id=${productId}`).done(function(response) {
                alert(response.message);
                loadProducts();
            }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
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
    $('#btn-cancel-patient-edit').on('click', function() { $('#patient-form').addClass('hidden'); });
    $('#patient-form').on('submit', function(e) {
        e.preventDefault();
        const patientId = $('#patient-id').val();
        const patientData = { name: $('#patient-name').val(), contact_number: $('#patient-contact').val(), address: $('#patient-address').val() };
        let method = patientId ? 'PUT' : 'POST';
        let url = patientId ? `patients.php?id=${patientId}` : 'patients.php';
        apiCall(method, url, patientData).done(function(response) {
            alert(response.message);
            $('#patient-form').addClass('hidden');
            loadPatients();
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
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
        if (confirm('Are you sure?')) {
            apiCall('DELETE', `patients.php?id=${patientId}`).done(function(response) {
                alert(response.message);
                loadPatients();
            }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
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
    function loadProductsForSelect(selectId) {
        apiCall('GET', 'products.php').done(function(products) {
            const select = $(selectId);
            select.empty().append('<option value="" disabled selected>Select a Product</option>');
            products.forEach(function(product) {
                select.append(`<option value="${product.id}" data-name="${product.name}">${product.name}</option>`);
            });
        });
    }
    $('#btn-show-add-stock-form').on('click', function() {
        $('#inventory-form').removeClass('hidden');
        $('#inventory-form')[0].reset();
        loadProductsForSelect('#inventory-product-id');
    });
    $('#btn-cancel-stock-add').on('click', function() { $('#inventory-form').addClass('hidden'); });
    $('#inventory-form').on('submit', function(e) {
        e.preventDefault();
        const stockData = { product_id: parseInt($('#inventory-product-id').val(), 10), batch_number: $('#inventory-batch-number').val(), quantity: parseInt($('#inventory-quantity').val(), 10), manufacturing_date: $('#inventory-mfg-date').val(), expiry_date: $('#inventory-expiry-date').val(), price: parseFloat($('#inventory-price').val()) };
        apiCall('POST', 'inventory.php', stockData).done(function(response) {
            alert(response.message);
            $('#inventory-form').addClass('hidden');
            loadInventory();
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
    });

    // --- DISTRIBUTORS PAGE LOGIC ---
    function loadDistributors() {
        apiCall('GET', 'distributors.php').done(function(distributors) {
            const tableBody = $('#distributors-table tbody');
            tableBody.empty();
            distributors.forEach(function(distributor) {
                tableBody.append(`
                    <tr>
                        <td>${distributor.id}</td>
                        <td>${distributor.name}</td>
                        <td>${distributor.contact_person}</td>
                        <td>${distributor.contact_email}</td>
                        <td>${distributor.phone}</td>
                        <td>
                            <button class="btn-edit-distributor" data-id="${distributor.id}">Edit</button>
                            <button class="btn-delete-distributor" data-id="${distributor.id}">Delete</button>
                        </td>
                    </tr>
                `);
            });
        });
    }
    $('#btn-show-add-distributor-form').on('click', function() {
        $('#distributor-form').removeClass('hidden');
        $('#distributor-form')[0].reset();
        $('#distributor-id').val('');
    });
    $('#btn-cancel-distributor-edit').on('click', function() { $('#distributor-form').addClass('hidden'); });
    $('#distributor-form').on('submit', function(e) {
        e.preventDefault();
        const distributorId = $('#distributor-id').val();
        const distributorData = { name: $('#distributor-name').val(), contact_person: $('#distributor-contact-person').val(), contact_email: $('#distributor-contact-email').val(), phone: $('#distributor-phone').val() };
        let method = distributorId ? 'PUT' : 'POST';
        let url = distributorId ? `distributors.php?id=${distributorId}` : 'distributors.php';
        apiCall(method, url, distributorData).done(function(response) {
            alert(response.message);
            $('#distributor-form').addClass('hidden');
            loadDistributors();
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
    });
    $('#distributors-table').on('click', '.btn-edit-distributor', function() {
        const distributorId = $(this).data('id');
        apiCall('GET', `distributors.php?id=${distributorId}`).done(function(distributor) {
            $('#distributor-id').val(distributor.id);
            $('#distributor-name').val(distributor.name);
            $('#distributor-contact-person').val(distributor.contact_person);
            $('#distributor-contact-email').val(distributor.contact_email);
            $('#distributor-phone').val(distributor.phone);
            $('#distributor-form').removeClass('hidden');
        });
    });
    $('#distributors-table').on('click', '.btn-delete-distributor', function() {
        const distributorId = $(this).data('id');
        if (confirm('Are you sure?')) {
            apiCall('DELETE', `distributors.php?id=${distributorId}`).done(function(response) {
                alert(response.message);
                loadDistributors();
            }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
        }
    });

    // --- NEW SALE PAGE LOGIC ---
    let saleCart = [];
    function loadNewSalePage() {
        loadProductsForSelect('#new-sale-product-id');
        saleCart = [];
        renderCart();
    }
    function renderCart() {
        const tableBody = $('#sale-cart-table tbody');
        tableBody.empty();
        saleCart.forEach(function(item, index) {
            tableBody.append(`<tr><td>${item.product_name}</td><td>${item.quantity}</td><td><button class="btn-remove-from-cart" data-index="${index}">Remove</button></td></tr>`);
        });
    }
    $('#btn-add-to-cart').on('click', function() {
        const productId = $('#new-sale-product-id').val();
        const productName = $('#new-sale-product-id').find(':selected').data('name');
        const quantity = parseInt($('#new-sale-quantity').val(), 10);
        if (productId && quantity > 0) {
            saleCart.push({ product_id: parseInt(productId, 10), product_name: productName, quantity: quantity });
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
        if (saleCart.length === 0) { alert('Cannot submit an empty sale.'); return; }
        const saleData = { products: saleCart.map(item => ({ product_id: item.product_id, quantity: item.quantity })) };
        apiCall('POST', 'sales.php', saleData).done(function(response) {
            alert(response.message);
            window.location.hash = '#orders';
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
    });

    // --- NEW PRESCRIPTION PAGE LOGIC ---
    let prescriptionCart = [];
    function loadNewPrescriptionPage() {
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
        $('#new-prescription-date').val(new Date().toISOString().slice(0, 10));
        prescriptionCart = [];
        renderPrescriptionCart();
    }
    function renderPrescriptionCart() {
        const tableBody = $('#prescription-cart-table tbody');
        tableBody.empty();
        prescriptionCart.forEach(function(item, index) {
            tableBody.append(`<tr><td>${item.product_name}</td><td>${item.quantity}</td><td><button class="btn-remove-from-prescription-cart" data-index="${index}">Remove</button></td></tr>`);
        });
    }
    $('#btn-add-to-prescription-cart').on('click', function() {
        const productId = $('#new-prescription-product-id').val();
        const productName = $('#new-prescription-product-id').find(':selected').data('name');
        const quantity = parseInt($('#new-prescription-quantity').val(), 10);
        if (productId && quantity > 0) {
            prescriptionCart.push({ product_id: parseInt(productId, 10), product_name: productName, quantity: quantity });
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
        const prescriptionData = { doctor_id: parseInt($('#new-prescription-doctor-id').val(), 10), patient_id: parseInt($('#new-prescription-patient-id').val(), 10), prescription_date: $('#new-prescription-date').val(), products: prescriptionCart.map(item => ({ product_id: item.product_id, quantity: item.quantity })) };
        if (!prescriptionData.doctor_id || !prescriptionData.patient_id || !prescriptionData.prescription_date || prescriptionData.products.length === 0) {
            alert('Please fill out all fields and add at least one product.');
            return;
        }
        apiCall('POST', 'prescriptions.php', prescriptionData).done(function(response) {
            alert(response.message);
            window.location.hash = '#orders';
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
    });

    // --- NEW PURCHASE ORDER PAGE LOGIC ---
    let poCart = [];
    function loadNewPOPage() {
        const distributorsCall = apiCall('GET', 'distributors.php');
        const productsCall = apiCall('GET', 'products.php');
        $.when(distributorsCall, productsCall).done(function(distributorsRes, productsRes) {
            const distributors = distributorsRes[0];
            const products = productsRes[0];
            const distributorSelect = $('#new-po-distributor-id');
            distributorSelect.empty().append('<option value="" disabled selected>Select Distributor</option>');
            distributors.forEach(d => distributorSelect.append(`<option value="${d.id}">${d.name}</option>`));
            const productSelect = $('#new-po-product-id');
            productSelect.empty().append('<option value="" disabled selected>Select Product</option>');
            products.forEach(p => productSelect.append(`<option value="${p.id}" data-name="${p.name}">${p.name}</option>`));
        });
        $('#new-po-order-date').val(new Date().toISOString().slice(0, 10));
        poCart = [];
        renderPOCart();
    }
    function renderPOCart() {
        const tableBody = $('#po-cart-table tbody');
        tableBody.empty();
        poCart.forEach(function(item, index) {
            const totalPrice = item.quantity * item.price_per_unit;
            tableBody.append(`<tr><td>${item.product_name}</td><td>${item.quantity}</td><td>${item.price_per_unit.toFixed(2)}</td><td>${totalPrice.toFixed(2)}</td><td><button class="btn-remove-from-po-cart" data-index="${index}">Remove</button></td></tr>`);
        });
    }
    $('#btn-add-to-po-cart').on('click', function() {
        const productId = $('#new-po-product-id').val();
        const productName = $('#new-po-product-id').find(':selected').data('name');
        const quantity = parseInt($('#new-po-quantity').val(), 10);
        const price_per_unit = parseFloat($('#new-po-price').val());
        if (productId && quantity > 0 && price_per_unit >= 0) {
            poCart.push({ product_id: parseInt(productId, 10), product_name: productName, quantity: quantity, price_per_unit: price_per_unit });
            renderPOCart();
        } else {
            alert('Please select a product and enter a valid quantity and price.');
        }
    });
    $('#po-cart-table').on('click', '.btn-remove-from-po-cart', function() {
        const indexToRemove = $(this).data('index');
        poCart.splice(indexToRemove, 1);
        renderPOCart();
    });
    $('#btn-submit-po').on('click', function() {
        const poData = { distributor_id: parseInt($('#new-po-distributor-id').val(), 10), order_date: $('#new-po-order-date').val(), expected_delivery_date: $('#new-po-expected-date').val() || null, products: poCart };
        if (!poData.distributor_id || !poData.order_date || poData.products.length === 0) {
            alert('Please select a distributor, order date, and add at least one product.');
            return;
        }
        apiCall('POST', 'purchase_orders.php', poData).done(function(response) {
            alert(response.message);
            window.location.hash = '#purchase-orders';
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
    });

    // --- PURCHASE ORDERS PAGE LOGIC ---
    function loadPurchaseOrders() {
        apiCall('GET', 'purchase_orders.php').done(function(pos) {
            const tableBody = $('#purchase-orders-table tbody');
            tableBody.empty();
            pos.forEach(function(po) {
                tableBody.append(`
                    <tr>
                        <td>${po.id}</td>
                        <td>${po.distributor_name}</td>
                        <td>${po.order_date}</td>
                        <td>${po.total_amount}</td>
                        <td>${po.status}</td>
                        <td>
                            <button class="btn-view-po" data-id="${po.id}">View Details</button>
                        </td>
                    </tr>
                `);
            });
        });
    }
    $('#purchase-orders-table, #draft-po-table').on('click', '.btn-view-po', function() {
        const poId = $(this).data('id');
        apiCall('GET', `purchase_orders.php?id=${poId}`).done(function(po) {
            const detailsView = $('#po-details-view');
            let itemsHtml = '<h4>Items:</h4><table><thead><tr><th>Product</th><th>Qty</th><th>Price/Unit</th><th>Total</th></tr></thead><tbody>';
            po.items.forEach(item => {
                itemsHtml += `<tr><td>${item.product_name}</td><td>${item.quantity}</td><td>${item.price_per_unit}</td><td>${item.total_price}</td></tr>`;
            });
            itemsHtml += '</tbody></table>';

            let approveButton = '';
            if (po.status === 'draft') {
                approveButton = `<button class="btn-approve-po" data-po-id="${po.id}">Approve & Order</button>`;
            }

            detailsView.html(`
                <h2>Purchase Order #${po.id}</h2>
                <p><strong>Distributor:</strong> ${po.distributor_name}</p>
                <p><strong>Status:</strong> ${po.status}</p>
                <p><strong>Total:</strong> ${po.total_amount}</p>
                ${itemsHtml}
                ${approveButton}
                <button class="btn-receive-shipment" data-po-id="${po.id}">Receive Shipment</button>
                <button class="btn-close-po-details">Close</button>
                <div id="receive-shipment-form-container"></div>
            `);
            $('#purchase-orders').append(detailsView);
            detailsView.removeClass('hidden');
        });
    });
    $('#po-details-view').on('click', '.btn-approve-po', function() {
        const poId = $(this).data('po-id');
        if (confirm('Are you sure you want to approve this purchase order?')) {
            apiCall('PUT', `purchase_orders.php?id=${poId}`, { status: 'ordered' }).done(function(response) {
                alert(response.message);
                $('#po-details-view').addClass('hidden').empty();
                loadReorderSuggestions();
                loadPurchaseOrders();
            }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
        }
    });
    $('#purchase-orders').on('click', '.btn-close-po-details', function() {
        $('#po-details-view').addClass('hidden').empty();
    });
    $('#purchase-orders').on('click', '.btn-receive-shipment', function() {
        const poId = $(this).data('po-id');
        apiCall('GET', `purchase_orders.php?id=${poId}`).done(function(po) {
            let formHtml = '<h4>Receive Shipment</h4><form id="receive-shipment-form">';
            po.items.forEach(item => {
                formHtml += `<fieldset><legend>${item.product_name} (Ordered: ${item.quantity})</legend><input type="hidden" name="product_id" value="${item.product_id}"><input type="hidden" name="quantity" value="${item.quantity}"><input type="text" name="batch_number" placeholder="Batch Number" required><input type="date" name="manufacturing_date" placeholder="Mfg. Date" required><input type="date" name="expiry_date" placeholder="Expiry Date" required></fieldset>`;
            });
            formHtml += '<button type="submit">Submit Received Stock</button></form>';
            $('#receive-shipment-form-container').html(formHtml);
        });
    });
    $('#purchase-orders').on('submit', '#receive-shipment-form', function(e) {
        e.preventDefault();
        const poId = $('.btn-receive-shipment').data('po-id');
        const items = [];
        $(this).find('fieldset').each(function() {
            items.push({ product_id: $(this).find('[name=product_id]').val(), quantity: $(this).find('[name=quantity]').val(), batch_number: $(this).find('[name=batch_number]').val(), manufacturing_date: $(this).find('[name=manufacturing_date]').val(), expiry_date: $(this).find('[name=expiry_date]').val() });
        });
        const shipmentData = { purchase_order_id: poId, items: items };
        apiCall('POST', 'receive_shipment.php', shipmentData).done(function(response) {
            alert(response.message);
            $('#po-details-view').addClass('hidden').empty();
            loadPurchaseOrders();
        }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
    });

    // --- REORDER SUGGESTIONS PAGE LOGIC ---
    function loadReorderSuggestions() {
        apiCall('GET', 'purchase_orders.php').done(function(pos) {
            const tableBody = $('#draft-po-table tbody');
            tableBody.empty();
            pos.filter(po => po.status === 'draft').forEach(function(po) {
                tableBody.append(`
                    <tr>
                        <td>${po.id}</td>
                        <td>${po.distributor_name}</td>
                        <td>${po.order_date}</td>
                        <td>${po.total_amount}</td>
                        <td><button class="btn-view-po" data-id="${po.id}">View & Approve</button></td>
                    </tr>
                `);
            });
        });
    }
    $('#btn-run-auto-reorder').on('click', function() {
        $(this).text('Working...').prop('disabled', true);
        apiCall('POST', 'automated_reorder.php').done(function(response) {
            alert(response.message);
            loadReorderSuggestions();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        }).always(function() {
            $('#btn-run-auto-reorder').text('Check for Low Stock & Generate Suggestions').prop('disabled', false);
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
                        <td><button class="btn-view-order" data-id="${order.id}">View Details</button></td>
                    </tr>
                `);
            });
        });
    }
    $('#orders-table').on('click', '.btn-view-order', function() {
        const orderId = $(this).data('id');
        apiCall('GET', `orders.php?id=${orderId}`).done(function(order) {
            $('#order-details-view').data('current-order-id', order.id);
            $('#details-order-id').text(order.id);
            $('#details-order-status').text(order.status);
            $('#details-order-total').text(order.total_amount);
            const itemsTableBody = $('#order-items-table tbody');
            itemsTableBody.empty();
            order.items.forEach(function(item) {
                itemsTableBody.append(`<tr><td>${item.product_name}</td><td>${item.quantity}</td><td>${item.price_per_unit}</td><td>${item.total_price}</td></tr>`);
            });
            $('#order-details-view').removeClass('hidden');
        });
    });
    $('#btn-close-order-details').on('click', function() { $('#order-details-view').addClass('hidden'); });
    function updateOrderStatus(status) {
        const orderId = $('#order-details-view').data('current-order-id');
        if (!orderId) return;
        const confirmationText = status === 'cancelled' ? 'Are you sure you want to cancel this order? This will return items to stock.' : 'Are you sure you want to mark this order as completed?';
        if (confirm(confirmationText)) {
            apiCall('PUT', `orders.php?id=${orderId}`, { status: status }).done(function(response) {
                alert(response.message);
                $('#order-details-view').addClass('hidden');
                loadOrders();
            }).fail(function(xhr) { alert('Error: ' + xhr.responseJSON.message); });
        }
    }
    $('#btn-mark-completed').on('click', function() { updateOrderStatus('completed'); });
    $('#btn-cancel-order').on('click', function() { updateOrderStatus('cancelled'); });
    $('#btn-print-bill').on('click', function() {
        const orderId = $('#order-details-view').data('current-order-id');
        if (!orderId) return;
        apiCall('POST', 'invoices.php', { order_id: orderId }).always(function() {
            apiCall('GET', `orders.php?id=${orderId}`).done(function(order) {
                let printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Print Bill</title><style>body{font-family:monospace;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:8px;}</style></head><body>');
                printWindow.document.write(`<h1>Invoice for Order #${order.id}</h1><p>Date: ${new Date(order.created_at).toLocaleString()}</p><p>Status: ${order.status}</p><h3>Items:</h3>`);
                printWindow.document.write('<table><thead><tr><th>Product</th><th>Qty</th><th>Price/Unit</th><th>Total</th></tr></thead><tbody>');
                order.items.forEach(item => {
                    printWindow.document.write(`<tr><td>${item.product_name}</td><td>${item.quantity}</td><td>${item.price_per_unit}</td><td>${item.total_price}</td></tr>`);
                });
                printWindow.document.write(`</tbody></table><h2>Total Amount: ${order.total_amount}</h2></body></html>`);
                printWindow.document.close();
                printWindow.print();
            });
        });
    });

    // --- ROUTER ---
    function router() {
        const hash = window.location.hash || '#dashboard';
        $('.page').hide();
        $(hash).show();
        $('nav a').removeClass('active');
        $(`nav a[href="${hash}"]`).addClass('active');
        switch(hash) {
            case '#products': loadProducts(); break;
            case '#patients': loadPatients(); break;
            case '#inventory': loadInventory(); break;
            case '#distributors': loadDistributors(); break;
            case '#new-sale': loadNewSalePage(); break;
            case '#new-prescription': loadNewPrescriptionPage(); break;
            case '#new-po': loadNewPOPage(); break;
            case '#orders': loadOrders(); break;
            case '#purchase-orders': loadPurchaseOrders(); break;
            case '#reorder-suggestions': loadReorderSuggestions(); break;
        }
    }
    $(window).on('hashchange', router);
    router();
    console.log("App loaded and router initialized.");
});
