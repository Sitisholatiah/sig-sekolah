<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pengujian otomatis memakai SQLite, tidak mendukung PostGIS
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA extensions');

        // Kolom dihitung otomatis dari longitude dan latitude, jadi selalu sinkron
        DB::statement('
            ALTER TABLE sekolahs
            ADD COLUMN IF NOT EXISTS lokasi extensions.geography(Point, 4326)
            GENERATED ALWAYS AS (
                extensions.ST_SetSRID(extensions.ST_MakePoint(longitude, latitude), 4326)::extensions.geography
            ) STORED
        ');

        DB::statement('CREATE INDEX IF NOT EXISTS sekolahs_lokasi_gist ON sekolahs USING GIST (lokasi)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS sekolahs_lokasi_gist');
        DB::statement('ALTER TABLE sekolahs DROP COLUMN IF EXISTS lokasi');
    }
};