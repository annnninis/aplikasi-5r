<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->enum('zone_category', ['operasional', 'kolaborasi', 'publik']);
            $table->string('focus_5r')->nullable();
            $table->foreignUuid('default_pic_id')->nullable()->constrained('users')->nullOnDelete();
            // Khusus area kolaborasi (booking ruangan)
            $table->unsignedInteger('capacity')->nullable();
            $table->string('facilities')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
