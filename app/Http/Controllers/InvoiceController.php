<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Counter;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    // public function search($customer){
    // {
    //     $result = Invoice::where('customer', 'LIKE', '%'. $customer. '%')->get();
    //     if(count($result)){
    //      return Response()->json($result);
    //     }
    //     else
    //     {
    //     return response()->json(['Result' => 'No Data not found'], 404);
    //   }
    // }
    // public function index()
    // {
    //     $results = Invoice::with(['customer'])
    //      ->orderBy('created_at', 'desc')
    //         ->paginate(15);

    //     return response()
    //         ->json(['results' => $results]);
    // }
    public function index(Request $request)
    {
        // dd($request->all());
        $results = Invoice::with('customer')->when(request()->has('ahmed'), function($q)
        {
            $q->where('customer_id','=', request('ahmed'));
        })
     
     
        

            // ->whereHas('customer',function($q) use ($request){
                    // $q->Where('firstname','like', '%' . request('ahmed') . '%');
                    // $q->orWhere('lastname','like', '%' . request('ahmed') . '%');
                    // })
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return response()
            ->json(['results' => $results]);
    }
    // public function index(Request $request)
    // {
    //     // dd($request->all());
    //     $results = Invoice::with(['items'])
    //         ->whereHas('items',function($q) use ($request){
    //                 $q->where('product_id','like', '%' . request('items') . '%');
    //                 // $q->orWhere('','like', '%' . request('items') . '%');
    //                 })
    //         ->orderBy('id', 'desc')
    //         ->paginate(15);

    //     return response()
    //         ->json(['results' => $results]);
    // }
    

    public function create()
    {
        $counter = Counter::where('key', 'invoice')->first();

        $form = [
            'number' => $counter->prefix . $counter->value,
            'customer_id' => null,
            'customer' => null,
            'date' => date('Y-m-d'),
            'due_date' => null,
            'reference' => null,
            'discount' => 0,
            'terms_and_conditions' => 'Default Terms',
            'items' => [
                [
                    'product_id' => null,
                    'product' => null,
                    'unit_price' => 0,
                    'qty' => 1
                ]
            ]
        ];

        return response()
            ->json(['form' => $form]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'date' => 'required|date_format:Y-m-d',
            'due_date' => 'required|date_format:Y-m-d',
            'reference' => 'nullable|max:100',
            'discount' => 'required|numeric|min:0',
            'terms_and_conditions' => 'required|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1'
        ]);

        $invoice = new Invoice;
        dd($invoice);
        $invoice->fill($request->except('items'));

        $invoice->sub_total = collect($request->items)->sum(function($item) {
            return $item['qty'] * $item['unit_price'];
        });

        $invoice = DB::transaction(function() use ($invoice, $request) {
            $counter = Counter::where('key', 'invoice')->first();
            $invoice->number = $counter->prefix . $counter->value;

            // custom method from app/Helper/HasManyRelation
            $invoice->storeHasMany([
                'items' => $request->items
            ]);

            $counter->increment('value');

            return $invoice;
        
        });

        return response()
            ->json(['saved' => true, 'id' => $invoice->id]);
    }

    public function show($id)
    {
        $model = Invoice::with(['customer', 'items.product'])
            ->findOrFail($id);

        return response()
            ->json(['model' => $model]);
    }

    public function edit($id)
    {
        $form = Invoice::with(['customer', 'items.product'])
            ->findOrFail($id);

        return response()
            ->json(['form' => $form]);
    }

    public function update($id, Request $request)
    {
        $invoice = Invoice::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'date' => 'required|date_format:Y-m-d',
            'due_date' => 'required|date_format:Y-m-d',
            'reference' => 'nullable|max:100',
            'discount' => 'required|numeric|min:0',
            'terms_and_conditions' => 'required|max:2000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'sometimes|required|integer|exists:invoice_items,id,invoice_id,'.$invoice->id,
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1'
        ]);

        $invoice->fill($request->except('items'));

        $invoice->sub_total = collect($request->items)->sum(function($item) {
            return $item['qty'] * $item['unit_price'];
        });

        $invoice = DB::transaction(function() use ($invoice, $request) {
            // custom method from app/Helper/HasManyRelation
            $invoice->updateHasMany([
                'items' => $request->items
            ]);

            return $invoice;
        });

        return response()
            ->json(['saved' => true, 'id' => $invoice->id]);
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        $invoice->items()->delete();

        $invoice->delete();

        return response()
            ->json(['deleted' => true]);
    }
}