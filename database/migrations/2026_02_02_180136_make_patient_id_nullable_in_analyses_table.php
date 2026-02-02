<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            // ✅ Supprimer la contrainte de clé étrangère
            $table->dropForeign(['patient_id']);
            
            // ✅ Modifier la colonne pour être nullable
            $table->foreignId('patient_id')
                  ->nullable()
                  ->change();
            
            // ✅ Recréer la contrainte avec nullable
            $table->foreign('patient_id')
                  ->references('id')
                  ->on('patients')
                  ->nullOnDelete(); // Au lieu de cascadeOnDelete
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            
            $table->foreignId('patient_id')
                  ->nullable(false)
                  ->change();
            
            $table->foreign('patient_id')
                  ->references('id')
                  ->on('patients')
                  ->cascadeOnDelete();
        });
    }
};