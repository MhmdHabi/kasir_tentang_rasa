<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembelanjaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        .separator {
            text-align: center;
            color: black;
            font-weight: bold;
            margin: 10px 0;
        }

        .label {
            flex: 0 0 200px;
        }

        .judul {
            flex: 0 0 120px;
        }

        .value {
            flex: 1;
            text-align: left;
        }

        .card-header,
        .card-footer {
            text-align: center;
        }

        .table-bordered {
            border: none;
        }

        .table-bordered th,
        .table-bordered td {
            border: none;
        }

        .table th,
        .table td {
            text-align: left;
        }

        .table td {
            padding-right: 10px;
        }

        .alamat {
            text-align: center;
            max-width: 400px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="">
                    <div class="card-header text-center">
                        <h3 class="mb-0 fs-4">Tentang Rasa</h3>
                        <p class="mb-0 alamat">Jl. Sunan Kalijaga No.60, Simpang III Sipin, Kec. Kota Baru, Kota Jambi,
                            Jambi 36129</p>
                    </div>
                    <div class="separator">=========================================</div>
                    <div class="">
                        <div class="d-flex justify-content-between w-100">
                            <span class="judul">Nama Tamu</span>
                            <span class="value">: {{ $nama_pengunjung }}</span>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <span class="judul">Tanggal</span>
                            <span class="value">: {{ now()->format('d-m-Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <span class="judul">Jam</span>
                            <span class="value">: {{ now()->format('H:i:s') }}</span>
                        </div>
                        <div class="separator">=========================================</div>
                        @foreach ($purchasedProducts as $product)
                            <div>
                                <p class="mb-1">{{ $product['name'] }}</p>
                                <p style="margin-left: 20px;" class="mb-1">{{ $product['quantity'] }} x Rp
                                    {{ number_format($product['price'], 0, ',', '.') }}
                                    <span style="float: right;">Rp
                                        {{ number_format($product['price'] * $product['quantity'], 0, ',', '.') }}</span>
                                </p>
                            </div>
                        @endforeach
                        <div class="separator">=========================================</div>
                        @if ($discountType === 'percentage')
                            <div class="d-flex justify-content-between w-100 mt-1">
                                <span class="label text-end">Diskon (%)</span>
                                <span class="value text-end">: {{ number_format($discountPercentage ?? 0, 0) }}%</span>
                            </div>
                        @elseif ($discountType === 'amount')
                            <div class="d-flex justify-content-between w-100 mt-1">
                                <span class="label text-end">Diskon (Rp)</span>
                                <span class="value text-end">: Rp
                                    {{ number_format($discount ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between w-100 mt-1">
                                <span class="label text-end">Diskon (%)</span>
                                <span class="value text-end">:
                                    {{ number_format($discountPercentage ?? 0, 2, ',', '.') }}%</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between w-100 mt-1">
                            <span class="label text-end">Total</span>
                            <span class="value text-end">: Rp
                                {{ number_format($totalAmount ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between w-100 mt-1">
                            <span class="label text-end">Jumlah Bayar</span>
                            <span class="value text-end">: Rp {{ number_format($amountPaid, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between w-100 mt-1">
                            <span class="label text-end">Kembalian</span>
                            <span class="value text-end">: Rp {{ number_format($change, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="separator">=========================================</div>
                    <div class="card-footer text-center">
                        <p class="mb-1">IG : tentangrasa1211</p>
                        <p class="mb-1">WA : 0932822293</p>
                        <p>Terima Kasih telah berbelanja di Tentang Rasa!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
