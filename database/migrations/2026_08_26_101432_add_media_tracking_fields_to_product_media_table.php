<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_media', function (Blueprint $table) {

            $table->string('checksum', 64)
                ->nullable()
                ->after('file_size');

            $table->unsignedInteger('download_count')
                ->default(0)
                ->after('checksum');

            $table->timestamp('last_downloaded_at')
                ->nullable()
                ->after('download_count');
        });
    }

    public function down(): void
    {
        Schema::table('product_media', function (Blueprint $table) {

            $table->dropColumn([
                'checksum',
                'download_count',
                'last_downloaded_at',
            ]);
        });
    }
};
