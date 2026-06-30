<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
        });

        // Backfill any existing device rows that pre-date this column.
        DB::table('devices')->whereNull('public_id')->orderBy('id')->each(function ($device) {
            DB::table('devices')->where('id', $device->id)->update(['public_id' => (string) Str::uuid()]);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->uuid('public_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
