<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::all();
        $totalAmount = $request->input('total_amount', 0);
        $change = $request->input('change', 0);

        return view('cashier.index', compact('products', 'totalAmount', 'change'));
    }
    public function store(Request $request)
    {
        // Validasi input dari request
        $validated = $request->validate([
            'nama_pengunjung' => 'required|string|max:255',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'amount_paid' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,amount',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        // Hitung total amount sebelum diskon
        $totalAmount = 0;
        $purchasedProducts = [];
        foreach ($validated['products'] as $productData) {
            $product = Product::find($productData['id']);
            $totalAmount += $product->price * $productData['quantity'];
            $purchasedProducts[] = [
                'name' => $product->name,
                'quantity' => $productData['quantity'],
                'price' => $product->price
            ];
        }

        // Terapkan diskon
        $discountType = $validated['discount_type'];
        $discountPercentage = $validated['discount_percentage'] ?? 0;
        $discountAmount = $validated['discount_amount'] ?? 0;

        $totalDiscount = 0;
        $discountPersen = 0;
        $discountRupiah = 0;

        if ($discountType === 'percentage') {
            $discountPersen = $discountPercentage;
            $totalDiscount = ($totalAmount * $discountPercentage / 100);
            $discountRupiah = $totalDiscount;
        } elseif ($discountType === 'amount') {
            $discountRupiah = $discountAmount;
            $totalDiscount = $discountAmount;
            $discountPersen = ($discountAmount / $totalAmount) * 100;
        }

        $totalAmountAfterDiscount = $totalAmount - $totalDiscount;

        $amountPaid = $validated['amount_paid'];
        $change = $amountPaid - $totalAmountAfterDiscount;
        $change = max(0, $change);

        DB::beginTransaction();
        try {
            $purchase = Purchase::create([
                'nama_pengunjung' => $validated['nama_pengunjung'],
                'total_amount' => $totalAmountAfterDiscount,
                'amount_paid' => $amountPaid,
                'change' => $change,
                'discount_type' => $discountType,
                'discount_rupiah' => $discountRupiah,
                'discount_persen' => $discountPersen,
            ]);

            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['id']);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productData['id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $productData['quantity'],
                ]);
            }

            DB::commit();

            // Simpan data ke session
            session()->put('receipt_data', [
                'nama_pengunjung' => $validated['nama_pengunjung'],
                'purchasedProducts' => $purchasedProducts,
                'totalAmount' => $totalAmountAfterDiscount,
                'amountPaid' => $amountPaid,
                'change' => $change,
                'discountType' => $discountType,
                'discountRupiah' => $discountRupiah,
                'discountPersen' => $discountPersen,
            ]);

            return redirect()->route('receipt.generate')->with('success', 'Transaction completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Transaction failed']);
        }
    }


    public function generateReceipt()
    {
        $data = session()->get('receipt_data', []);

        // Clear the session data
        session()->forget('receipt_data');

        return view('cashier.receipt', $data);
    }

    public function show()
    {
        $purchases = Purchase::with('purchaseItems.product')->get();

        return view('cashier.show', compact('purchases'));
    }
    public function struk($id)
    {

        $purchase = Purchase::with('purchaseItems.product')->findOrFail($id);

        $discountType = $purchase->discount_type;
        $discountPercentage = $purchase->discount_persen;
        $fixedDiscountAmount = $purchase->discount_rupiah;

        // Calculate the total amount and discount
        $totalAmount = $purchase->total_amount;
        $discountAmount = 0;

        if ($discountType === 'percentage') {
            $discountAmount = $totalAmount * ($discountPercentage / 100);
        } elseif ($discountType === 'amount') {
            $discountAmount = $fixedDiscountAmount;
            $discountPercentage = ($discountAmount / $totalAmount) * 100;
        }

        $totalAmountAfterDiscount = $totalAmount - $discountAmount;

        $amountPaid = $purchase->amount_paid;
        $change = $amountPaid - $totalAmountAfterDiscount;

        return view('cashier.detail_struk', [
            'nama_pengunjung' => $purchase->nama_pengunjung,
            'purchasedProducts' => $purchase->purchaseItems->map(function ($item) {
                return [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price
                ];
            }),
            'totalAmount' => $totalAmountAfterDiscount,
            'amountPaid' => $amountPaid,
            'change' => $change,
            'discount' => $discountAmount,
            'discountType' => $discountType,
            'discountPercentage' => $discountPercentage,
        ]);
    }
}
