<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Counter;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function search()
    {
    //     $results = Product::orderBy('item_code')
    //         ->when(request('q'), function($query) {
    //             $query->where('item_code', 'like', '%'.request('q').'%')
    //             ->orWhere('description', 'like', '%'.request('q').'%');
    //         })
    //         ->limit(6)
    //         ->get();
        
    }
    public function index()
    {
        $results = Product::with('vendor')->when(request()->has('vendor'), function($q)
        {
            $q->where('vendor_id','=', request('vendor'));
        })
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return response()
            ->json(['results' => $results]);
    }
    public function create()
    {
        $counter = Counter::where('key', 'product')->first();

        $form = [
            'vendor' => null,
            // 'item_code' => null,
            'description' => null,
            'unit_price' => 0
        ];

        return response()
            ->json(['form' => $form]);
    }
    public function store(Request $request)
    {
        // dd($request->all());
        $product = new Product;
// $product->item_code = $request->item_code;
// $product->description = $request->description;
// $product->unit_price = $request->unit_price;
        
        $product->fill($request->all());

        $product = DB::transaction(function() use ($product, $request) {
            $counter = Counter::where('key', 'product')->first();
            $product->item_code = $counter->prefix . $counter->value;

            // custom method from app/Helper/HasManyRelation
            // $product->storeHasMany([
            //     'items' => $request->items
            // ]);
            return $product;
        });
        $product->save();
        
        return response()
            ->json(['saved' => true, 'id' => $product->id]);
    }
    public function show($id)
    {
        $model = Product::with(['vendor'])
            ->findOrFail($id);

        return response()
            ->json(['model' => $model]);
    }

    public function edit($id)
    {
        $form = Product::with(['vendor'])
            ->findOrFail($id);

        return response()
            ->json(['form' => $form]);
    }
    public function update($id, Request $request)
    {
        $product = Product::findOrFail($id);

        $product->fill($request->all());

        $product = DB::transaction(function() use ($product, $request) {
            // custom method from app/Helper/HasManyRelation
            // $product->updateHasMany([
            //     'items' => $request->items
            // ]);
            return $product;
        });
        $product->save();
        return response()
            ->json(['saved' => true, 'id']);
    
    }
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        $product->delete();

        return response()
            ->json(['deleted' => true]);
    }
}