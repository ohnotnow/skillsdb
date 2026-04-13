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
        Schema::table('agent_conversation_messages', function (Blueprint $table) {
            $table->json('meta')->change();
            $table->json('attachments')->change();
            $table->json('tool_calls')->change();
            $table->json('tool_results')->change();
            $table->json('usage')->change();
        });
    }

    public function down(): void
    {
        Schema::table('agent_conversation_messages', function (Blueprint $table) {
            $table->text('meta')->change();
            $table->text('attachments')->change();
            $table->text('tool_calls')->change();
            $table->text('tool_results')->change();
            $table->text('usage')->change();
        });
    }
};
