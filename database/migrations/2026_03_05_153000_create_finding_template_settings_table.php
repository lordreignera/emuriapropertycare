<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_template_settings', function (Blueprint $table) {
            $table->id();
            $table->string('task_question', 255);
            $table->string('category', 120)->nullable();
            $table->unsignedTinyInteger('default_priority')->default(2);
            $table->boolean('default_included')->default(true);
            $table->decimal('default_labour_hours', 8, 2)->default(0);
            $table->string('photo_reference', 50)->nullable();
            $table->text('default_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_template_settings');
    }
};
