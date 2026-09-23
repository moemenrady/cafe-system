# ☕ Cafe Management System & POS Thermal Printing Suite

نظام متكامل لإدارة الكافيهات ونقاط البيع (POS) مع دعم الطباعة الحرارية المباشرة عبر بروتوكول **ESC/POS** والـ WebSockets المعتمد على **Laravel Reverb**.

---

## 🚀 دليل إطلاق وتحديث نسخة جديدة من برنامج الطباعة (Node.js Agent OTA Update Guide)

يحتوي النظام على ميزة **التحديث التلقائي عبر الهواء (Over-The-Air Auto-Update)** لبرنامج الطباعة المثبت على أجهزة الكاشير بنظام Windows. يوضح هذا الدليل الخطوات التفصيلية خطوة بخطوة لبناء النسخة الجديدة ونشرها وتفعيل التحديث التلقائي لكافة الأجهزة.

---

### 1. أين يتم وضع الملف المترجم `.exe` في لارافيل؟

المكان القياسي والأفضل لوضع ملف الـ `.exe` المترجم هو داخل مجلد **`public/downloads/`** في مشروع Laravel:

```text
cafe-system/
├── app/
├── bootstrap/
├── config/
├── nodejs_v2/              <-- مشروع برنامج الطباعة (Node.js)
│   ├── dist/
│   │   └── pos-agent.exe   <-- الملف المترجم الناتج من عملية الـ Build
│   └── package.json
├── public/                 <-- المجلد العام المتاح مباشرة عبر الويب
│   ├── downloads/          <-- المجلد المخصص لملفات التحميل
│   │   └── pos-agent-latest.exe  <-- ضع النسخة الأحدث هنا
│   ├── index.php
│   └── ...
└── routes/
    └── api.php             <-- مسار فحص وتوجيه التحديثات
```

> **المسار الكامل للملف على السيرفر:**  
> `your-laravel-project/public/downloads/pos-agent-latest.exe`

---

### 2. لماذا يتم وضع الملف داخل `public/downloads/`؟

1. **تحميل مباشر وسريع (Direct HTTP Streaming):**  
   أي ملف موجود داخل `public/` يتم تقديمه مباشرة عبر خادم الويب (Nginx / Apache / PHP Built-in Server) بأقصى سرعة ودون استهلاك ذاكرة PHP أو طوابير المعالجة (Queues).
2. **دعم استكمال التحميل والتقطيع (Chunked Downloads):**  
   يقوم برنامج Node.js بتحميل الملف كـ Stream مجزأ مع رمز استجابة قياسي `200 OK` مما يمنع انقطاع التحميل أو تلف الملف التنفيذي أثناء التنزيل.
3. **رابط موحد ومباشر (Zero Configuration):**  
   يمكن الوصول إلى الملف وتوليد رابطه التلقائي في لارافيل عبر الدالة المساعدة:
   ```php
   url('/downloads/pos-agent-latest.exe')
   ```

---

### 3. تهيئة مسار فحص التحديثات في `routes/api.php`

في ملف [routes/api.php](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/api.php)، يتم فحص رقم الإصدار الحالي لجهاز الكاشير ومقارنته برقم الإصدار الأحدث:

```php
/*
|--------------------------------------------------------------------------
| Over-The-Air Auto Update Endpoint
|--------------------------------------------------------------------------
*/
Route::get('/pos/check-update', function (Request $request) {
    // رقم الإصدار الحالي المرسل من جهاز الكاشير (الافتراضي 1.0.0)
    $currentVersion = $request->query('version', '1.0.0');

    // 1. رقم الإصدار الأحدث المتوفر حالياً
    $latestVersion = '1.0.1';

    // 2. رابط التحميل المباشر للملف التنفيذي
    $downloadUrl = url('/downloads/pos-agent-latest.exe');

    // مقارنة الإصدارات لمعرفة إذا كان هناك تحديث متوفر
    $updateAvailable = version_compare($currentVersion, $latestVersion, '<');

    return response()->json([
        'latest_version'   => $latestVersion,
        'update_available' => $updateAvailable,
        'download_url'     => $updateAvailable ? $downloadUrl : null,
        'mandatory'        => false,
    ]);
});
```

---

### 4. خطوات إصدار ونشر نسخة جديدة خطوة بخطوة (Step-by-Step Release Workflow)

عند إجراء أي تعديلات برمجية على برنامج الطباعة في `nodejs_v2` والرغبة في إرسال التحديث لجميع الأجهزة:

#### الخطوة 1: تحديث رقم الإصدار في ملفات الإعدادات
افتح الملفين التاليين وقم برفع رقم الإصدار (مثال من `1.0.0` إلى `1.0.1`):
- `nodejs_v2/package.json`:
  ```json
  "version": "1.0.1"
  ```
- `nodejs_v2/config.json`:
  ```json
  "app_version": "1.0.1"
  ```

#### الخطوة 2: بناء وترجمة الملف التنفيذي للويندوز
افتح موجه الأوامر (Terminal) في مجلد `nodejs_v2` ونفّذ أمر البناء:
```bash
cd nodejs_v2
npm run build:win
```
*سيتم إنشاء الملف التنفيذي داخل مجلد `nodejs_v2/dist/pos-agent.exe`.*

#### الخطوة 3: نسخ الملف إلى مجلد التحميلات في لارافيل
انسخ الملف الناتج واستبدل به النسخة السابقة في لارافيل باسم `pos-agent-latest.exe`:

* **على نظام macOS / Linux:**
  ```bash
  cp nodejs_v2/dist/pos-agent.exe public/downloads/pos-agent-latest.exe
  ```
* **على نظام Windows (CMD):**
  ```cmd
  copy nodejs_v2\dist\pos-agent.exe public\downloads\pos-agent-latest.exe
  ```

#### الخطوة 4: تفعيل التحديث في لارافيل
افتح ملف [routes/api.php](file:///Users/axon/Desktop/my_laravel_project/cafe-system-1/routes/api.php) وعدّل متغير `$latestVersion` إلى رقم الإصدار الجديد:
```php
$latestVersion = '1.0.1';
```

---

### 5. كيف تعمل عملية التحديث على جهاز العميل تلقائياً؟

بمجرد حفظ رقم الإصدار الجديد في لارافيل:
1. **اكتشاف التحديث:** يقوم برنامج الـ Agent بفحص الرابط `GET /api/pos/check-update?version=1.0.0` تلقائياً كل 6 ساعات، وعند كل إعادة تشغيل، أو يدوياً عبر زر "فحص التحديثات" في لوحة تحكم الـ Agent.
2. **تنزيل آمن مجزأ:** يستلم الـ Agent رد السيرفر بوجود تحديث جديد، ويبدأ بتحميل `pos-agent-latest.exe` في الخلفية كملف مؤقت `pos-agent-new.exe`.
3. **الاستبدال الذري وإعادة التشغيل (Hot Swap on Windows):**
   - يُنشئ البرنامج ملف باتش مؤقت `updater.bat`.
   - يقوم بإنهاء البرنامج القديم لتحرير قفل الملف (Release File Lock).
   - يستبدل `pos-agent.exe` بالملف الجديد.
   - يعيد تشغيل البرنامج بالإصدار الجديد فوراً ويحذف الملف المؤقت.

---

### 6. التحقق واختبار نقطة النهاية (Verification)

يمكنك التأكد من جاهزية الرابط في أي وقت عبر تشغيل أمر `curl` أو فتحه في المتصفح:

```bash
curl -X GET "http://127.0.0.1:8000/api/pos/check-update?version=1.0.0"
```

النتيجة المتوقعة عند توفر التحديث:
```json
{
  "latest_version": "1.0.1",
  "update_available": true,
  "download_url": "http://127.0.0.1:8000/downloads/pos-agent-latest.exe",
  "mandatory": false
}
```

---

## 🛠️ أوامر التشغيل السريع للتطوير (Development)

| الخدمة | الأمر | الوصف |
| :--- | :--- | :--- |
| **Laravel Web Server** | `php artisan serve` | تشغيل سيرفر الويب وتطبيق الكافيه |
| **Laravel Reverb** | `php artisan reverb:start` | خادم الـ WebSockets لبث أحداث الطباعة |
| **Vite Frontend Assets** | `npm run dev` | معالجة ملفات CSS والـ JavaScript للـ POS |
| **Node.js Print Agent** | `cd nodejs_v2 && npm start` | تشغيل برنامج الطباعة وربطه بالطابعات |
| **Virtual Thermal Printer** | `cd nodejs_v2 && npm run virtual-printer` | محاكي طابعات وهمي (منافذ 9101 و 9102) للتجربة بدون طابعة حقيقية |
