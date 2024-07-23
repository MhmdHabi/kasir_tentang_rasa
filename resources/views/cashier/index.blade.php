@extends('layouts.master')

@section('title', 'Kasir')

@section('content')
    <div class="container my-5">
        <h1 class="mb-4 fw-bold font-sans-serif">Kasir</h1>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Notification Messages -->
        <div id="notification" class="mb-3"></div>

        <form action="{{ route('cashier.store') }}" method="POST" id="purchase-form">
            @csrf

            <!-- Nama Pengunjung -->
            <div class="mb-3">
                <label for="nama_pengunjung" class="form-label">Nama Pengunjung</label>
                <input type="text" name="nama_pengunjung" id="nama_pengunjung" class="form-control"
                    value="{{ old('nama_pengunjung') }}" required>
            </div>

            <!-- Product Selection -->
            <div class="row mb-3">
                <div class="col-md-9">
                    <label for="select_product" class="form-label">Pilih Produk</label>
                    <select id="select_product" class="form-select">
                        <option value="">Pilih Produk</option>
                        @foreach ($products as $prod)
                            <option value="{{ $prod->id }}" data-price="{{ $prod->price }}">
                                {{ $prod->name }} - Rp{{ number_format($prod->price, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="add-product" class="btn btn-warning w-100">Tambah Produk</button>
                </div>
            </div>

            <!-- Garis Line -->
            <div class="border border-dark border-start-0 border-end-0 border-bottom-0 border-2 p-3 mb-4"></div>

            <!-- Selected Products Container -->
            <div class="border border-dark border-start-0 border-end-0 border-2 p-3 mb-4" id="selected-products-container"
                style="display: none;">
                <div id="selected-products"></div>
            </div>

            <!-- Discount -->
            <div class="mb-3">
                <label for="discount" class="form-label">Diskon (%)</label>
                <input type="number" name="discount" id="discount" class="form-control" placeholder="0" min="0"
                    max="100">
            </div>

            <!-- Uang Pembayaran dan Total Harga -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="amount_paid" class="form-label">Uang Dibayar</label>
                    <input type="text" name="amount_paid" id="amount_paid" class="form-control" placeholder="0"
                        value="{{ old('amount_paid') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="total_amount" class="form-label">Total Harga</label>
                    <input type="text" id="total_amount" class="form-control" value="Rp0" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 offset-md-6">
                    <label for="change" class="form-label">Kembalian</label>
                    <input type="text" id="change" class="form-control" value="Rp0" readonly>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-warning mb-2">Submit</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedProducts = [];

            function formatCurrency(value) {
                return `Rp${value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
            }

            function unformatCurrency(value) {
                return parseFloat(value.replace(/[^\d,-]/g, '').replace(',', '.'));
            }

            document.getElementById('add-product').addEventListener('click', function() {
                let productSelect = document.getElementById('select_product');
                let selectedProduct = productSelect.options[productSelect.selectedIndex];
                let productId = selectedProduct.value;
                let productName = selectedProduct.textContent.split(' - ')[0];
                let productPrice = parseFloat(selectedProduct.getAttribute('data-price'));

                if (productId && !selectedProducts.some(p => p.id === productId)) {
                    selectedProducts.push({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: 1
                    });

                    updateSelectedProducts();
                    calculateTotal();
                    showNotification(`Product "${productName}" berhasil ditambahkan.`, 'info');
                } else {
                    showNotification(`Product "${productName}" sudah ditambahkan.`, 'info');
                }

                productSelect.selectedIndex = 0;
            });

            function updateSelectedProducts() {
                let container = document.getElementById('selected-products');
                container.innerHTML = '';

                if (selectedProducts.length > 0) {
                    document.getElementById('selected-products-container').style.display = 'block';
                } else {
                    document.getElementById('selected-products-container').style.display = 'none';
                }

                selectedProducts.forEach((product, index) => {
                    let row = document.createElement('div');
                    row.setAttribute('data-index', index);
                    row.innerHTML = `
                        <div class="row mb-2">
                            <div class="col-md-5">
                                <input type="hidden" name="products[${index}][id]" value="${product.id}">
                                <label class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" value="${product.name}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label for="quantity_${index}" class="form-label">Quantity</label>
                                <input type="number" id="quantity_${index}" name="products[${index}][quantity]" class="form-control" value="${product.quantity}" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Total Harga</label>
                                <input type="text" class="form-control" value="${formatCurrency(product.price * product.quantity)}" readonly>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-remove" data-index="${index}">Hapus</button>
                            </div>
                        </div>
                    `;
                    container.appendChild(row);

                    row.querySelector(`#quantity_${index}`).addEventListener('input', function() {
                        let newQuantity = parseInt(this.value);
                        selectedProducts[index].quantity = newQuantity;
                        updateSelectedProducts();
                        calculateTotal();
                    });
                });

                document.querySelectorAll('.btn-remove').forEach(button => {
                    button.addEventListener('click', function() {
                        let index = this.getAttribute('data-index');
                        let productName = selectedProducts[index].name;
                        selectedProducts.splice(index, 1);
                        updateSelectedProducts();
                        calculateTotal();
                        showNotification(`Product "${productName}" berhasil dihapus.`, 'info');
                    });
                });
            }

            function calculateTotal() {
                let total = selectedProducts.reduce((sum, product) => sum + (product.price * product.quantity), 0);
                let discount = parseFloat(document.getElementById('discount').value) || 0;
                total = total - (total * discount / 100);
                document.getElementById('total_amount').value = formatCurrency(total);
                calculateChange();
            }

            function calculateChange() {
                let amountPaid = unformatCurrency(document.getElementById('amount_paid').value) || 0;
                let totalAmount = unformatCurrency(document.getElementById('total_amount').value) || 0;
                let change = amountPaid - totalAmount;

                document.getElementById('change').value = amountPaid >= totalAmount ? formatCurrency(change) :
                'Rp0';
            }

            document.getElementById('amount_paid').addEventListener('input', function() {
                let value = this.value.replace(/[^0-9]/g, '');
                if (!isNaN(value) && value !== '') {
                    let numericValue = parseFloat(value);
                    this.value = numericValue;
                    calculateChange();
                }
            });

            document.getElementById('discount').addEventListener('input', calculateTotal);

            document.getElementById('print-receipt').addEventListener('click', function() {
                let form = document.getElementById('purchase-form');
                let formData = new FormData(form);

                formData.append('selectedProducts', JSON.stringify(selectedProducts));
                formData.append('totalAmount', unformatCurrency(document.getElementById('total_amount')
                    .value));
                formData.append('amountPaid', unformatCurrency(document.getElementById('amount_paid')
                    .value));
                formData.append('change', unformatCurrency(document.getElementById('change').value));

                fetch('{{ route('receipt.generate') }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        let receiptWindow = window.open('', '_blank');
                        receiptWindow.document.write(html);
                        receiptWindow.document.close();
                        receiptWindow.print();
                    })
                    .catch(error => console.error('Error:', error));
            });

            function showNotification(message, type) {
                let notificationDiv = document.getElementById('notification');
                notificationDiv.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
                setTimeout(() => notificationDiv.innerHTML = '', 3000);
            }
        });
    </script>
@endsection
