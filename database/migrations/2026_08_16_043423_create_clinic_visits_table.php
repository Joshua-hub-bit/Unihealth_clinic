<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('visit_date');
            $table->time('visit_time')->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('symptoms')->nullable();
            $table->text('treatment')->nullable();
            $table->string('bp', 20)->nullable();
            $table->decimal('temp', 4, 1)->nullable();
            $table->integer('pulse')->nullable();
            $table->integer('rr')->nullable();
            $table->integer('o2_sat')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->foreignId('nurse_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('physician_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('disposition', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_visits');
    }
};
