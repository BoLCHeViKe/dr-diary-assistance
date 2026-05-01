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
            $table->boolean('perm_agenda_disponible')->default(false)->after('perm_hc');
        });

        DB::table('rol')->where('id', 1)->update(['perm_agenda_disponible' => true]);
        DB::table('rol')->where('id', 2)->update(['perm_agenda_disponible' => true]);
    }

    public function down(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->dropColumn('perm_agenda_disponible');
        });
    }
};
