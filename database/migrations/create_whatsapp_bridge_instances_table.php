<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_bridge_instances', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('api_base_url');
            $table->text('api_token')->nullable()->comment('Encrypted API token');
            $table->string('session_name')->default('default');
            $table->string('sender')->nullable();
            $table->unsignedInteger('timeout')->default(30);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_bridge_instances');
    }
};
