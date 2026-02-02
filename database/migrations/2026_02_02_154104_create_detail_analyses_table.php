<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
     public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('detail_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analyse_id')->constrained('analyses')->cascadeOnDelete();
            $table->json('donnees')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_analyses');
    }
};
