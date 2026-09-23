<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * البحث عن العملاء بالهاتف أو الاسم للـ POS
     */
    public function search(Request $request)
    {
        $term = trim($request->input('q', $request->input('phone', '')));
        if (strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $customers = Customer::where('phone', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->take(10)
            ->get(['id', 'name', 'phone']);

        return response()->json(['data' => $customers]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
