# PHP_Laravel12_File_Upload_Using_API

# STEP 1: Install Laravel 12 Project
Open terminal / command prompt and run:
```php
composer create-project laravel/laravel your folder name
```
Explanation:
This command installs a fresh Laravel 12 project
Project folder name will be your folder name 

# STEP 2: Go to Project Directory
```php
cd your folder
```

# STEP 3: Database Configuration
Open .env file and update database details:
```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your database
DB_USERNAME=root
DB_PASSWORD=
```
Explanation:
Connects Laravel project to MySQL database
Make sure database name  exists in phpMyAdmin

# STEP 4: Create Products Migration
Run this command:
```php
php artisan make:migration create_products_table
```
Explanation:
Creates a migration file for products table

# STEP 5: Write Products Table Schema
Open migration file from:
database/migrations/xxxx_xx_xx_create_products_table.php
Add this code:
```php
public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('product_name');
        $table->text('details');
        $table->string('image')->nullable();//with file upload system
        $table->string('size');
        $table->string('color');
        $table->string('category');
        $table->timestamps();
    });
}
```
Explanation:
Defines columns for products table
image is nullable because image upload is optional

# STEP 6: Run Migration
```php
php artisan migrate
```
Explanation:
Creates products table in database

# STEP 7: Create Product Model
```php
php artisan make:model Product
```

# STEP 8: Product Model Code
 app/Models/Product.php
 ```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_name',
        'details',
        'image',
        'size',
        'color',
        'category'
    ];
}
```
Explanation:
$fillable allows mass assignment when creating products

# STEP 9: Create Product Controller
```php
php artisan make:controller ProductController
```
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // ============================================================
    //  WEB ROUTES METHODS (For Blade Views - Admin Panel)
    // ============================================================

    /**
     * DISPLAY ALL PRODUCTS LIST
     * 
     * This method fetches all products from database and shows them in index view
     * latest() = Orders by 'created_at' column DESC (newest first)
     */
    public function index()
    {
        $products = Product::latest()->get(); 
        // get() = Returns collection of all matching records
        return view('products.index', compact('products'));
        // compact('products') = Passes $products variable to Blade view
    }

    /**
     * SHOW CREATE PRODUCT FORM
     * 
     * Empty method - just returns the create form view
     * Form will have input fields for all product data
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     *  CREATE NEW PRODUCT (Store Method)
     * 
     * 1. Validates input data
     * 2. Handles image upload to public/image folder
     * 3. Saves product record to database
     * 4. Redirects to products list
     */
    public function store(Request $request)
    {
        // ========================================
        //  STEP 1: VALIDATE INPUT DATA
        // ========================================
        $request->validate([
            'product_name' => 'required|min:3|max:255',        // Required, 3-255 chars
            'details'      => 'required|min:10',               // Required, min 10 chars
            'image'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048', // Optional image, 2MB max
            'size'         => 'required',                      // Required field
            'color'        => 'required',                      // Required field
            'category'     => 'required',                      // Required field
        ]);

        $imageName = null; // Default: no image

        // ========================================
        //  STEP 2: IMAGE UPLOAD PROCESS (DETAILED)
        // ========================================
        if ($request->hasFile('image')) {
            // CHECK 1: File exists in request?
            // $request->image = Uploaded file object

            // STEP 2.1: CREATE UNIQUE FILENAME
            // Why unique? Prevent overwriting existing files
            // Format: 1703123456_originalname.jpg
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            // time() = Current timestamp (unix seconds)
            // getClientOriginalName() = Original filename from user computer

            // STEP 2.2: CREATE STORAGE FOLDER (if not exists)
            $imagePath = public_path('image');
            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0755, true);
            }

            // STEP 2.3: MOVE FILE FROM TEMP TO PERMANENT LOCATION
            // TEMP LOCATION: /tmp/php12345 (system temp)
            // PERMANENT: /public/image/filename.jpg
            $request->image->move(public_path('image'), $imageName);
            
            // ✅ SUCCESS! Image now at: http://yoursite.com/image/filename.jpg
        }

        // ========================================
        //  STEP 3: SAVE TO DATABASE
        // ========================================
        Product::create([
            'product_name' => $request->product_name,
            'details'      => $request->details,
            'image'        => $imageName,  // Only filename stored (not full path)
            'size'         => $request->size,
            'color'        => $request->color,
            'category'     => $request->category,
        ]);

        // ========================================
        //  STEP 4: REDIRECT WITH SUCCESS MESSAGE
        // ========================================
        return redirect()->route('products.index')
            ->with('success', 'Product Created Successfully');
    }

    /**
     *  SHOW EDIT FORM
     * 
     * Route Model Binding: Laravel automatically finds Product by ID
     * $product = Product with ID from URL
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    /**
     *  UPDATE EXISTING PRODUCT
     * 
     * 1. Keep existing image OR upload new one
     * 2. Delete old image if new one uploaded
     * 3. Update database record
     */
    public function update(Request $request, Product $product)
    {
        // Start with existing image name
        $imageName = $product->image;

        // ========================================
        //  NEW IMAGE UPLOADED? REPLACE OLD ONE
        // ========================================
        if ($request->hasFile('image')) {
            // DELETE OLD IMAGE (Free up storage space)
            $oldImagePath = public_path('image/' . $product->image);
            if ($product->image && file_exists($oldImagePath)) {
                unlink($oldImagePath); // Remove file from server
            }

            // SAVE NEW IMAGE (Same process as store())
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        // ========================================
        //  UPDATE DATABASE
        // ========================================
        $product->update([
            'product_name' => $request->product_name,
            'details'      => $request->details,
            'image'        => $imageName,
            'size'         => $request->size,
            'color'        => $request->color,
            'category'     => $request->category,
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Product Updated Successfully');
    }

    /**
     *  DELETE PRODUCT + IMAGE
     * 
     * 1. Delete image file from server
     * 2. Delete database record
     */
    public function destroy(Product $product)
    {
        // Delete image file first
        $imagePath = public_path('image/' . $product->image);
        if ($product->image && file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete database record
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product Deleted Successfully');
    }
}
```
# STEP 10: Create web Route
 routes/web.php
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::resource('products', ProductController::class);

Route::get('/', function () {
    return view('welcome');
});
```

# Now Create all blade file in index , create and edit method
# STEP 11: Create index.blade.php, create.blade.php and edit.blade.php in resource folder

# Resource/view/create products folder 
Resource/view/products/index.blade.php
```php
<h2>Products</h2>

<a href="{{ route('products.create') }}">Add Product</a>

@if(session('success'))
    <p>{{ session('success') }}</p>
@endif

<table border="1" cellpadding="10">
<tr>
    <th>Name</th>
    <th>Image</th>
    <th>Size</th>
    <th>Color</th>
    <th>Category</th>
    <th>Action</th>
</tr>

@foreach($products as $product)
<tr>
    <td>{{ $product->product_name }}</td>
   <td>
    @if($product->image)
        <!-- ========================================
             COMPLETE IMAGE DISPLAY PROCESS:
             ======================================= -->
        <!-- 1. Database stores FILENAME ONLY: "1703123456_lipstick.jpg" -->
        <!-- 2. File location on server: /public/image/1703123456_lipstick.jpg -->
        <!-- 3. asset() helper generates FULL URL: http://yoursite.com/image/filename.jpg -->
        <!-- 4. Browser loads image from public folder -->
        
        <img src="{{ asset('image/' . $product->image) }}" 
             alt="{{ $product->product_name }}" 
             width="80" 
             height="80"
             style="border-radius: 5px; object-fit: cover;"
             onerror="this.style.display='none'">
             
        <!-- ========================================
             HOW IMAGE URL IS GENERATED:
             ======================================= -->
        <!-- $product->image = "1703123456_lipstick.jpg" -->
        <!-- asset('image/filename') = http://yoursite.com/image/1703123456_lipstick.jpg -->
        <!-- ======================================= -->
        
    @else
        <!-- NO IMAGE UPLOADED -->
        <span style="color: #999; font-style: italic;">No image</span>
    @endif
</td>

    <td>{{ $product->size }}</td>
    <td>{{ $product->color }}</td>
    <td>{{ $product->category }}</td>
    <td>
        <a href="{{ route('products.edit',$product->id) }}">Edit</a>

        <form action="{{ route('products.destroy',$product->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit">Delete</button>
        </form>
    </td>
</tr>
@endforeach

</table>
```

Resource/view/products/create.blade.php
```php
<h2>Add Product</h2>

<form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<input type="text" name="product_name" placeholder="Product Name"><br><br>
<textarea name="details" placeholder="Details"></textarea><br><br>
<!-- ========================================
     COMPLETE IMAGE UPLOAD INPUT PROCESS:
     ======================================= -->
<input type="file" name="image"><br><br>
<!-- ========================================
     HOW THIS WORKS WITH CONTROLLER:
     ======================================= -->
<!-- 1. USER SELECTS FILE: lipstick.jpg (from computer) -->
<!-- 2. FORM SUBMIT: POST /products (multipart/form-data) -->
<!-- 3. LARAVEL RECEIVES: $request->hasFile('image') = true -->
<!-- 4. TEMP STORAGE: /tmp/phpABCD1234 (automatic) -->
<!-- 5. CONTROLLER PROCESSES: -->
<!--    - Validates: image|mimes:jpg,png,jpeg|max:2048 -->
<!--    - Renames: 1703123456_lipstick.jpg -->
<!--    - Moves: /tmp/ → /public/image/ -->
<!-- 6. DATABASE: products.image = "1703123456_lipstick.jpg" -->

<input type="text" name="size" placeholder="Size"><br><br>
<input type="text" name="color" placeholder="Color"><br><br>
<input type="text" name="category" placeholder="Category"><br><br>

<button type="submit">Save</button>
</form>
```

Resource/view/products/edit.blade.php
```php
<h2>Edit Product</h2>

{{-- SUCCESS MESSAGE --}}
@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<form action="{{ route('products.update', $product->id) }}" 
      method="POST" 
      enctype="multipart/form-data">

    @csrf
    @method('PUT')

    {{-- PRODUCT NAME --}}
    <input 
        type="text" 
        name="product_name" 
        value="{{ $product->product_name }}" 
        placeholder="Product Name">
    <br><br>

    {{-- DETAILS --}}
    <textarea 
        name="details" 
        placeholder="Details">{{ $product->details }}</textarea>
    <br><br>

   {{-- ========================================
     COMPLETE IMAGE EDIT SECTION:
     ======================================= --}}
<div style="border: 2px dashed #ddd; padding: 20px; border-radius: 10px; margin: 20px 0;">

    {{-- ========================================
         CURRENT IMAGE DISPLAY (Existing Image)
         ======================================= --}}
    @if($product->image)
        <div style="text-align: center; margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 10px; color: #333;">
                Current Product Image:
            </label>
            
            {{-- IMAGE DISPLAY PROCESS --}}
            {{-- 1. DB stores: "1703123456_lipstick.jpg" --}}
            {{-- 2. File exists: /public/image/1703123456_lipstick.jpg --}}
            {{-- 3. asset() creates: http://yoursite.com/image/1703123456_lipstick.jpg --}}
            <img src="{{ asset('image/' . $product->image) }}" 
                 alt="Current: {{ $product->product_name }}" 
                 width="100" 
                 height="100"
                 style="border-radius: 10px; object-fit: cover; border: 3px solid #007bff;"
                 onerror="this.style.display='none'">
                 
            <p style="color: #666; font-size: 12px; margin-top: 5px;">
                Current: {{ $product->image }}
            </p>
        </div>
    @else
        <div style="text-align: center; padding: 40px; color: #999; font-style: italic;">
            No current image found
        </div>
    @endif

    {{-- ========================================
         NEW IMAGE UPLOAD INPUT
         ======================================= --}}
    <div style="text-align: center;">
        <label style="display: block; font-weight: bold; margin-bottom: 10px; color: #333;">
            Replace with New Image (Optional):
        </label>
        
        {{-- COMPLETE UPLOAD INPUT --}}
        {{-- name="image" matches $request->image in controller --}}
        <input type="file" 
               name="image" 
               accept="image/jpeg,image/jpg,image/png" 
               id="editProductImage"
               style="padding: 12px; 
                      border: 2px dashed #28a745; 
                      border-radius: 8px; 
                      width: 100%; 
                      max-width: 300px;
                      background: #f8f9fa;">
        
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            JPG, PNG only - Max 2MB
        </p>
    </div>

</div>

{{-- ========================================
     COMPLETE UPDATE FLOW SUMMARY:
     ======================================= --}}
{{-- 1. PAGE LOAD: Shows current image from DB --}}
{{-- 2. USER: Selects new image in <input name="image"> --}}
{{-- 3. FORM SUBMIT: POST /products/{id} --}}
{{-- 4. CONTROLLER update(): --}}
{{--    - $request->hasFile('image') = true --}}
{{--    - DELETE: public/image/old_image.jpg --}}
{{--    - SAVE: public/image/1703123456_new_image.jpg --}}
{{--    - DB: products.image = "1703123456_new_image.jpg" --}}
{{-- 5. REDIRECT: Back to index with new image displayed --}}

    {{-- SIZE --}}
    <input 
        type="text" 
        name="size" 
        value="{{ $product->size }}" 
        placeholder="Size">
    <br><br>

    {{-- COLOR --}}
    <input 
        type="text" 
        name="color" 
        value="{{ $product->color }}" 
        placeholder="Color">
    <br><br>

    {{-- CATEGORY --}}
    <input 
        type="text" 
        name="category" 
        value="{{ $product->category }}" 
        placeholder="Category">
    <br><br>

    <button type="submit">Update Product</button>
</form>

<br>
<a href="{{ route('products.index') }}">⬅ Back to Product List</a>
```


# STEP 12: Run Laravel Server
```php
php artisan serve
```
```php
Server URL:	
http://127.0.0.1:8000/products
```
<img width="979" height="503" alt="image" src="https://github.com/user-attachments/assets/3a1d94a5-848d-4bf4-890b-944ff1ceb1cc" />


# Above  this crud is using web route and file upload for image and now create this crud for api route and file and image upload any time please followed all step
# Now added route for routes/api.php file
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// 🔹 PRODUCT API ROUTES
Route::get('/products', [ProductController::class, 'apiIndex']);
Route::post('/products', [ProductController::class, 'apiStore']);
Route::get('/products/{id}', [ProductController::class, 'apiShow']);
Route::post('/products/{id}', [ProductController::class, 'apiUpdate']);
Route::delete('/products/{id}', [ProductController::class, 'apiDelete']);
```

# Now Create for api crud method  and upload for image file and store public folder for existing productcontroller and added this method
```php
// ============================================================
//  API METHODS (JSON Responses for Mobile Apps/Postman)
// ============================================================

/**
 *  API: GET ALL PRODUCTS
 * 
 * URL: GET /api/products
 * 
 * Response:
 * {
 *   "status": true,
 *   "data": [ {products array} ]
 * }
 * 
 * Returns all products ordered by newest first (latest()->get())
 * No image upload - just database query
 */
public function apiIndex(Request $request)
{
    $products = Product::latest()->get();
    return response()->json([
        'status' => true,
        'data' => $products
    ]);
}

/**
 *  API: CREATE NEW PRODUCT
 * 
 * URL: POST /api/products
 * Headers: Content-Type: multipart/form-data
 * 
 * Request Body (Form Data):
 * - product_name (required)
 * - details (required) 
 * - image (optional file)
 * - size (required)
 * - color (required)
 * - category (required)
 * 
 * ========================================
 * COMPLETE IMAGE UPLOAD PROCESS:
 * ========================================
 * 1. Client sends image file via POST
 * 2. Laravel stores in TEMP: /tmp/phpXXXXX.jpg
 * 3. Validate: image|mimes:jpg,png,jpeg|max:2048 (2MB)
 * 4. Generate unique name: time()_original.jpg
 * 5. Move to PERMANENT: public/image/newname.jpg
 * 6. Save filename to database
 * 7. Image accessible: http://yoursite.com/image/filename.jpg
 */
public function apiStore(Request $request)
{
    // STEP 1: VALIDATE ALL FIELDS
    $request->validate([
        'product_name' => 'required|min:3|max:255',     // Name validation
        'details'      => 'required|min:10',            // Description min 10 chars
        'image'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048', // Image rules
        'size'         => 'required',                   // Size required
        'color'        => 'required',                   // Color required
        'category'     => 'required',                   // Category required
    ]);

    $imageName = null; // Default: no image

    // ========================================
    //  STEP 2: IMAGE UPLOAD & STORAGE (DETAILED)
    // ========================================
    if ($request->hasFile('image')) {
        // CHECK: Does request contain image file?
        // $request->image = UploadedFile object

        // 2.1: GENERATE UNIQUE FILENAME
        // Prevents overwriting: 1703123456_lipstick.jpg
        $imageName = time() . '_' . $request->image->getClientOriginalName();
        
        // 2.2: ENSURE FOLDER EXISTS
        $storagePath = public_path('image');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        // 2.3: MOVE FROM TEMPORARY → PUBLIC FOLDER
        // TEMP: /tmp/phpABCD1234 (auto-deleted)
        // PUBLIC: /public/image/1703123456_lipstick.jpg
        $request->image->move(public_path('image'), $imageName);
        
        //  IMAGE READY! URL: http://yoursite.com/image/1703123456_lipstick.jpg
    }

    // ========================================
    //  STEP 3: CREATE DATABASE RECORD
    // ========================================
    $product = Product::create([
        'product_name' => $request->product_name,
        'details'      => $request->details,
        'image'        => $imageName,  // Only filename (not full path)
        'size'         => $request->size,
        'color'        => $request->color,
        'category'     => $request->category,
    ]);

    // ========================================
    //  STEP 4: JSON RESPONSE
    // ========================================
    return response()->json([
        'status' => true,
        'message' => 'Product Created Successfully',
        'data' => $product
    ], 201); // HTTP 201 = Resource Created
}

/**
 *  API: SHOW SINGLE PRODUCT
 * 
 * URL: GET /api/products/{id}
 * 
 * No image upload - just retrieve from database
 * Returns product data including image filename
 * 
 * Response if not found:
 * {
 *   "status": false,
 *   "message": "Product Not Found"
 * }
 */
public function apiShow($id)
{
    $product = Product::find($id); // Find by primary key

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'Product Not Found'
        ], 404); // HTTP 404 = Not Found
    }

    return response()->json([
        'status' => true,
        'data' => $product
    ]);
}

/**
 *  API: UPDATE PRODUCT
 * 
 * URL: PUT /api/products/{id} or PATCH /api/products/{id}
 * Headers: Content-Type: multipart/form-data
 * 
 * Request Body (Form Data):
 * - product_name (optional - uses ?? null coalescing)
 * - details (optional)
 * - image (optional - replaces old image)
 * - size, color, category (optional)
 * 
 * ========================================
 * IMAGE UPDATE PROCESS:
 * ========================================
 * 1. Keep existing image by default
 * 2. If NEW image uploaded:
 *    a. DELETE old image file
 *    b. Save new image with unique name
 *    c. Update database with new filename
 */
public function apiUpdate(Request $request, $id)
{
    // STEP 1: FIND PRODUCT
    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'Product Not Found'
        ], 404);
    }

    // STEP 2: SET EXISTING IMAGE AS DEFAULT
    $imageName = $product->image; // Keep old image initially

    // ========================================
    //  STEP 3: NEW IMAGE? REPLACE OLD ONE
    // ========================================
    if ($request->hasFile('image')) {
        // 3.1: DELETE OLD IMAGE FILE
        $oldImagePath = public_path('image/' . $product->image);
        if ($product->image && file_exists($oldImagePath)) {
            unlink($oldImagePath); // Remove from server
            // Prevents storage bloat from unused images
        }

        // 3.2: SAVE NEW IMAGE (Same as apiStore)
        $imageName = time() . '_' . $request->image->getClientOriginalName();
        $request->image->move(public_path('image'), $imageName);
        
        // ✅ New image replaces old one completely
    }

    // ========================================
    //  STEP 4: UPDATE DATABASE
    // ========================================
    // ?? = Null coalescing: use new value OR keep old value
    $product->update([
        'product_name' => $request->product_name ?? $product->product_name,
        'details'      => $request->details ?? $product->details,
        'image'        => $imageName,
        'size'         => $request->size ?? $product->size,
        'color'        => $request->color ?? $product->color,
        'category'     => $request->category ?? $product->category,
    ]);

    // ========================================
    //  STEP 5: RETURN UPDATED DATA
    // ========================================
    return response()->json([
        'status' => true,
        'message' => 'Product Updated Successfully',
        'data' => $product->fresh() // Reload from DB with latest changes
    ]);
}

/**
 *  API: DELETE PRODUCT
 * 
 * URL: DELETE /api/products/{id}
 * 
 * ========================================
 * COMPLETE DELETION PROCESS:
 * ========================================
 * 1. Find product by ID
 * 2. DELETE IMAGE FILE from public/image/
 * 3. DELETE DATABASE RECORD
 * 4. Return success response
 */
public function apiDelete($id)
{
    // STEP 1: FIND PRODUCT
    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'Product Not Found'
        ], 404);
    }

    // ========================================
    //  STEP 2: DELETE IMAGE FILE
    // ========================================
    $imagePath = public_path('image/' . $product->image);
    if ($product->image && file_exists($imagePath)) {
        unlink($imagePath); // Remove physical file
        // file_exists() = Safety check before delete
    }

    // ========================================
    //  STEP 3: DELETE DATABASE RECORD
    // ========================================
    $product->delete();

    // ========================================
    //  STEP 4: SUCCESS RESPONSE
    // ========================================
    return response()->json([
        'status' => true,
        'message' => 'Product Deleted Successfully'
    ]);
}
}
```

# Now Open the postman and run this method and file/image upload and store time 
# Open postman 
# Select method : Post
# Paste this url : http://127.0.0.1:8000/api/products

<img width="628" height="67" alt="image" src="https://github.com/user-attachments/assets/9da886bd-ca7f-4395-88a9-5f1bbafe6862" />


# Now  Body setting most important part for postman because this part for any file and image upload and store for public file and show image for index page 
# Select body and used for form data:

# Key type for image and select file not select for text and value type select your image path:
<img width="628" height="349" alt="image" src="https://github.com/user-attachments/assets/def7892b-4175-43a0-9ab6-a2bd9e42850b" />

# Now Click Send button and show the result:
<img width="628" height="134" alt="image" src="https://github.com/user-attachments/assets/0881d04b-085a-4760-897e-115acc63d86d" />
<img width="131" height="200" alt="image" src="https://github.com/user-attachments/assets/1e9c1d6b-2397-47b9-b2ff-5301285fe547" />



