<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')
                  ->constrained('uploads')
                  ->cascadeOnDelete();
            $table->json('row_data');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_rows');
    }
};
