@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Stat 1 -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-start">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Orders</p>
                <h3 class="text-2xl font-black text-gray-900">128</h3>
                <p class="text-[11px] text-gray-400 mt-1">+12% vs yesterday</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="bg-[#FFF5F5] p-5 rounded-2xl shadow-sm border border-red-50 flex justify-between items-start">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pending Payments</p>
                <h3 class="text-2xl font-black text-gray-900">EGP 45,780</h3>
                <p class="text-[11px] text-gray-400 mt-1">23 Orders</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-red-100 text-red-500 flex items-center justify-center text-lg">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="bg-[#FFFAF2] p-5 rounded-2xl shadow-sm border border-yellow-50 flex justify-between items-start">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Orders in Production</p>
                <h3 class="text-2xl font-black text-gray-900">68</h3>
                <p class="text-[11px] text-gray-400 mt-1">53% of total</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-yellow-100 text-yellow-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-scissors"></i>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="bg-[#F0FDF4] p-5 rounded-2xl shadow-sm border border-green-50 flex justify-between items-start">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ready to Ship</p>
                <h3 class="text-2xl font-black text-gray-900">15</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-truck"></i>
            </div>
        </div>

        <!-- Stat 5 -->
        <div class="bg-[#F8FAFC] p-5 rounded-2xl shadow-sm border border-blue-50 flex justify-between items-start">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Delivered Today</p>
                <h3 class="text-2xl font-black text-gray-900">9</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-regular fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- ADMIN VIEW Table -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col transition-all">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-2 bg-[#111827] text-white px-4 py-2 rounded-xl font-bold text-xs w-max shadow-sm">
                <i class="fa-solid fa-lock text-[10px]"></i> ADMIN VIEW (Full Details)
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                <div class="relative w-full sm:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" placeholder="Search by Order ID, Customer Name, Phone..." class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-gray-300 transition">
                </div>
                <button class="w-full sm:w-auto flex items-center justify-center gap-2 border border-gray-200 px-5 py-2.5 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 transition shadow-sm">
                    <i class="fa-solid fa-filter text-gray-400"></i> Filters
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead class="text-[11px] text-gray-500 font-bold bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-4">Order ID</th>
                        <th class="px-5 py-4">Customer Name</th>
                        <th class="px-5 py-4">Phone 1</th>
                        <th class="px-5 py-4">Phone 2</th>
                        <th class="px-5 py-4">Address</th>
                        <th class="px-5 py-4">Product</th>
                        <th class="px-5 py-4">Total Price</th>
                        <th class="px-5 py-4">Paid</th>
                        <th class="px-5 py-4">Remaining</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Source</th>
                        <th class="px-5 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <!-- Row 1 -->
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-5 py-4 text-gray-600 font-medium">#ORD-1001</td>
                        <td class="px-5 py-4 font-bold text-gray-900">Ahmed Mostafa</td>
                        <td class="px-5 py-4 text-gray-600">01012345678</td>
                        <td class="px-5 py-4 text-gray-600">01123456789</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Cairo, Nasr City<br>Street 10, Building 25</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Design 101<br>Black - L - Full</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 850</td>
                        <td class="px-5 py-4 text-gray-600">EGP 500</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 350</td>
                        <td class="px-5 py-4"><span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2.5 py-1 rounded-md">Cutting</span></td>
                        <td class="px-5 py-4"><span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2.5 py-1 rounded-md">New</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                    <!-- Row 2 -->
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-5 py-4 text-gray-600 font-medium">#ORD-1002</td>
                        <td class="px-5 py-4 font-bold text-gray-900">Sara Mohamed</td>
                        <td class="px-5 py-4 text-gray-600">01098765432</td>
                        <td class="px-5 py-4 text-gray-400">-</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Giza, Mohandessin<br>Street 4, Building 12</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Design 205<br>Beige - M - Half</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 1,250</td>
                        <td class="px-5 py-4 text-gray-600">EGP 1,000</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 250</td>
                        <td class="px-5 py-4"><span class="bg-purple-100 text-purple-700 text-[10px] font-bold px-2.5 py-1 rounded-md">Sewing</span></td>
                        <td class="px-5 py-4"><span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2.5 py-1 rounded-md">New</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                    <!-- Row 3 -->
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-5 py-4 text-gray-600 font-medium">#ORD-1003</td>
                        <td class="px-5 py-4 font-bold text-gray-900">Omar Ashraf</td>
                        <td class="px-5 py-4 text-gray-600">01122334455</td>
                        <td class="px-5 py-4 text-gray-600">01055667788</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Alexandria, Smouha<br>Street 17, Building 8</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Design 110<br>Navy - XL - Full</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 950</td>
                        <td class="px-5 py-4 text-gray-600">EGP 950</td>
                        <td class="px-5 py-4 font-semibold text-gray-400">EGP 0</td>
                        <td class="px-5 py-4"><span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2.5 py-1 rounded-md">Finishing</span></td>
                        <td class="px-5 py-4"><span class="bg-green-50 text-green-600 text-[10px] font-bold px-2.5 py-1 rounded-md">From Stock</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                    <!-- Row 4 -->
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-5 py-4 text-gray-600 font-medium">#ORD-1004</td>
                        <td class="px-5 py-4 font-bold text-gray-900">Yara Magdy</td>
                        <td class="px-5 py-4 text-gray-600">01066778899</td>
                        <td class="px-5 py-4 text-gray-400">-</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Cairo, Maadi<br>Street 9, Building 3</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Design 300<br>White - M - Half</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 1,100</td>
                        <td class="px-5 py-4 text-gray-600">EGP 300</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 800</td>
                        <td class="px-5 py-4"><span class="bg-teal-100 text-teal-700 text-[10px] font-bold px-2.5 py-1 rounded-md">Ready to Ship</span></td>
                        <td class="px-5 py-4"><span class="bg-orange-50 text-orange-600 text-[10px] font-bold px-2.5 py-1 rounded-md">Replacement</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                    <!-- Row 5 -->
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-5 py-4 text-gray-600 font-medium">#ORD-1005</td>
                        <td class="px-5 py-4 font-bold text-gray-900">Hassan Ali</td>
                        <td class="px-5 py-4 text-gray-600">01033445566</td>
                        <td class="px-5 py-4 text-gray-600">01112344321</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Giza, 6th October<br>District 1, Building 20</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Design 120<br>Olive - L - Full</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 750</td>
                        <td class="px-5 py-4 text-gray-600">EGP 750</td>
                        <td class="px-5 py-4 font-semibold text-gray-400">EGP 0</td>
                        <td class="px-5 py-4"><span class="bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-md">Shipped</span></td>
                        <td class="px-5 py-4"><span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2.5 py-1 rounded-md">New</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                    <!-- Row 6 -->
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-5 py-4 text-gray-600 font-medium">#ORD-1006</td>
                        <td class="px-5 py-4 font-bold text-gray-900">Nourhan Hesham</td>
                        <td class="px-5 py-4 text-gray-600">01077889900</td>
                        <td class="px-5 py-4 text-gray-400">-</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Cairo, Heliopolis<br>Street 8, Building 16</td>
                        <td class="px-5 py-4 text-xs text-gray-500">Design 500<br>Brown - S - Half</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 650</td>
                        <td class="px-5 py-4 text-gray-600">EGP 200</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">EGP 450</td>
                        <td class="px-5 py-4"><span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2.5 py-1 rounded-md">New</span></td>
                        <td class="px-5 py-4"><span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2.5 py-1 rounded-md">New</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- STAFF VIEW Kanban -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col transition-all overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex items-center gap-2 bg-[#111827] text-white px-4 py-2 rounded-xl font-bold text-xs w-max shadow-sm">
                    <i class="fa-solid fa-lock text-[10px]"></i> STAFF VIEW (Limited Details)
                </div>
                <span class="text-[11px] text-gray-400 font-medium hidden md:block">You can only see customer name & phone</span>
            </div>
            <div class="relative w-full sm:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" placeholder="Search by Name or Phone..." class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-gray-300 transition">
            </div>
        </div>
        
        <div class="p-4 overflow-x-auto">
            <div class="flex gap-4 min-w-max">
                
                <!-- Col: New -->
                <div class="w-[260px] flex flex-col h-full bg-white border border-gray-100 rounded-xl shadow-sm">
                    <div class="bg-gray-50 text-center py-2 font-bold text-xs text-gray-600 rounded-t-xl border-b border-gray-100">New</div>
                    <div class="p-3 space-y-3 flex-1 min-h-[300px]">
                        <!-- Card 1 -->
                        <div class="bg-white border border-gray-200 p-4 rounded-lg shadow-sm">
                            <div class="font-bold text-gray-900 text-sm">Ahmed Mostafa</div>
                            <div class="text-xs text-gray-500 mb-3">01012345678</div>
                            <div class="space-y-1.5 mt-2">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Cutter</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Tailor</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Finisher</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                            </div>
                        </div>
                        <!-- Card 2 -->
                        <div class="bg-white border border-gray-200 p-4 rounded-lg shadow-sm">
                            <div class="font-bold text-gray-900 text-sm">Nourhan Hesham</div>
                            <div class="text-xs text-gray-500 mb-3">01077889900</div>
                            <div class="space-y-1.5 mt-2">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Cutter</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Tailor</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Finisher</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 text-center text-gray-400 hover:text-gray-600 text-xs font-semibold cursor-pointer border-t border-gray-100 transition">+ Add Card</div>
                </div>

                <!-- Col: Confirmed -->
                <div class="w-[260px] flex flex-col h-full bg-white border border-gray-100 rounded-xl shadow-sm">
                    <div class="bg-[#e0e7ff] text-center py-2 font-bold text-xs text-[#3730a3] rounded-t-xl border-b border-gray-100">Confirmed</div>
                    <div class="p-3 space-y-3 flex-1 min-h-[300px]">
                        <!-- Card -->
                        <div class="bg-white border border-gray-200 p-4 rounded-lg shadow-sm">
                            <div class="font-bold text-gray-900 text-sm">Sara Mohamed</div>
                            <div class="text-xs text-gray-500 mb-3">01098765432</div>
                            <div class="space-y-1.5 mt-2">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Cutter</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Tailor</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Finisher</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 text-center text-gray-400 hover:text-gray-600 text-xs font-semibold cursor-pointer border-t border-gray-100 transition">+ Add Card</div>
                </div>

                <!-- Col: Cutting -->
                <div class="w-[260px] flex flex-col h-full bg-white border border-gray-100 rounded-xl shadow-sm">
                    <div class="bg-yellow-100 text-center py-2 font-bold text-xs text-yellow-800 rounded-t-xl border-b border-gray-100">Cutting</div>
                    <div class="p-3 space-y-3 flex-1 min-h-[300px]">
                        <!-- Card -->
                        <div class="bg-white border border-gray-200 p-4 rounded-lg shadow-sm">
                            <div class="font-bold text-gray-900 text-sm">Omar Ashraf</div>
                            <div class="text-xs text-gray-500 mb-3">01122334455</div>
                            <div class="space-y-1.5 mt-2">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Cutter</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Tailor</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Finisher</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 text-center text-gray-400 hover:text-gray-600 text-xs font-semibold cursor-pointer border-t border-gray-100 transition">+ Add Card</div>
                </div>

                <!-- Col: Sewing -->
                <div class="w-[260px] flex flex-col h-full bg-white border border-gray-100 rounded-xl shadow-sm">
                    <div class="bg-purple-100 text-center py-2 font-bold text-xs text-purple-800 rounded-t-xl border-b border-gray-100">Sewing</div>
                    <div class="p-3 space-y-3 flex-1 min-h-[300px]">
                        <!-- Card -->
                        <div class="bg-white border border-gray-200 p-4 rounded-lg shadow-sm">
                            <div class="font-bold text-gray-900 text-sm">Yara Magdy</div>
                            <div class="text-xs text-gray-500 mb-3">01066778899</div>
                            <div class="space-y-1.5 mt-2">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Cutter</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Tailor</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-14 text-gray-500 font-medium">Finisher</span>
                                    <input type="text" class="flex-1 border border-gray-200 rounded px-2 py-1 outline-none focus:border-gray-300 text-xs">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 text-center text-gray-400 hover:text-gray-600 text-xs font-semibold cursor-pointer border-t border-gray-100 transition">+ Add Card</div>
                </div>

                <!-- Col: Finishing -->
                <div class="w-[260px] flex flex-col h-full bg-white border border-gray-100 rounded-xl shadow-sm">
                    <div class="bg-blue-100 text-center py-2 font-bold text-xs text-blue-800 rounded-t-xl border-b border-gray-100">Finishing</div>
                    <div class="p-6 flex-1 min-h-[300px] flex flex-col items-center justify-center text-gray-300">
                        <div class="mb-2 w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center border border-dashed border-gray-300">
                            <i class="fa-solid fa-layer-group text-xl"></i>
                        </div>
                        <span class="text-[11px] font-medium uppercase tracking-wide">No orders</span>
                    </div>
                    <div class="p-3 text-center text-gray-400 hover:text-gray-600 text-xs font-semibold cursor-pointer border-t border-gray-100 transition">+ Add Card</div>
                </div>

                <!-- Col: Ready to Ship -->
                <div class="w-[260px] flex flex-col h-full bg-white border border-gray-100 rounded-xl shadow-sm">
                    <div class="bg-teal-100 text-center py-2 font-bold text-xs text-teal-800 rounded-t-xl border-b border-gray-100">Ready to Ship</div>
                    <div class="p-6 flex-1 min-h-[300px] flex flex-col items-center justify-center text-gray-300">
                        <div class="mb-2 w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center border border-dashed border-gray-300">
                            <i class="fa-solid fa-box text-xl"></i>
                        </div>
                        <span class="text-[11px] font-medium uppercase tracking-wide">No orders</span>
                    </div>
                    <div class="p-3 text-center text-gray-400 hover:text-gray-600 text-xs font-semibold cursor-pointer border-t border-gray-100 transition">+ Add Card</div>
                </div>

            </div>
        </div>
    </div>
@endsection