<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
     public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('analyse_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analyse_id')->constrained('analyses')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->json('donnees')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyse_archives');
    }
};
