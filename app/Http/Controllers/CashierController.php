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
        $validated = $request->validate([
            'nama_pengunjung' => 'required|string|max:255',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'amount_paid' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

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

        // Apply discount
        $discount = $validated['discount'] ?? 0;
        $discountAmount = $totalAmount * $discount / 100;
        $totalAmount -= $discountAmount;

        $amountPaid = $validated['amount_paid'];
        $change = $amountPaid - $totalAmount;
        $change = max(0, $change);

        DB::beginTransaction();
        try {
            $purchase = Purchase::create([
                'nama_pengunjung' => $validated['nama_pengunjung'],
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change' => $change,
                'discount' => $discount,
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

            // Store data in session
            session()->put('receipt_data', [
                'nama_pengunjung' => $validated['nama_pengunjung'],
                'purchasedProducts' => $purchasedProducts,
                'totalAmount' => $totalAmount,
                'amountPaid' => $amountPaid,
                'change' => $change,
                'discount' => $discount,
                'discountAmount' => $discountAmount,
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

        $totalAmount = $purchase->total_amount;
        $amountPaid = $purchase->amount_paid;
        $change = $amountPaid - $totalAmount;
        $discount = $purchase->discount;
        $discountAmount = $totalAmount * ($discount / 100);

        return view('cashier.receipt', [
            'nama_pengunjung' => $purchase->nama_pengunjung,
            'purchasedProducts' => $purchase->purchaseItems->map(function ($item) {
                return [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price
                ];
            }),
            'totalAmount' => $totalAmount,
            'amountPaid' => $amountPaid,
            'change' => $change,
            'discount' => $discount,
            'discountAmount' => $discountAmount,
        ]);
    }
}
