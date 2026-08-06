<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->get();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|min:3|max:255',
            'details' => 'required|min:10',
            'size' => 'required',
            'color' => 'required',
            'category' => 'required',
            'files.*' => 'nullable|file|max:10240',
            'base64_files' => 'nullable|string',
        ]);

        $product = Product::create([
            'product_name' => $request->product_name,
            'details' => $request->details,
            'size' => $request->size,
            'color' => $request->color,
            'category' => $request->category,
            'image' => null,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                $this->storeMedia($product->id, $file, $index === 0);
            }
        }

        if ($request->base64_files) {
            $base64Files = json_decode($request->base64_files, true);
            if (is_array($base64Files)) {
                foreach ($base64Files as $index => $base64File) {
                    $this->storeBase64Media($product->id, $base64File, $index === 0);
                }
            }
        }

        return redirect()->route('products.index')
            ->with('success', 'Product Created Successfully');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'product_name' => 'required|min:3|max:255',
            'details' => 'required|min:10',
            'size' => 'required',
            'color' => 'required',
            'category' => 'required',
            'files.*' => 'nullable|file|max:10240',
            'base64_files' => 'nullable|string',
            'remove_media' => 'nullable|array',
        ]);

        $imageName = $product->image;

        if ($request->hasFile('image')) {
            $oldImagePath = public_path('image/' . $product->image);
            if ($product->image && file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        $product->update([
            'product_name' => $request->product_name,
            'details' => $request->details,
            'image' => $imageName,
            'size' => $request->size,
            'color' => $request->color,
            'category' => $request->category,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeMedia($product->id, $file);
            }
        }

        if ($request->base64_files) {
            $base64Files = json_decode($request->base64_files, true);
            if (is_array($base64Files)) {
                foreach ($base64Files as $base64File) {
                    $this->storeBase64Media($product->id, $base64File);
                }
            }
        }

        if ($request->remove_media) {
            foreach ($request->remove_media as $mediaId) {
                $media = ProductMedia::where('product_id', $product->id)->where('id', $mediaId)->first();
                if ($media) {
                    Storage::disk('public')->delete(str_replace('app/public/', '', $media->file_path));
                    if ($media->thumbnail_path) {
                        Storage::disk('public')->delete(str_replace('app/public/', '', $media->thumbnail_path));
                    }
                    $media->delete();
                }
            }
        }

        return redirect()->route('products.index')
            ->with('success', 'Product Updated Successfully');
    }

    public function destroy(Product $product)
    {
        $imagePath = public_path('image/' . $product->image);
        if ($product->image && file_exists($imagePath)) {
            unlink($imagePath);
        }

        foreach ($product->media as $media) {
            Storage::disk('public')->delete(str_replace('app/public/', '', $media->file_path));
            if ($media->thumbnail_path) {
                Storage::disk('public')->delete(str_replace('app/public/', '', $media->thumbnail_path));
            }
        }

        $product->media()->delete();
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product Deleted Successfully');
    }

    public function apiIndex(Request $request)
    {
        $query = Product::query();
        if ($request->has('search')) {
            $query->where('product_name', 'like', '%' . $request->search . '%');
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('sort')) {
            $query->orderBy('product_name', $request->sort);
        } else {
            $query->latest();
        }
        $products = $query->paginate($request->per_page ?? 15);
        return response()->json([
            'status' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'product_name' => 'required|min:3|max:255',
            'details' => 'required|min:10',
            'size' => 'required',
            'color' => 'required',
            'category' => 'required',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp,svg|max:10240',
            'files.*' => 'nullable|file|max:10240',
            'base64_files' => 'nullable|string',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        $product = Product::create([
            'product_name' => $request->product_name,
            'details' => $request->details,
            'image' => $imageName,
            'size' => $request->size,
            'color' => $request->color,
            'category' => $request->category,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                $this->storeMedia($product->id, $file, $index === 0);
            }
        }

        if ($request->base64_files) {
            $base64Files = json_decode($request->base64_files, true);
            if (is_array($base64Files)) {
                foreach ($base64Files as $index => $base64File) {
                    $this->storeBase64Media($product->id, $base64File, $index === 0);
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Product Created Successfully',
            'data' => new ProductResource($product->load('media')),
        ], 201);
    }

    public function apiShow($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product Not Found'], 404);
        }
        return response()->json(['status' => true, 'data' => new ProductResource($product->load('media'))]);
    }

    public function apiUpdate(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product Not Found'], 404);
        }

        $request->validate([
            'product_name' => 'nullable|min:3|max:255',
            'details' => 'nullable|min:10',
            'size' => 'nullable',
            'color' => 'nullable',
            'category' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp,svg|max:10240',
            'files.*' => 'nullable|file|max:10240',
            'base64_files' => 'nullable|string',
            'remove_media' => 'nullable|array',
        ]);

        $imageName = $product->image;
        if ($request->hasFile('image')) {
            $oldImagePath = public_path('image/' . $product->image);
            if ($product->image && file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        $product->update([
            'product_name' => $request->product_name ?? $product->product_name,
            'details' => $request->details ?? $product->details,
            'image' => $imageName,
            'size' => $request->size ?? $product->size,
            'color' => $request->color ?? $product->color,
            'category' => $request->category ?? $product->category,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeMedia($product->id, $file);
            }
        }

        if ($request->base64_files) {
            $base64Files = json_decode($request->base64_files, true);
            if (is_array($base64Files)) {
                foreach ($base64Files as $base64File) {
                    $this->storeBase64Media($product->id, $base64File);
                }
            }
        }

        if ($request->remove_media) {
            foreach ($request->remove_media as $mediaId) {
                $media = ProductMedia::where('product_id', $product->id)->where('id', $mediaId)->first();
                if ($media) {
                    Storage::disk('public')->delete(str_replace('app/public/', '', $media->file_path));
                    if ($media->thumbnail_path) {
                        Storage::disk('public')->delete(str_replace('app/public/', '', $media->thumbnail_path));
                    }
                    $media->delete();
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Product Updated Successfully',
            'data' => new ProductResource($product->fresh()->load('media')),
        ]);
    }

    public function apiDelete($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product Not Found'], 404);
        }

        $imagePath = public_path('image/' . $product->image);
        if ($product->image && file_exists($imagePath)) {
            unlink($imagePath);
        }

        foreach ($product->media as $media) {
            Storage::disk('public')->delete(str_replace('app/public/', '', $media->file_path));
            if ($media->thumbnail_path) {
                Storage::disk('public')->delete(str_replace('app/public/', '', $media->thumbnail_path));
            }
        }

        $product->media()->delete();
        $product->delete();

        return response()->json(['status' => true, 'message' => 'Product Deleted Successfully']);
    }

    private function storeMedia($productId, $file, $isPrimary = false)
    {
        $allowedImages = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $allowedVideos = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
        $allowedAudio = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3', 'audio/aac'];
        $allowedDocs = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'text/plain'];

        $allMimes = array_merge($allowedImages, $allowedVideos, $allowedAudio, $allowedDocs);
        $maxSize = 10 * 1024 * 1024;

        if ($file->getSize() > $maxSize) {
            return;
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allMimes)) {
            return;
        }

        $disk = 'public';
        $folder = 'products/' . $productId;
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = $folder . '/' . $fileName;

        Storage::disk($disk)->put($filePath, file_get_contents($file), 'public');

        $fileType = 'image';
        if (in_array($mimeType, $allowedVideos)) {
            $fileType = 'video';
        } elseif (in_array($mimeType, $allowedAudio)) {
            $fileType = 'audio';
        } elseif ($mimeType === 'application/pdf') {
            $fileType = 'document';
        }

        $thumbnailPath = null;
        if ($fileType === 'image') {
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->decodePath(Storage::disk($disk)->path($filePath));
                $thumbnail = $image->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $thumbnailFileName = 'thumb_' . $fileName;
                $thumbnailPath = $folder . '/' . $thumbnailFileName;
                Storage::disk($disk)->put($thumbnailPath, (string) $thumbnail->encode());
            } catch (\Exception $e) {
                $thumbnailPath = null;
            }
        }

        if ($isPrimary) {
            ProductMedia::where('product_id', $productId)->update(['is_primary' => false]);
        }

        ProductMedia::create([
            'product_id' => $productId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => 'app/public/' . $filePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'thumbnail_path' => $thumbnailPath ? 'app/public/' . $thumbnailPath : null,
            'is_primary' => $isPrimary,
        ]);
    }

    private function storeBase64Media($productId, $base64File, $isPrimary = false)
    {
        $base64 = $base64File['base64'] ?? $base64File;
        $fileName = $base64File['file_name'] ?? 'base64_image';
        $mimeType = $base64File['mime_type'] ?? 'image/jpeg';
        $altText = $base64File['alt_text'] ?? null;

        if (preg_match('/^data:([a-zA-Z0-9\/\+]+);base64,/', $base64, $matches)) {
            $mimeType = $matches[1];
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $disk = 'public';
        $folder = 'products/' . $productId;
        $extension = explode('/', $mimeType)[1] ?? 'jpg';
        if (in_array($extension, ['jpeg', 'jpg'])) {
            $extension = 'jpg';
        }
        $fileName = time() . '_base64_' . uniqid() . '.' . $extension;
        $filePath = $folder . '/' . $fileName;

        $fileData = base64_decode($base64);
        if ($fileData === false) {
            return;
        }

        Storage::disk($disk)->put($filePath, $fileData, 'public');

        $fileType = 'image';
        if (str_starts_with($mimeType, 'video/')) {
            $fileType = 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            $fileType = 'audio';
        } elseif ($mimeType === 'application/pdf') {
            $fileType = 'document';
        }

        $thumbnailPath = null;
        if ($fileType === 'image') {
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->decodePath(Storage::disk($disk)->path($filePath));
                $thumbnail = $image->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $thumbnailFileName = 'thumb_' . $fileName;
                $thumbnailPath = $folder . '/' . $thumbnailFileName;
                Storage::disk($disk)->put($thumbnailPath, (string) $thumbnail->encode());
            } catch (\Exception $e) {
                $thumbnailPath = null;
            }
        }

        if ($isPrimary) {
            ProductMedia::where('product_id', $productId)->update(['is_primary' => false]);
        }

        ProductMedia::create([
            'product_id' => $productId,
            'file_name' => $fileName,
            'file_path' => 'app/public/' . $filePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => strlen($fileData),
            'thumbnail_path' => $thumbnailPath ? 'app/public/' . $thumbnailPath : null,
            'alt_text' => $altText,
            'is_primary' => $isPrimary,
        ]);
    }
}
