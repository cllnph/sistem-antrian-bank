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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Contoh: 001, 002');
            $table->string('slug', 50)->unique()->comment('Contoh: pusat, bogor, depok');
            $table->string('name', 150)->comment('Nama Kantor Cabang / Kantor Kas');
            $table->string('type', 50)->default('Kantor Cabang')->comment('Kantor Pusat, Kantor Cabang, Kantor Kas');
            $table->text('address')->nullable()->comment('Alamat lengkap untuk struk thermal dan running text TV');
            $table->string('phone', 50)->nullable()->comment('No Telepon kantor');
            $table->string('operating_hours', 100)->default('Senin – Jumat (08.00 – 16.00 WIB)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
