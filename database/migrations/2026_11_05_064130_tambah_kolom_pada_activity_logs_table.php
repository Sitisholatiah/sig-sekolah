<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_logs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'aksi')) {
                $table->string('aksi', 20)->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'objek')) {
                $table->string('objek', 30)->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'sekolah_id')) {
                $table->unsignedBigInteger('sekolah_id')->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'sekolah_nama')) {
                $table->string('sekolah_nama')->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'perubahan')) {
                $table->json('perubahan')->nullable();
            }
        });
    }

    public function down(): void
    {
        // sengaja dikosongkan
    }
};