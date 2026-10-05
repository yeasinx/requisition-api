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
        Schema::create('cc_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('email')->index(); // Unique among active contacts (enforced in validation, soft deletes allow reuse)
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cc_contact_requisition', function (Blueprint $table) {
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->foreignId('cc_contact_id')->constrained('cc_contacts')->onDelete('cascade');
            $table->primary(['requisition_id', 'cc_contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cc_contact_requisition');
        Schema::dropIfExists('cc_contacts');
    }
};
