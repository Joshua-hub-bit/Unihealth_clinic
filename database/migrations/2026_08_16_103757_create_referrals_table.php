<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referral_no', 30)->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('to_campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('referred_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('referred_to_name', 150)->nullable();
            $table->string('specialty', 150)->nullable();
            $table->string('referral_type', 30)->default('inter-campus'); // inter-campus, external
            $table->text('diagnosis')->nullable();
            $table->text('history')->nullable();
            $table->text('reason')->nullable();
            $table->text('management_given')->nullable();
            $table->string('urgency', 20)->default('routine'); // routine, urgent, emergency
            $table->string('status', 30)->default('pending'); // pending, accepted, completed, cancelled
            $table->text('feedback')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('from_campus_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
