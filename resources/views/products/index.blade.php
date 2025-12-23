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
