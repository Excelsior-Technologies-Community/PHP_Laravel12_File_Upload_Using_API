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
