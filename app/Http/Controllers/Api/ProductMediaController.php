<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductMediaResource;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMediaController extends Controller
{
    /**
     * Display media for a product.
     *
     * Supports:
     * search
     * file_type
     * is_primary
     * sorting
     * pagination
     * date filtering
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

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

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
                'in:name,size,date,downloads',
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

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],
        ]);

        $query = ProductMedia::where(
            'product_id',
            $productId
        );

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'file_name',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'mime_type',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'file_type',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILE TYPE
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
        | PRIMARY FILTER
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
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from_date')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->to_date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SORT
        |--------------------------------------------------------------------------
        */

        $sortBy = $request->get(
            'sort_by',
            'date'
        );

        $sortOrder = $request->get(
            'sort_order',
            'asc'
        );

        $sortColumns = [

            'name' => 'file_name',

            'size' => 'file_size',

            'date' => 'created_at',

            'downloads' => 'download_count',
        ];

        $query->orderBy(
            $sortColumns[$sortBy],
            $sortOrder
        );

        /*
        |--------------------------------------------------------------------------
        | PRIMARY FIRST
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

        return response()->json([

            'status' => true,

            'message' => 'Media fetched successfully.',

            'data' => ProductMediaResource::collection(
                $media->items()
            ),

            'filters' => [

                'search' =>
                $request->search,

                'file_type' =>
                $request->file_type,

                'is_primary' =>
                $request->is_primary,

                'sort_by' =>
                $sortBy,

                'sort_order' =>
                $sortOrder,

                'from_date' =>
                $request->from_date,

                'to_date' =>
                $request->to_date,

                'per_page' =>
                (int) $perPage,
            ],

            'pagination' => [

                'current_page' =>
                $media->currentPage(),

                'last_page' =>
                $media->lastPage(),

                'per_page' =>
                $media->perPage(),

                'total' =>
                $media->total(),

                'from' =>
                $media->firstItem(),

                'to' =>
                $media->lastItem(),

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
     *
     * Includes duplicate detection.
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

            'files' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'files.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,svg,mp4,mov,avi,mkv,mp3,wav,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
            ],
        ]);

        $uploadedFiles = [];

        $duplicateFiles = [];

        foreach ($request->file('files') as $file) {

            $fileData = file_get_contents(
                $file->getRealPath()
            );

            $checksum = hash(
                'sha256',
                $fileData
            );

            /*
            |--------------------------------------------------------------------------
            | DUPLICATE CHECK
            |--------------------------------------------------------------------------
            */

            $duplicate = ProductMedia::where(
                'product_id',
                $productId
            )
                ->where(
                    'checksum',
                    $checksum
                )
                ->first();

            if ($duplicate) {

                $duplicateFiles[] = [

                    'file_name' =>
                    $file->getClientOriginalName(),

                    'existing_media_id' =>
                    $duplicate->id,

                    'message' =>
                    'Duplicate file already exists.',
                ];

                continue;
            }

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
            | FILE TYPE
            |--------------------------------------------------------------------------
            */

            if (
                str_starts_with(
                    $mimeType,
                    'image/'
                )
            ) {

                $fileType = 'image';
            } elseif (
                str_starts_with(
                    $mimeType,
                    'video/'
                )
            ) {

                $fileType = 'video';
            } elseif (
                str_starts_with(
                    $mimeType,
                    'audio/'
                )
            ) {

                $fileType = 'audio';
            } else {

                $fileType = 'document';
            }

            /*
            |--------------------------------------------------------------------------
            | UNIQUE FILE NAME
            |--------------------------------------------------------------------------
            */

            $fileName =
                Str::uuid() .
                '.' .
                $extension;

            /*
            |--------------------------------------------------------------------------
            | STORE
            |--------------------------------------------------------------------------
            */

            $path = $file->storeAs(
                "products/{$productId}",
                $fileName,
                'public'
            );

            /*
            |--------------------------------------------------------------------------
            | PRIMARY
            |--------------------------------------------------------------------------
            */

            $isPrimary =
                !ProductMedia::where(
                    'product_id',
                    $productId
                )->exists();

            /*
            |--------------------------------------------------------------------------
            | DATABASE
            |--------------------------------------------------------------------------
            */

            $media = ProductMedia::create([

                'product_id' =>
                $productId,

                'file_name' =>
                $originalName,

                'file_path' =>
                'app/public/' . $path,

                'file_type' =>
                $fileType,

                'mime_type' =>
                $mimeType,

                'file_size' =>
                $fileSize,

                'thumbnail_path' =>
                null,

                'alt_text' =>
                null,

                'is_primary' =>
                $isPrimary,

                'checksum' =>
                $checksum,

                'download_count' =>
                0,

                'last_downloaded_at' =>
                null,
            ]);

            $uploadedFiles[] =
                $media;
        }

        return response()->json([

            'status' => true,

            'message' =>
            'Media upload completed.',

            'uploaded_count' =>
            count($uploadedFiles),

            'duplicate_count' =>
            count($duplicateFiles),

            'data' =>
            ProductMediaResource::collection(
                $uploadedFiles
            ),

            'duplicates' =>
            $duplicateFiles,
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

            'file' =>
            'required|string',

            'file_name' =>
            'required|string|max:255',
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

        $mimeType =
            $matches[1];

        $fileData =
            base64_decode(
                $matches[2],
                true
            );

        if ($fileData === false) {

            return response()->json([
                'status' => false,
                'message' =>
                'Unable to decode Base64 file.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECKSUM
        |--------------------------------------------------------------------------
        */

        $checksum =
            hash(
                'sha256',
                $fileData
            );

        $duplicate =
            ProductMedia::where(
                'product_id',
                $productId
            )
            ->where(
                'checksum',
                $checksum
            )
            ->first();

        if ($duplicate) {

            return response()->json([
                'status' => false,
                'message' =>
                'Duplicate file already exists.',
                'existing_media_id' =>
                $duplicate->id,
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | FILE TYPE
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with(
                $mimeType,
                'image/'
            )
        ) {

            $fileType = 'image';
        } elseif (
            str_starts_with(
                $mimeType,
                'video/'
            )
        ) {

            $fileType = 'video';
        } elseif (
            str_starts_with(
                $mimeType,
                'audio/'
            )
        ) {

            $fileType = 'audio';
        } else {

            $fileType = 'document';
        }

        /*
        |--------------------------------------------------------------------------
        | EXTENSION
        |--------------------------------------------------------------------------
        */

        $extension = match ($mimeType) {

            'image/jpeg' => 'jpg',

            'image/png' => 'png',

            'image/gif' => 'gif',

            'image/webp' => 'webp',

            'image/svg+xml' => 'svg',

            'video/mp4' => 'mp4',

            'video/webm' => 'webm',

            'audio/mpeg' => 'mp3',

            'audio/wav' => 'wav',

            'application/pdf' => 'pdf',

            default => 'bin',
        };

        $fileName =
            Str::uuid() .
            '.' .
            $extension;

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

            'product_id' =>
            $productId,

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

            'checksum' =>
            $checksum,

            'download_count' =>
            0,

            'last_downloaded_at' =>
            null,
        ]);

        return response()->json([

            'status' => true,

            'message' =>
            'Base64 media uploaded successfully.',

            'data' =>
            new ProductMediaResource(
                $media
            ),
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
        $media =
            ProductMedia::where(
                'product_id',
                $productId
            )->find($id);

        if (!$media) {

            return response()->json([
                'status' => false,
                'message' =>
                'Media not found.',
            ], 404);
        }

        return response()->json([

            'status' => true,

            'data' =>
            new ProductMediaResource(
                $media
            ),
        ]);
    }


    /**
     * Download media.
     *
     * Download counter is increased.
     *
     * GET /api/products/{productId}/media/{id}/download
     */
    public function download(
        $productId,
        $id
    ) {
        $media =
            ProductMedia::where(
                'product_id',
                $productId
            )->find($id);

        if (!$media) {

            return response()->json([
                'status' => false,
                'message' =>
                'Media not found.',
            ], 404);
        }

        $filePath =
            $this->cleanStoragePath(
                $media->file_path
            );

        $disk =
            Storage::disk('public');

        if (!$disk->exists($filePath)) {

            return response()->json([
                'status' => false,
                'message' =>
                'Media file not found on storage.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD TRACKING
        |--------------------------------------------------------------------------
        */

        $media->increment(
            'download_count'
        );

        $media->update([
            'last_downloaded_at' =>
            now(),
        ]);

        $downloadName =
            $media->file_name
            ?: basename($filePath);

        $mimeType =
            $media->mime_type
            ?: $disk->mimeType($filePath);

        return $disk->download(
            $filePath,
            $downloadName,
            [
                'Content-Type' =>
                $mimeType,
            ]
        );
    }


    /**
     * Update media metadata.
     *
     * PUT /api/products/{productId}/media/{id}
     */
    public function update(
        Request $request,
        $productId,
        $id
    ) {
        $media =
            ProductMedia::where(
                'product_id',
                $productId
            )->find($id);

        if (!$media) {

            return response()->json([
                'status' => false,
                'message' =>
                'Media not found.',
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

        if (
            $request->has('is_primary') &&
            $request->boolean('is_primary')
        ) {

            ProductMedia::where(
                'product_id',
                $productId
            )
                ->where(
                    'id',
                    '!=',
                    $media->id
                )
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

            'data' =>
            new ProductMediaResource(
                $media
            ),
        ]);
    }


    /**
     * Replace physical media file.
     *
     * PUT /api/products/{productId}/media/{id}/replace
     */
    public function replace(
        Request $request,
        $productId,
        $id
    ) {
        $media =
            ProductMedia::where(
                'product_id',
                $productId
            )->find($id);

        if (!$media) {

            return response()->json([
                'status' => false,
                'message' =>
                'Media not found.',
            ], 404);
        }

        $request->validate([

            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,svg,mp4,mov,avi,mkv,mp3,wav,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
            ],

            'alt_text' =>
            'nullable|string|max:255',
        ]);

        $file =
            $request->file('file');

        $fileData =
            file_get_contents(
                $file->getRealPath()
            );

        $checksum =
            hash(
                'sha256',
                $fileData
            );

        /*
        |--------------------------------------------------------------------------
        | DUPLICATE CHECK
        |--------------------------------------------------------------------------
        */

        $duplicate =
            ProductMedia::where(
                'product_id',
                $productId
            )
            ->where(
                'checksum',
                $checksum
            )
            ->where(
                'id',
                '!=',
                $media->id
            )
            ->first();

        if ($duplicate) {

            return response()->json([
                'status' => false,
                'message' =>
                'This file already exists.',
                'existing_media_id' =>
                $duplicate->id,
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE OLD FILE
        |--------------------------------------------------------------------------
        */

        $oldPath =
            $this->cleanStoragePath(
                $media->file_path
            );

        $oldThumbnail =
            $this->cleanStoragePath(
                $media->thumbnail_path
            );

        if (
            $oldPath &&
            Storage::disk('public')->exists($oldPath)
        ) {
            Storage::disk('public')->delete(
                $oldPath
            );
        }

        if (
            $oldThumbnail &&
            Storage::disk('public')->exists(
                $oldThumbnail
            )
        ) {
            Storage::disk('public')->delete(
                $oldThumbnail
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILE TYPE
        |--------------------------------------------------------------------------
        */

        $mimeType =
            $file->getMimeType();

        if (
            str_starts_with(
                $mimeType,
                'image/'
            )
        ) {

            $fileType = 'image';
        } elseif (
            str_starts_with(
                $mimeType,
                'video/'
            )
        ) {

            $fileType = 'video';
        } elseif (
            str_starts_with(
                $mimeType,
                'audio/'
            )
        ) {

            $fileType = 'audio';
        } else {

            $fileType = 'document';
        }

        $extension =
            strtolower(
                $file->getClientOriginalExtension()
            );

        $fileName =
            Str::uuid() .
            '.' .
            $extension;

        $path =
            $file->storeAs(
                "products/{$productId}",
                $fileName,
                'public'
            );

        /*
        |--------------------------------------------------------------------------
        | UPDATE SAME RECORD
        |--------------------------------------------------------------------------
        */

        $media->update([

            'file_name' =>
            $file->getClientOriginalName(),

            'file_path' =>
            'app/public/' . $path,

            'file_type' =>
            $fileType,

            'mime_type' =>
            $mimeType,

            'file_size' =>
            $file->getSize(),

            'thumbnail_path' =>
            null,

            'checksum' =>
            $checksum,

            'download_count' =>
            0,

            'last_downloaded_at' =>
            null,

            'alt_text' =>
            $request->alt_text
                ?? $media->alt_text,
        ]);

        return response()->json([

            'status' => true,

            'message' =>
            'Media file replaced successfully.',

            'data' =>
            new ProductMediaResource(
                $media->fresh()
            ),
        ]);
    }


    /**
     * Delete single media.
     *
     * DELETE /api/products/{productId}/media/{id}
     */
    public function destroy(
        $productId,
        $id
    ) {
        $media =
            ProductMedia::where(
                'product_id',
                $productId
            )->find($id);

        if (!$media) {

            return response()->json([
                'status' => false,
                'message' =>
                'Media not found.',
            ], 404);
        }

        $this->deletePhysicalMedia(
            $media
        );

        $wasPrimary =
            $media->is_primary;

        $media->delete();

        /*
        |--------------------------------------------------------------------------
        | ASSIGN NEW PRIMARY
        |--------------------------------------------------------------------------
        */

        if ($wasPrimary) {

            $nextMedia =
                ProductMedia::where(
                    'product_id',
                    $productId
                )
                ->oldest()
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
     * Bulk delete media.
     *
     * DELETE /api/products/{productId}/media/bulk-delete
     */
    public function bulkDelete(
        Request $request,
        $productId
    ) {
        $product =
            Product::find($productId);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' =>
                'Product not found.',
            ], 404);
        }

        $request->validate([

            'media_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'media_ids.*' => [
                'integer',
                'exists:product_media,id',
            ],
        ]);

        $mediaList =
            ProductMedia::where(
                'product_id',
                $productId
            )
            ->whereIn(
                'id',
                $request->media_ids
            )
            ->get();

        if ($mediaList->isEmpty()) {

            return response()->json([
                'status' => false,
                'message' =>
                'No matching media found.',
            ], 404);
        }

        $deletedCount = 0;

        foreach ($mediaList as $media) {

            $this->deletePhysicalMedia(
                $media
            );

            $media->delete();

            $deletedCount++;
        }

        /*
        |--------------------------------------------------------------------------
        | ENSURE PRIMARY MEDIA
        |--------------------------------------------------------------------------
        */

        $hasPrimary =
            ProductMedia::where(
                'product_id',
                $productId
            )
            ->where(
                'is_primary',
                true
            )
            ->exists();

        if (!$hasPrimary) {

            $nextMedia =
                ProductMedia::where(
                    'product_id',
                    $productId
                )
                ->oldest()
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
            'Media files deleted successfully.',

            'deleted_count' =>
            $deletedCount,
        ]);
    }


    /**
     * Media statistics.
     *
     * GET /api/products/{productId}/media-stats
     */
    public function statistics(
        $productId
    ) {
        $product =
            Product::find($productId);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' =>
                'Product not found.',
            ], 404);
        }

        $query =
            ProductMedia::where(
                'product_id',
                $productId
            );

        $totalFiles =
            (clone $query)->count();

        $images =
            (clone $query)
            ->where(
                'file_type',
                'image'
            )
            ->count();

        $videos =
            (clone $query)
            ->where(
                'file_type',
                'video'
            )
            ->count();

        $audio =
            (clone $query)
            ->where(
                'file_type',
                'audio'
            )
            ->count();

        $documents =
            (clone $query)
            ->where(
                'file_type',
                'document'
            )
            ->count();

        $totalSize =
            (clone $query)
            ->sum('file_size');

        $totalDownloads =
            (clone $query)
            ->sum('download_count');

        $primaryMedia =
            (clone $query)
            ->where(
                'is_primary',
                true
            )
            ->first();

        return response()->json([

            'status' => true,

            'message' =>
            'Media statistics fetched successfully.',

            'data' => [

                'total_files' =>
                $totalFiles,

                'images' =>
                $images,

                'videos' =>
                $videos,

                'audio' =>
                $audio,

                'documents' =>
                $documents,

                'total_size_bytes' =>
                (int) $totalSize,

                'total_size_mb' =>
                round(
                    $totalSize / 1024 / 1024,
                    2
                ),

                'total_downloads' =>
                (int) $totalDownloads,

                'primary_media_id' =>
                $primaryMedia?->id,
            ],
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
        $media =
            ProductMedia::where(
                'product_id',
                $productId
            )->find($id);

        if (!$media) {

            return response()->json([
                'status' => false,
                'message' =>
                'Media not found.',
            ], 404);
        }

        DB::transaction(function () use (
            $productId,
            $media
        ) {

            ProductMedia::where(
                'product_id',
                $productId
            )->update([
                'is_primary' => false,
            ]);

            $media->update([
                'is_primary' => true,
            ]);
        });

        return response()->json([

            'status' => true,

            'message' =>
            'Primary media updated successfully.',

            'data' =>
            new ProductMediaResource(
                $media->fresh()
            ),
        ]);
    }


    /**
     * Clean database storage path.
     */
    private function cleanStoragePath(
        ?string $path
    ): ?string {

        if (!$path) {
            return null;
        }

        return preg_replace(
            '#^app/public/#',
            '',
            $path
        );
    }


    /**
     * Delete physical media files.
     */
    private function deletePhysicalMedia(
        ProductMedia $media
    ): void {

        $disk =
            Storage::disk('public');

        $filePath =
            $this->cleanStoragePath(
                $media->file_path
            );

        $thumbnailPath =
            $this->cleanStoragePath(
                $media->thumbnail_path
            );

        if (
            $filePath &&
            $disk->exists($filePath)
        ) {
            $disk->delete(
                $filePath
            );
        }

        if (
            $thumbnailPath &&
            $disk->exists($thumbnailPath)
        ) {
            $disk->delete(
                $thumbnailPath
            );
        }
    }
}
