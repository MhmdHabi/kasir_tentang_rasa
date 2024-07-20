<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            Product::create([
                'name' => $request->name,
                'price' => $request->price,
            ]);

            DB::commit();

            return redirect()->route('products')->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollback();

            return redirect()->route('products.store')->with('error', 'Gagal menambahkan produk. Silakan coba lagi.');
        }
    }

    public function update(Request $request, Product $product)
    {

        DB::beginTransaction();

        try {
            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
            ]);

            // Update the product
            $product->update([
                'name' => $request->name,
                'price' => $request->price,
            ]);

            DB::commit();

            return redirect()->route('products')->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('products')->with('error', 'Failed to update the product. Please try again.');
        }
    }

    // Remove the specified product from storage
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products')->with('success', 'Product deleted successfully!');
    }
}
