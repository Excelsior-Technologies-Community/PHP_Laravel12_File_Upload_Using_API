@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Edit Product</h2>
            </div>
        </div>

        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" id="productForm" class="mt-8 space-y-8">
            @csrf
            @method('PUT')

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Product Information</h3>
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                            <div class="mt-1">
                                <input type="text" name="product_name" id="product_name" value="{{ old('product_name', $product->product_name) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('product_name') border-red-300 @enderror">
                                @error('product_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
                            <div class="mt-1">
                                <textarea name="details" id="details" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('details') border-red-300 @enderror">{{ old('details', $product->details) }}</textarea>
                                @error('details') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="size" class="block text-sm font-medium text-gray-700">Size</label>
                            <div class="mt-1">
                                <input type="text" name="size" id="size" value="{{ old('size', $product->size) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('size') border-red-300 @enderror">
                                @error('size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="color" class="block text-sm font-medium text-gray-700">Color</label>
                            <div class="mt-1">
                                <input type="text" name="color" id="color" value="{{ old('color', $product->color) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('color') border-red-300 @enderror">
                                @error('color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <div class="mt-1">
                                <input type="text" name="category" id="category" value="{{ old('category', $product->category) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('category') border-red-300 @enderror">
                                @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Media Gallery</h3>
                    <p class="mt-1 text-sm text-gray-500">Manage existing media or upload new files.</p>

                    <div class="mt-6">
                        <div id="existingMedia" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach($product->media_urls ?? [] as $media)
                            <div class="relative group" data-media-id="{{ $media['id'] }}">
                                <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                    @if($media['file_type'] === 'image')
                                        <img src="{{ $media['thumbnail_url'] ?? $media['url'] }}" alt="{{ $media['alt_text'] ?? '' }}" class="w-full h-full object-cover">
                                    @elseif($media['file_type'] === 'video')
                                        <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                            <svg class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    @elseif($media['file_type'] === 'audio')
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                            <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                            <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <p class="text-xs text-gray-500 truncate">{{ $media['file_name'] }}</p>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="remove_media[]" value="{{ $media['id'] }}" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-500 focus:ring-red-500">
                                        <span class="ml-1 text-xs text-red-600">Remove</span>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700">Add New Media</label>
                        <div id="editDropZone" class="mt-2 flex justify-center rounded-lg border-2 border-dashed border-gray-300 hover:border-indigo-500 transition-colors px-6 py-10">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v12m0 0v4m0-4H36m-4 4h12a4 4 0 004-4V12a4 4 0 00-4-4H28a4 4 0 00-4 4v4m0 0h4m-4 0H20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <div class="mt-4 flex text-sm leading-6 text-gray-600">
                                    <label for="files" class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                        <span>Upload files</span>
                                        <input id="files" name="files[]" type="file" multiple class="sr-only" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.zip,.txt">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs leading-5 text-gray-600">PNG, JPG, GIF, WEBP, MP4, PDF up to 10MB each</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Base64 Upload</h3>
                    <div class="mt-4">
                        <label for="base64_files" class="block text-sm font-medium text-gray-700">Base64 Encoded Files (JSON array)</label>
                        <textarea name="base64_files" id="base64_files" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder='[{"base64": "data:image/jpeg;base64,...", "file_name": "image.jpg", "mime_type": "image/jpeg"}]'></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('products.index') }}" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Cancel
                </a>
                <button type="submit" class="rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Update Product
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('js/product-upload.js') }}"></script>
@endsection
