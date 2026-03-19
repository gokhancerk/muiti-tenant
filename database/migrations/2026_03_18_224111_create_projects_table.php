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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // 1. Kısıtlama: Foreign Key ve Cascade Delete
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            // 2. Darboğaz Çözümü: Bileşik İndeks (Composite Index)
            // Sadece tenant_id'ye değil, sorgu paternine göre indeks atılır.
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
