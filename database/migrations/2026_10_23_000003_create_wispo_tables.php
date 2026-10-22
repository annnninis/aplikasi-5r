<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wispo_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('item_code')->unique(); // SPO-0001, dibuat otomatis
            $table->string('name');
            $table->string('category');
            $table->string('unit');
            $table->string('photo_path')->nullable();
            // Hanya diubah lewat StockService
            $table->unsignedInteger('current_stock_qty')->default(0);
            $table->unsignedInteger('safety_stock_qty')->default(0);
            $table->string('storage_location')->nullable();
            $table->string('brand_model')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('added_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('category');
            $table->index('name');
        });

        Schema::create('wispo_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_id')->constrained('wispo_items')->restrictOnDelete();
            $table->enum('request_type', ['permintaan', 'peminjaman']);
            $table->enum('usage_category', ['rutin', 'shutdown_water_cleaning', 'turn_around', 'crash_program']);
            $table->unsignedInteger('qty');
            $table->enum('requester_type', ['internal'])->default('internal');
            $table->foreignUuid('requester_user_id')->constrained('users')->restrictOnDelete();
            $table->text('purpose');
            $table->date('expected_return_at')->nullable();
            $table->timestamp('actual_return_at')->nullable();
            $table->enum('status', [
                'menunggu_persetujuan',
                'disetujui',
                'dipinjam',
                'dikembalikan',
                'terlambat',
                'ditolak',
            ])->default('menunggu_persetujuan');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('return_notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('wispo_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_id')->constrained('wispo_items')->restrictOnDelete();
            $table->enum('transaction_type', ['masuk', 'keluar', 'reject', 'pinjam', 'kembali']);
            $table->unsignedInteger('qty');
            $table->unsignedInteger('stock_after');
            $table->date('transaction_date');
            $table->string('source_or_destination')->nullable();
            $table->foreignUuid('request_id')->nullable()->constrained('wispo_requests')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['item_id', 'created_at']);
        });

        DB::statement('ALTER TABLE wispo_requests ADD CONSTRAINT wispo_requests_qty_positive CHECK (qty > 0)');
        DB::statement('ALTER TABLE wispo_transactions ADD CONSTRAINT wispo_transactions_qty_positive CHECK (qty > 0)');
        DB::statement("ALTER TABLE wispo_requests ADD CONSTRAINT wispo_requests_loan_needs_return_date CHECK (request_type <> 'peminjaman' OR expected_return_at IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('wispo_transactions');
        Schema::dropIfExists('wispo_requests');
        Schema::dropIfExists('wispo_items');
    }
};
