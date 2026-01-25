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
        Schema::table('training_course_user', function (Blueprint $table) {
            $table->timestamp('requested_at')->nullable()->after('rating');
            $table->foreignId('approved_by')->nullable()->after('requested_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_course_user', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['requested_at', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
