<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('display_name')->nullable();
        });
        foreach (\Illuminate\Support\Facades\DB::table('roles')->get(['id', 'name']) as $role) {
            $label = match ($role->name) {
                'cs' => 'Customer Service', 'teller' => 'Teller',
                'admin' => 'Administrator', 'admin_cabang' => 'Admin Cabang',
                default => ucfirst(str_replace('_', ' ', $role->name)),
            };
            \Illuminate\Support\Facades\DB::table('roles')->where('id', $role->id)->update(['display_name' => $label]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', fn (Blueprint $table) => $table->dropColumn('display_name'));
    }
};
