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
