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
        Schema::create('requisition_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('original_name');
            $table->string('path'); // Relative path on the private "local" disk
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // Bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_attachments');
    }
};
