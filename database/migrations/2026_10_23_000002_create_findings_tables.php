<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('source', ['quick_report'])->default('quick_report');
            $table->foreignUuid('area_id')->constrained('areas')->restrictOnDelete();
            $table->foreignUuid('reported_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->enum('priority', ['tinggi', 'sedang', 'rendah'])->default('sedang');
            $table->date('deadline')->nullable();
            $table->enum('status', [
                'belum_ditugaskan',
                'belum_selesai',
                'menunggu_verifikasi',
                'selesai',
                'terlambat',
                'ditolak',
            ])->default('belum_ditugaskan');
            $table->text('resolution_note')->nullable();
            $table->text('revision_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'deadline']);
            $table->index(['pic_id', 'status']);
        });

        Schema::create('finding_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('finding_id')->constrained('findings')->cascadeOnDelete();
            $table->enum('type', ['before', 'after']);
            $table->string('photo_path');
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_photos');
        Schema::dropIfExists('findings');
    }
};
