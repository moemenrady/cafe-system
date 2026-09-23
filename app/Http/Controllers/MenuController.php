<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::with(['menuItems' => function ($q) {
            $q->orderBy('name');
        }])->orderBy('id')->get();

        $menus = Menu::with(['category'])->latest()->get();
        $totalMenuItems = $menus->count();
        $availableCount = $menus->where('is_available', true)->count();
        $unavailableCount = $menus->where('is_available', false)->count();

        return view('menu.index', compact('categories', 'menus', 'totalMenuItems', 'availableCount', 'unavailableCount'));
    }

    /**
     * عرض أو تحميل ملف المينيو PDF الأصلي
     */
    public function viewPdf()
    {
        $pdfPath = public_path('pdf/uno_menu.pdf');

        if (!file_exists($pdfPath)) {
            $source = base_path('UNO MENU PRINT.pdf');
            if (file_exists($source)) {
                if (!is_dir(public_path('pdf'))) {
                    mkdir(public_path('pdf'), 0777, true);
                }
                copy($source, $pdfPath);
            }
        }

        if (file_exists($pdfPath)) {
            return response()->file($pdfPath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="UNO_Cafe_Menu.pdf"',
            ]);
        }

        return redirect()->back()->with('error', 'ملف المينيو PDF غير موجود حالياً.');
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('menu.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'price'        => 'required|numeric|min:0',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'is_available' => 'nullable|boolean',
        ], [
            'name.required'        => 'اسم المنتج مطلوب.',
            'category_id.required' => 'يرجى اختيار القسم.',
            'category_id.exists'   => 'القسم المحدد غير موجود.',
            'price.required'       => 'سعر المنتج مطلوب.',
            'price.numeric'        => 'السعر يجب أن يكون رقماً صحيحاً.',
            'image.image'          => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'image.mimes'          => 'صيغ الصور المسموح بها هي: jpeg, png, jpg, webp, gif.',
            'image.max'            => 'الحد الأقصى لحجم الصورة هو 5 ميجابايت.',
        ]);

        $data = [
            'name'         => $validated['name'],
            'category_id'  => $validated['category_id'],
            'price'        => $validated['price'],
            'is_available' => $request->boolean('is_available', true),
        ];

        // تخزين الصورة في القرص العام public
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $data['image'] = $request->file('image')->store('menu', 'public');
        }

        Menu::create($data);

        return redirect()->route('menu.index')->with('success', 'تم إضافة المنتج وصورته بنجاح.');
    }

    public function edit(Menu $menu)
    {
        $categories = Category::orderBy('name')->get();
        return view('menu.edit', compact('menu', 'categories'));
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'price'        => 'required|numeric|min:0',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'is_available' => 'nullable|boolean',
            'remove_image' => 'nullable|boolean',
        ], [
            'name.required'        => 'اسم المنتج مطلوب.',
            'category_id.required' => 'يرجى اختيار القسم.',
            'category_id.exists'   => 'القسم المحدد غير موجود.',
            'price.required'       => 'سعر المنتج مطلوب.',
            'price.numeric'        => 'السعر يجب أن يكون رقماً صحيحاً.',
            'image.image'          => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'image.mimes'          => 'صيغ الصور المسموح بها هي: jpeg, png, jpg, webp, gif.',
            'image.max'            => 'الحد الأقصى لحجم الصورة هو 5 ميجابايت.',
        ]);

        $data = [
            'name'         => $validated['name'],
            'category_id'  => $validated['category_id'],
            'price'        => $validated['price'],
            'is_available' => $request->boolean('is_available', false),
        ];

        // في حال طلب المستخدم حذف الصورة القديمة
        if ($request->boolean('remove_image')) {
            if ($menu->image && Storage::disk('public')->exists($menu->image)) {
                Storage::disk('public')->delete($menu->image);
            }
            $data['image'] = null;
        }

        // في حال رفع صورة جديدة
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // حذف الصورة القديمة إن وجدت
            if ($menu->image && Storage::disk('public')->exists($menu->image)) {
                Storage::disk('public')->delete($menu->image);
            }
            $data['image'] = $request->file('image')->store('menu', 'public');
        }

        $menu->update($data);

        return redirect()->route('menu.index')->with('success', 'تم تحديث بيانات المنتج والصورة بنجاح.');
    }

    public function destroy(Menu $menu)
    {
        // حذف الصورة المرتبطة من القرص
        if ($menu->image && Storage::disk('public')->exists($menu->image)) {
            Storage::disk('public')->delete($menu->image);
        }

        $menu->delete();

        return redirect()->route('menu.index')->with('success', 'تم حذف المنتج وصورته بنجاح.');
    }
}
