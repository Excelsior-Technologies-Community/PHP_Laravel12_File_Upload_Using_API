<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMediaController extends Controller
{
    /**
     * Display media for a product.
     *
     * Supports:
     * - Search
     * - File type filter
     * - Primary media filter
     * - Sorting
     * - Pagination
     *
     * GET /api/products/{productId}/media
     */
    public function index(Request $request, $productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $request->validate([
            'search' => 'nullable|string|max:255',

            'file_type' => [
                'nullable',
                'string',
                'in:image,video,audio,document',
            ],

            'is_primary' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                'string',
                'in:name,size,date',
            ],

            'sort_order' => [
                'nullable',
                'string',
                'in:asc,desc',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $query = ProductMedia::where('product_id', $productId);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhere('mime_type', 'like', "%{$search}%")
                    ->orWhere('file_type', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILE TYPE FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('file_type')) {
            $query->where(
                'file_type',
                $request->file_type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PRIMARY MEDIA FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->has('is_primary')) {
            $query->where(
                'is_primary',
                filter_var(
                    $request->is_primary,
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SORTING
        |--------------------------------------------------------------------------
        */

        $sortBy = $request->get('sort_by', 'date');

        $sortOrder = $request->get(
            'sort_order',
            'desc'
        );

        $sortColumns = [
            'name' => 'file_name',
            'size' => 'file_size',
            'date' => 'created_at',
        ];

        $query->orderBy(
            $sortColumns[$sortBy],
            $sortOrder
        );

        /*
        |--------------------------------------------------------------------------
        | PRIMARY MEDIA FIRST
        |--------------------------------------------------------------------------
        */

        $query->orderBy(
            'is_primary',
            'desc'
        );

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $perPage = $request->get(
            'per_page',
            10
        );

        $media = $query->paginate(
            $perPage
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'message' => 'Media fetched successfully.',

            'data' => $media->items(),

            'filters' => [
                'search' => $request->search,
                'file_type' => $request->file_type,
                'is_primary' => $request->is_primary,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => (int) $perPage,
            ],

            'pagination' => [
                'current_page' => $media->currentPage(),

                'last_page' => $media->lastPage(),

                'per_page' => $media->perPage(),

                'total' => $media->total(),

                'from' => $media->firstItem(),

                'to' => $media->lastItem(),

                'has_more_pages' =>
                    $media->hasMorePages(),

                'next_page_url' =>
                    $media->nextPageUrl(),

                'previous_page_url' =>
                    $media->previousPageUrl(),
            ],
        ]);
    }


    /**
     * Upload multiple media files.
     *
     * POST /api/products/{productId}/media/upload
     */
    public function uploadMultiple(
        Request $request,
        $productId
    ) {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,svg,mp4,mov,avi,mkv,mp3,wav,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
            ],
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {

            $originalName =
                $file->getClientOriginalName();

            $extension =
                strtolower(
                    $file->getClientOriginalExtension()
                );

            $mimeType =
                $file->getMimeType();

            $fileSize =
                $file->getSize();

            /*
            |--------------------------------------------------------------------------
            | Determine file type
            |--------------------------------------------------------------------------
            */

            if (str_starts_with($mimeType, 'image/')) {
                $fileType = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $fileType = 'video';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $fileType = 'audio';
            } else {
                $fileType = 'document';
            }

            /*
            |--------------------------------------------------------------------------
            | Generate unique filename
            |--------------------------------------------------------------------------
            */

            $fileName =
                Str::uuid() . '.' . $extension;

            /*
            |--------------------------------------------------------------------------
            | Store file
            |--------------------------------------------------------------------------
            */

            $path = $file->storeAs(
                "products/{$productId}",
                $fileName,
                'public'
            );

            /*
            |--------------------------------------------------------------------------
            | Determine primary media
            |--------------------------------------------------------------------------
            */

            $isPrimary =
                !ProductMedia::where(
                    'product_id',
                    $productId
                )->exists();

            /*
            |--------------------------------------------------------------------------
            | Save database record
            |--------------------------------------------------------------------------
            */

            $media = ProductMedia::create([
                'product_id' => $productId,

                'file_name' => $originalName,

                'file_path' =>
                    'app/public/' . $path,

                'file_type' => $fileType,

                'mime_type' => $mimeType,

                'file_size' => $fileSize,

                'thumbnail_path' => null,

                'alt_text' => null,

                'is_primary' => $isPrimary,
            ]);

            $uploadedFiles[] = $media;
        }

        return response()->json([
            'status' => true,

            'message' =>
                'Media uploaded successfully.',

            'data' => $uploadedFiles,
        ], 201);
    }


    /**
     * Upload Base64 media.
     *
     * POST /api/products/{productId}/media/base64
     */
    public function uploadBase64(
        Request $request,
        $productId
    ) {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $request->validate([
            'file' => 'required|string',
            'file_name' => 'required|string|max:255',
        ]);

        $base64File =
            $request->file;

        if (
            !preg_match(
                '/^data:(.*?);base64,(.*)$/',
                $base64File,
                $matches
            )
        ) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Invalid Base64 file format.',
            ], 422);
        }

        $mimeType = $matches[1];

        $fileData =
            base64_decode($matches[2]);

        if ($fileData === false) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to decode Base64 file.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Determine file type
        |--------------------------------------------------------------------------
        */

        if (str_starts_with($mimeType, 'image/')) {
            $fileType = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $fileType = 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            $fileType = 'audio';
        } else {
            $fileType = 'document';
        }

        /*
        |--------------------------------------------------------------------------
        | Extension
        |--------------------------------------------------------------------------
        */

        $extension =
            match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'video/mp4' => 'mp4',
                'audio/mpeg' => 'mp3',
                'application/pdf' => 'pdf',
                default => 'bin',
            };

        $fileName =
            Str::uuid() . '.' . $extension;

        $path =
            "products/{$productId}/{$fileName}";

        Storage::disk('public')->put(
            $path,
            $fileData
        );

        $isPrimary =
            !ProductMedia::where(
                'product_id',
                $productId
            )->exists();

        $media = ProductMedia::create([
            'product_id' => $productId,

            'file_name' =>
                $request->file_name,

            'file_path' =>
                'app/public/' . $path,

            'file_type' =>
                $fileType,

            'mime_type' =>
                $mimeType,

            'file_size' =>
                strlen($fileData),

            'thumbnail_path' =>
                null,

            'alt_text' =>
                null,

            'is_primary' =>
                $isPrimary,
        ]);

        return response()->json([
            'status' => true,

            'message' =>
                'Base64 media uploaded successfully.',

            'data' => $media,
        ], 201);
    }


    /**
     * Show single media.
     *
     * GET /api/products/{productId}/media/{id}
     */
    public function show(
        $productId,
        $id
    ) {
        $media = ProductMedia::where(
            'product_id',
            $productId
        )->find($id);

        if (!$media) {
            return response()->json([
                'status' => false,
                'message' => 'Media not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $media,
        ]);
    }


    /**
     * Download media.
     *
     * GET /api/products/{productId}/media/{id}/download
     */
    public function download(
        $productId,
        $id
    ) {
        /*
        |--------------------------------------------------------------------------
        | Find media
        |--------------------------------------------------------------------------
        */

        $media = ProductMedia::where(
            'product_id',
            $productId
        )->find($id);

        if (!$media) {
            return response()->json([
                'status' => false,
                'message' => 'Media not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Convert database path to storage path
        |--------------------------------------------------------------------------
        */

        $filePath = $media->file_path;

        if (
            str_starts_with(
                $filePath,
                'app/public/'
            )
        ) {
            $filePath = substr(
                $filePath,
                strlen('app/public/')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Storage disk
        |--------------------------------------------------------------------------
        */

        $disk =
            Storage::disk('public');

        /*
        |--------------------------------------------------------------------------
        | Check physical file
        |--------------------------------------------------------------------------
        */

        if (!$disk->exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Media file not found on storage.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Original filename
        |--------------------------------------------------------------------------
        */

        $downloadName =
            $media->file_name;

        if (!$downloadName) {
            $downloadName =
                basename($filePath);
        }

        /*
        |--------------------------------------------------------------------------
        | MIME type
        |--------------------------------------------------------------------------
        */

        $mimeType =
            $media->mime_type;

        if (!$mimeType) {
            $mimeType =
                $disk->mimeType($filePath);
        }

        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        return $disk->download(
            $filePath,
            $downloadName,
            [
                'Content-Type' =>
                    $mimeType,

                'Content-Disposition' =>
                    'attachment; filename="' .
                    addslashes($downloadName) .
                    '"',
            ]
        );
    }


    /**
     * Update media.
     *
     * PUT /api/products/{productId}/media/{id}
     */
    public function update(
        Request $request,
        $productId,
        $id
    ) {
        $media = ProductMedia::where(
            'product_id',
            $productId
        )->find($id);

        if (!$media) {
            return response()->json([
                'status' => false,
                'message' => 'Media not found.',
            ], 404);
        }

        $request->validate([
            'alt_text' =>
                'nullable|string|max:255',

            'is_primary' =>
                'nullable|boolean',
        ]);

        if ($request->has('alt_text')) {
            $media->alt_text =
                $request->alt_text;
        }

        /*
        |--------------------------------------------------------------------------
        | Set Primary
        |--------------------------------------------------------------------------
        */

        if (
            $request->has('is_primary') &&
            $request->is_primary
        ) {
            ProductMedia::where(
                'product_id',
                $productId
            )
                ->where('id', '!=', $media->id)
                ->update([
                    'is_primary' => false,
                ]);

            $media->is_primary = true;
        }

        $media->save();

        return response()->json([
            'status' => true,

            'message' =>
                'Media updated successfully.',

            'data' => $media,
        ]);
    }


    /**
     * Delete media.
     *
     * DELETE /api/products/{productId}/media/{id}
     */
    public function destroy(
        $productId,
        $id
    ) {
        $media = ProductMedia::where(
            'product_id',
            $productId
        )->find($id);

        if (!$media) {
            return response()->json([
                'status' => false,
                'message' => 'Media not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Delete physical file
        |--------------------------------------------------------------------------
        */

        $filePath =
            $media->file_path;

        if (
            str_starts_with(
                $filePath,
                'app/public/'
            )
        ) {
            $filePath = substr(
                $filePath,
                strlen('app/public/')
            );
        }

        if (
            Storage::disk('public')
                ->exists($filePath)
        ) {
            Storage::disk('public')
                ->delete($filePath);
        }

        /*
        |--------------------------------------------------------------------------
        | Delete database record
        |--------------------------------------------------------------------------
        */

        $wasPrimary =
            $media->is_primary;

        $media->delete();

        /*
        |--------------------------------------------------------------------------
        | Assign another media as primary
        |--------------------------------------------------------------------------
        */

        if ($wasPrimary) {
            $nextMedia =
                ProductMedia::where(
                    'product_id',
                    $productId
                )
                ->latest()
                ->first();

            if ($nextMedia) {
                $nextMedia->update([
                    'is_primary' => true,
                ]);
            }
        }

        return response()->json([
            'status' => true,

            'message' =>
                'Media deleted successfully.',
        ]);
    }


    /**
     * Set media as primary.
     *
     * POST /api/products/{productId}/media/{id}/primary
     */
    public function setPrimary(
        $productId,
        $id
    ) {
        $media = ProductMedia::where(
            'product_id',
            $productId
        )->find($id);

        if (!$media) {
            return response()->json([
                'status' => false,
                'message' => 'Media not found.',
            ], 404);
        }

        ProductMedia::where(
            'product_id',
            $productId
        )->update([
            'is_primary' => false,
        ]);

        $media->update([
            'is_primary' => true,
        ]);

        return response()->json([
            'status' => true,

            'message' =>
                'Primary media updated successfully.',

            'data' => $media,
        ]);
    }
}