<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('area_id')->constrained('areas')->restrictOnDelete();
            $table->enum('booking_mode', ['offline'])->default('offline');
            $table->enum('requester_type', ['internal'])->default('internal');
            $table->foreignUuid('requester_user_id')->constrained('users')->restrictOnDelete();
            $table->text('purpose');
            $table->unsignedInteger('participant_count');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->enum('approval_status', [
                'menunggu_persetujuan',
                'disetujui',
                'ditolak',
                'dibatalkan',
                'kedaluwarsa',
            ])->default('menunggu_persetujuan');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->string('zoom_link')->nullable();
            $table->timestamps();

            $table->index(['area_id', 'start_time']);
        });

        DB::statement('ALTER TABLE room_bookings ADD CONSTRAINT room_bookings_time_order CHECK (end_time > start_time)');

        // Pengaman terakhir anti-bentrok: dua booking aktif di ruang yang sama tidak boleh tumpang tindih.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement("
            ALTER TABLE room_bookings ADD CONSTRAINT room_bookings_no_overlap
            EXCLUDE USING gist (area_id WITH =, tsrange(start_time, end_time) WITH &&)
            WHERE (approval_status IN ('menunggu_persetujuan', 'disetujui'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('room_bookings');
    }
};
