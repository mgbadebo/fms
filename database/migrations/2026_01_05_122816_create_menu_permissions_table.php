<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('menu_key'); // e.g., 'bell-pepper', 'gari', 'general'
            $table->string('submenu_key')->nullable(); // e.g., 'harvests', 'sales', null for menu-level
            $table->string('permission_type'); // 'view', 'create', 'update'
            $table->string('name'); // Display name
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['menu_key', 'submenu_key', 'permission_type'], 'menu_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_permissions');
    }
};
