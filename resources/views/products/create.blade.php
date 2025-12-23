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
