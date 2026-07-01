<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique()->index();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('sender')->index();
            $table->text('body');
            $table->timestamp('received_at')->index();
            $table->foreignId('outbound_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_messages');
    }
};
