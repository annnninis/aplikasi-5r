<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nomor urut kode barang SPO-0001, SPO-0002, ... dari sequence PostgreSQL (aman dari kode ganda).
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS wispo_item_code_seq START 1');
    }

    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS wispo_item_code_seq');
    }
};
