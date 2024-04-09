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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers');
            $table->string('banner_path');
            $table->string('thumbnail_path');
            $table->string('name');
            $table->string('slug');
            $table->enum('category', ['Music Concerts', 'Seminar', 'Art Festivals', 'Theater', 'Galas', 'Sports', 'Workshops']);
            $table->integer('capacity');
            $table->dateTime('start_at');
            $table->dateTime('closed_at');
            $table->text('address');
            $table->string('region');
            $table->string('province');
            $table->string('city');
            $table->string('zip');
            $table->string('coordinates');
            $table->text('description');
            $table->text('refund_rules')->nullable()->default('');
            $table->string('keywords')->nullable()->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
