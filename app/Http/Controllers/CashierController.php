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
        $request->validate([
            'nama_pengunjung' => 'required|string|max:255',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $totalAmount = 0;
        $purchasedProducts = [];
        foreach ($request->input('products') as $productData) {
            $product = Product::find($productData['id']);
            $totalAmount += $product->price * $productData['quantity'];
            $purchasedProducts[] = [
                'name' => $product->name,
                'quantity' => $productData['quantity'],
                'price' => $product->price
            ];
        }

        $amountPaid = $request->amount_paid;
        $change = $amountPaid - $totalAmount;
        if ($change < 0) {
            $change = 0;
        }

        DB::beginTransaction();
        try {
            $purchase = Purchase::create([
                'nama_pengunjung' => $request->nama_pengunjung,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change' => $change,
            ]);

            foreach ($request->input('products') as $productData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productData['id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => Product::find($productData['id'])->price,
                    'total_price' => Product::find($productData['id'])->price * $productData['quantity'],
                ]);
            }

            DB::commit();

            return redirect()->route('receipt.generate')->with([
                'success' => 'Transaction completed successfully',
                'purchasedProducts' => $purchasedProducts,
                'totalAmount' => $totalAmount,
                'amountPaid' => $amountPaid,
                'change' => $change,
                'nama_pengunjung' => $request->nama_pengunjung,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Transaction failed']);
        }
    }


    public function generateReceipt(Request $request)
    {
        $purchasedProducts = session('purchasedProducts');
        $totalAmount = session('totalAmount');
        $change = session('change');
        $amountPaid = session('amountPaid');
        $nama_pengunjung = session('nama_pengunjung');

        return view('cashier.receipt', compact('purchasedProducts', 'totalAmount', 'change', 'amountPaid', 'nama_pengunjung'));
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
        ]);
    }
}