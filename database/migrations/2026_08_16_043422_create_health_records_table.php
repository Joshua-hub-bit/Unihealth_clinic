<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('record_date')->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('first_name', 80)->nullable();
            $table->string('middle_name', 80)->default('');
            $table->string('contact', 30)->default('');
            $table->string('course_year', 100)->nullable();
            $table->integer('age')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('ec_name', 150)->nullable();
            $table->string('ec_relationship', 80)->nullable();
            $table->string('ec_address', 255)->nullable();
            $table->string('ec_contact', 50)->nullable();
            $table->text('allergy_detail')->nullable();
            $table->text('accident_detail')->nullable();
            $table->text('hospitalization_detail')->nullable();
            $table->text('surgical_detail')->nullable();
            $table->text('family_history')->nullable();
            // Symptoms — stored as Y/N single char
            $table->char('sym_headache', 1)->default('N');
            $table->char('sym_dizziness', 1)->default('N');
            $table->char('sym_fainting', 1)->default('N');
            $table->char('sym_blurring_vision', 1)->default('N');
            $table->char('sym_hearing_difficulty', 1)->default('N');
            $table->char('sym_back_pain', 1)->default('N');
            $table->char('sym_chest_pain', 1)->default('N');
            $table->char('sym_breathing_difficulty', 1)->default('N');
            $table->char('sym_cough', 1)->default('N');
            $table->char('sym_sore_throat', 1)->default('N');
            $table->char('sym_runny_nose', 1)->default('N');
            $table->char('sym_nose_bleed', 1)->default('N');
            $table->char('sym_vomiting', 1)->default('N');
            $table->char('sym_nausea', 1)->default('N');
            $table->char('sym_abdominal_pain', 1)->default('N');
            $table->char('sym_fever', 1)->default('N');
            $table->char('sym_frequent_urination', 1)->default('N');
            $table->char('sym_weight_loss', 1)->default('N');
            $table->char('sym_difficulty_sleeping', 1)->default('N');
            $table->char('sym_nervousness', 1)->default('N');
            // Immunizations
            $table->boolean('imm_chicken_pox')->default(false);
            $table->boolean('imm_typhoid')->default(false);
            $table->boolean('imm_hepatitis_a')->default(false);
            $table->boolean('imm_hepatitis_b')->default(false);
            $table->boolean('imm_tetanus')->default(false);
            $table->boolean('imm_covid')->default(false);
            // Menstrual
            $table->boolean('menstrual_regular')->nullable();
            $table->boolean('menstrual_irregular')->nullable();
            $table->string('excessive_blood_flow', 10)->default('');
            $table->string('avg_days', 10)->nullable();
            $table->string('dysmenorrhea', 10)->default('');
            // Vitals
            $table->string('bp', 20)->nullable();
            $table->string('pr', 20)->nullable();
            $table->decimal('temp', 4, 1)->nullable();
            // Meta
            $table->string('status', 20)->default('submitted');
            $table->text('findings_recommendation')->nullable();
            $table->string('signature_name', 150)->nullable();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
