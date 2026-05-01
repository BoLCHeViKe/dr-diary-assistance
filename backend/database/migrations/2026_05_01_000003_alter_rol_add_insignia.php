<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->string('insignia', 10)->nullable()->after('tipo');
        });

        DB::table('rol')->where('id', 2)->update(['insignia' => 'MÉDICO']);
    }

    public function down(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->dropColumn('insignia');
        });
    }
};
