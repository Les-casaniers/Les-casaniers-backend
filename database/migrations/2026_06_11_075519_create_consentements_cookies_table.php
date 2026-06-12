<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('consentements_cookies', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('choix', ['accepter', 'refuser']);
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();
            
            $table->index('session_id');
            $table->index('created_at');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('consentements_cookies');
    }
};