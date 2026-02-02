<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
      public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('prenom');
            $table->string('nom');
            $table->integer('age');
            $table->string('adresse');
            $table->string('telephone');
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->date('date_prescrit')->nullable();
            $table->date('date_edite')->nullable();
            $table->text('diagnostic')->nullable();
            // $table->timestamps(); si tu veux garder les timestamps, sinon false
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
