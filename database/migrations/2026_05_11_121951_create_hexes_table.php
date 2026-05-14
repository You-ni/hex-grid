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
        Schema::create('hexes', function (Blueprint $table) {
            $table->id();

            $table->json('coordinates');

            $table->enum('security_level', [1, 2, 3])->nullable();
            $table->enum('type', ['deposit', 'combat', 'ability', 'sociality', 'rest'])->nullable();

            $table->enum('key_dice', ['D1', 'D2', 'D4', 'D6', 'D8', 'D10', 'D12'])->default('D12');
            $table->boolean('has_key')->default(false);

            $table->enum('data_dice', ['D6'])->default('D6');
            $table->unsignedInteger('dump')->nullable();

            $table->enum('box_dice', ['D6'])->default('D6');
            $table->enum('box_item_dice', ['D4', 'D6', 'D8'])->nullable();
            $table->boolean('has_box')->nullable();

            $table->enum('corridor_dice', ['D3', 'D4', 'D6', 'D8'])->nullable();
            $table->unsignedInteger('corridors')->nullable();

            $table->boolean('generated')->default(false);
            $table->boolean('resolved')->default(false);
            $table->boolean('saved')->default(false);
            $table->boolean('visible')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hexes');
    }
};
