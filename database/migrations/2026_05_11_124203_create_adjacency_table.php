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
        Schema::create('adjacencies', function (Blueprint $table) {
            $table->foreignId('hex_id')
                ->constrained('hexes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('adjacent_hex_id')
                ->constrained('hexes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->boolean('has_corridor')->default(false);
            $table->timestamps();

            $table->primary(['hex_id', 'adjacent_hex_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjacencies');
    }
};
