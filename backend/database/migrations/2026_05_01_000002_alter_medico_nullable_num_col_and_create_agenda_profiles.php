<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make num_col nullable so non-doctor agenda users don't need a colegiado number
        Schema::table('medico', function (Blueprint $table) {
            $table->string('num_col', 10)->nullable()->change();
        });

        // Auto-create medico records for users whose role has perm_agenda_disponible
        // but who don't have a medico record yet (e.g. ENFERMERO users)
        $userIds = DB::table('users as u')
            ->join('rol as r', 'u.id_rol', '=', 'r.id')
            ->where('r.perm_agenda_disponible', true)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('medico')->whereColumn('medico.id', 'u.id');
            })
            ->pluck('u.id');

        foreach ($userIds as $uid) {
            DB::table('medico')->insert(['id' => $uid, 'num_col' => null]);
        }
    }

    public function down(): void
    {
        Schema::table('medico', function (Blueprint $table) {
            $table->string('num_col', 10)->nullable(false)->change();
        });
    }
};
