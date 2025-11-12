<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // Sender
            
            $table->enum('channel', ['email', 'phone', 'in_person', 'portal', 'sms'])->default('portal');
            $table->string('contact_person')->nullable();
            
            $table->string('subject')->nullable();
            $table->text('summary');
            $table->text('next_action')->nullable();
            
            $table->timestamps();
            
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
