<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ip_guards', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->enum('type', ['whitelist', 'blacklist']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['ip_address', 'type']);
            $table->index(['type', 'is_active']);
            $table->unique(['ip_address', 'type']); // Prevent duplicate IPs in same type
        });
    }

    public function down()
    {
        Schema::dropIfExists('ip_guards');
    }
};
