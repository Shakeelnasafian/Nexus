<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->constrained('workflow_runs')->cascadeOnDelete();
            $table->foreignId('workflow_step_run_id')->constrained('workflow_step_runs')->cascadeOnDelete();
            $table->string('channel', 100);
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'workflow_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_notifications');
    }
};
