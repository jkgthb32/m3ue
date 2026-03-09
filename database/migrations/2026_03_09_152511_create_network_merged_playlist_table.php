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
        Schema::disableForeignKeyConstraints();

        Schema::create('network_merged_playlist', function (Blueprint $table) {
            $table->foreignId('network_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('merged_playlist_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['network_id', 'merged_playlist_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_merged_playlist');
    }
};
