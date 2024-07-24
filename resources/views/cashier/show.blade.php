@extends('layouts.master')

@section('title', 'Data Penjualan')

@section('content')
    <div class="container mt-5">
        <h1 class="mb-4 fw-bold font-sans-serif">Data Penjualan</h1>

        @if ($purchases->isEmpty())
            <div class="alert alert-info" role="alert">
                Tidak ada data penjualan.
            </div>
        @else
            <!-- Export to Excel button -->
            <a href="{{ route('export.data') }}">
                <button type="submit" class="btn btn-success mb-3">Export to Excel</button>
            </a>

            <div class="table-responsive">
                <table id="purchasesTable" class="table table-bordered">
                    <thead class="table-primary" style="font-size: 0.875rem;">
                        <tr>
                            <th>#</th>
                            <th>Nama Pengunjung</th>
                            <th>Produk dan Quantity</th>
                            <th>Total Harga</th>
                            <th>Uang Pembayaran</th>
                            <th>Kembalian</th>
                            <th>Harga Satuan</th>
                            <th class="text-center">Created At</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $purchase->nama_pengunjung }}</td>
                                <td>
                                    @foreach ($purchase->purchaseItems as $item)
                                        {{ $item->product->name }} ({{ $item->quantity }}) -
                                        Rp{{ number_format($item->total_price, 0, ',', '.') }}<br>
                                    @endforeach
                                </td>
                                <td>Rp{{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                                <td>Rp{{ number_format($purchase->amount_paid, 0, ',', '.') }}</td>
                                <td>Rp{{ number_format($purchase->change, 0, ',', '.') }}</td>
                                <td>
                                    @foreach ($purchase->purchaseItems as $item)
                                        Rp{{ number_format($item->unit_price, 0, ',', '.') }}<br>
                                    @endforeach
                                </td>
                                <td>
                                    <div>{{ $purchase->created_at->format('d/m/Y') }}</div>
                                    <div>{{ $purchase->created_at->format('H:i') }}</div>
                                </td>
                                <td class="text-center ">
                                    <a href="{{ route('receipt.show', ['id' => $purchase->id]) }}"
                                        class="btn btn-primary">Struk</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Initialize DataTables -->
    <script>
        $(document).ready(function() {
            $('#purchasesTable').DataTable();
        });
    </script>

    <!-- Style untuk DataTable -->
    <style>
        #purchasesTable td,
        #purchasesTable th {
            border: 1px solid #dee2e6;
        }

        .dataTables_wrapper {
            margin-bottom: 20px;
        }

        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 20px;
        }
    </style>
@endsection
