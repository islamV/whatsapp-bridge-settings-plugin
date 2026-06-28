<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_bridge_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name', 255)->default('default');
            $table->string('api_base_url', 255)->nullable();
            $table->text('api_token')->nullable()->comment('Encrypted API token');
            $table->string('sender', 255)->nullable();
            $table->string('default_country_code', 10)->default('20');
            $table->boolean('otp_enabled')->default(true);
            $table->boolean('messages_enabled')->default(true);
            $table->text('otp_template')->nullable();
            $table->integer('timeout')->default(30);
            $table->json('extra_settings')->nullable();
            $table->enum('active_provider', ['bridge', 'meta', 'twilio'])->default('bridge');
            $table->json('providers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_bridge_settings');
    }
};
