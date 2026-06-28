<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_bridge_settings', function (Blueprint $table) {
            $table->string('active_provider', 50)->default('bridge')->after('provider_name');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_bridge_settings', function (Blueprint $table) {
            $table->dropColumn('active_provider');
        });
    }
};
