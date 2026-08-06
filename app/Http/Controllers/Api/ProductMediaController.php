<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductMediaController extends Controller
{
    protected function uploadFile(Request $request, $file, $productId, $isPrimary = false)
    {
        $allowedImages = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $allowedVideos = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
        $allowedAudio = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3', 'audio/aac'];
        $allowedDocs = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'text/plain'];

        $allMimes = array_merge($allowedImages, $allowedVideos, $allowedAudio, $allowedDocs);
        $maxSize = 10 * 1024 * 1024; // 10MB

        if ($file->getSize() > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds 10MB limit'];
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allMimes)) {
            return ['success' => false, 'message' => 'Unsupported file type: ' . $mimeType];
        }

        if (in_array($mimeType, $allowedImages)) {
            $fileType = 'image';
        } elseif (in_array($mimeType, $allowedVideos)) {
            $fileType = 'video';
        } elseif (in_array($mimeType, $allowedAudio)) {
            $fileType = 'audio';
        } else {
            $fileType = 'document';
        }

        $disk = 'public';
        $folder = 'products/' . $productId;
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = $folder . '/' . $fileName;

        Storage::disk($disk)->put($filePath, file_get_contents($file), 'public');

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

        $media = ProductMedia::create([
            'product_id' => $productId,
            'file_name' => $fileName,
            'file_path' => 'app/public/' . $filePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'thumbnail_path' => $thumbnailPath ? 'app/public/' . $thumbnailPath : null,
            'alt_text' => $request->alt_text ?? null,
            'is_primary' => $isPrimary,
        ]);

        return [
            'success' => true,
            'media' => $media,
            'url' => Storage::disk($disk)->url($filePath),
            'thumbnail_url' => $thumbnailPath ? Storage::disk($disk)->url($thumbnailPath) : null,
        ];
    }

    public function uploadMultiple(Request $request, $productId)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'file|max:10240',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($productId);
        $uploaded = [];
        $errors = [];

        foreach ($request->file('files') as $index => $file) {
            $isPrimary = ($index === 0 && !$product->media()->where('is_primary', true)->exists());
            $result = $this->uploadFile($request, $file, $productId, $isPrimary);
            if ($result['success']) {
                $uploaded[] = $result;
            } else {
                $errors[] = $result['message'];
            }
        }

        return response()->json([
            'status' => true,
            'message' => count($uploaded) . ' file(s) uploaded successfully',
            'data' => ProductMedia::where('product_id', $productId)->get(),
            'errors' => $errors,
        ], 201);
    }

    public function uploadBase64(Request $request, $productId)
    {
        $request->validate([
            'base64' => 'required|string',
            'file_name' => 'required|string|max:255',
            'mime_type' => 'nullable|string',
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
        ]);

        $product = Product::findOrFail($productId);

        $base64 = $request->base64;
        if (preg_match('/^data:([a-zA-Z0-9\/\+]+);base64,/', $base64, $matches)) {
            $mimeType = $matches[1];
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } else {
            $mimeType = $request->mime_type ?? 'image/jpeg';
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
            return response()->json(['status' => false, 'message' => 'Invalid base64 data'], 422);
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

        if ($request->is_primary) {
            ProductMedia::where('product_id', $productId)->update(['is_primary' => false]);
        }

        $media = ProductMedia::create([
            'product_id' => $productId,
            'file_name' => $request->file_name,
            'file_path' => 'app/public/' . $filePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => strlen($fileData),
            'thumbnail_path' => $thumbnailPath ? 'app/public/' . $thumbnailPath : null,
            'alt_text' => $request->alt_text,
            'is_primary' => $request->is_primary ?? false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'File uploaded via base64 successfully',
            'data' => $media,
            'url' => Storage::disk($disk)->url($filePath),
            'thumbnail_url' => $thumbnailPath ? Storage::disk($disk)->url($thumbnailPath) : null,
        ], 201);
    }

    public function index($productId)
    {
        $product = Product::findOrFail($productId);
        $media = ProductMedia::where('product_id', $productId)->orderBy('is_primary', 'desc')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $media,
        ]);
    }

    public function show($productId, $id)
    {
        $media = ProductMedia::where('product_id', $productId)->where('id', $id)->first();
        if (!$media) {
            return response()->json(['status' => false, 'message' => 'Media not found'], 404);
        }
        return response()->json(['status' => true, 'data' => $media]);
    }

    public function update(Request $request, $productId, $id)
    {
        $media = ProductMedia::where('product_id', $productId)->where('id', $id)->first();
        if (!$media) {
            return response()->json(['status' => false, 'message' => 'Media not found'], 404);
        }

        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
        ]);

        if ($request->has('is_primary') && $request->is_primary) {
            ProductMedia::where('product_id', $productId)->update(['is_primary' => false]);
        }

        $media->update($request->only(['alt_text', 'is_primary']));

        return response()->json([
            'status' => true,
            'message' => 'Media updated successfully',
            'data' => $media,
        ]);
    }

    public function destroy($productId, $id)
    {
        $media = ProductMedia::where('product_id', $productId)->where('id', $id)->first();
        if (!$media) {
            return response()->json(['status' => false, 'message' => 'Media not found'], 404);
        }

        Storage::disk('public')->delete(str_replace('app/public/', '', $media->file_path));
        if ($media->thumbnail_path) {
            Storage::disk('public')->delete(str_replace('app/public/', '', $media->thumbnail_path));
        }

        $media->delete();

        return response()->json([
            'status' => true,
            'message' => 'Media deleted successfully',
        ]);
    }

    public function setPrimary($productId, $id)
    {
        ProductMedia::where('product_id', $productId)->update(['is_primary' => false]);
        $media = ProductMedia::where('product_id', $productId)->where('id', $id)->first();
        if (!$media) {
            return response()->json(['status' => false, 'message' => 'Media not found'], 404);
        }
        $media->update(['is_primary' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Primary media updated',
            'data' => $media,
        ]);
    }
}
