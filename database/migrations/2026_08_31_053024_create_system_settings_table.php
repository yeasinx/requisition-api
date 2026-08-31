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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('first_approver_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('second_approver_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('business_controller_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('accounts_approver_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('hr_admin_approver_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
