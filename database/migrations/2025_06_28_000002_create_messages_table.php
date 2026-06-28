<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // Nullable: at send time we may not yet know which device will handle it.
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('to');
            $table->text('content');
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])
                ->default('pending')
                ->index();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
