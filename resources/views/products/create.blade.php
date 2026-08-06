@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Add New Product</h2>
            </div>
        </div>

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm" class="mt-8 space-y-8">
            @csrf

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Product Information</h3>
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                            <div class="mt-1">
                                <input type="text" name="product_name" id="product_name" value="{{ old('product_name') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('product_name') border-red-300 @enderror">
                                @error('product_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
                            <div class="mt-1">
                                <textarea name="details" id="details" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('details') border-red-300 @enderror">{{ old('details') }}</textarea>
                                @error('details') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="size" class="block text-sm font-medium text-gray-700">Size</label>
                            <div class="mt-1">
                                <input type="text" name="size" id="size" value="{{ old('size') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('size') border-red-300 @enderror">
                                @error('size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="color" class="block text-sm font-medium text-gray-700">Color</label>
                            <div class="mt-1">
                                <input type="text" name="color" id="color" value="{{ old('color') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('color') border-red-300 @enderror">
                                @error('color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <div class="mt-1">
                                <input type="text" name="category" id="category" value="{{ old('category') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('category') border-red-300 @enderror">
                                @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Media Upload</h3>
                    <p class="mt-1 text-sm text-gray-500">Upload images, videos, audio, or documents. Supports drag & drop.</p>

                    <div class="mt-6">
                        <div id="dropZone" class="flex justify-center rounded-lg border-2 border-dashed border-gray-300 hover:border-indigo-500 transition-colors px-6 py-10">
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

                    <div id="filePreview" class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 hidden">
                    </div>

                    <div id="uploadProgress" class="mt-4 hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700">Uploading...</span>
                                    <span id="progressText" class="text-sm font-medium text-gray-700">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="progressBar" class="bg-indigo-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Base64 Upload (Mobile Apps)</h3>
                    <p class="mt-1 text-sm text-gray-500">Upload files via base64 encoded string for mobile applications.</p>
                    <div class="mt-4">
                        <label for="base64_files" class="block text-sm font-medium text-gray-700">Base64 Encoded Files (JSON array)</label>
                        <textarea name="base64_files" id="base64_files" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder='[{"base64": "data:image/jpeg;base64,...", "file_name": "image.jpg", "mime_type": "image/jpeg"}]'></textarea>
                        <p class="mt-1 text-xs text-gray-500">Format: JSON array of objects with base64, file_name, and mime_type</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('products.index') }}" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Cancel
                </a>
                <button type="submit" class="rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Save Product
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('js/product-upload.js') }}"></script>
@endsection
