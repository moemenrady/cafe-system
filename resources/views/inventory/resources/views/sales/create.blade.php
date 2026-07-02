@extends('layouts.app')
@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl mb-4">كاشير - تسجيل أوردر</h2>
    <form action="{{ route('sales.store') }}" method="POST">
        @csrf
        <div class="bg-white p-4 shadow rounded">
            <p>شاشة البيع قيد التطوير...</p>
            <button type="submit" class="bg-green-600 text-white p-2 rounded">إتمام البيع</button>
        </div>
    </form>
</div>
@endsection
