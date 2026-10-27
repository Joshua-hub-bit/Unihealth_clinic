-- Schema-only export derived from clinics_sunn.sql. All database rows and settings data are excluded.

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `appt_date` date DEFAULT NULL,
  `checkup_date` date DEFAULT NULL,
  `checkup_time` time DEFAULT NULL,
  `checkup_completed_at` timestamp NULL DEFAULT NULL,
  `appt_time` time DEFAULT NULL,
  `preferred_time` varchar(20) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_professional_id` bigint(20) UNSIGNED DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `availability_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `triage_priority` varchar(20) DEFAULT NULL,
  `triage_confidence` decimal(5,4) DEFAULT NULL,
  `triage_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`triage_details`)),
  `triage_log_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL DEFAULT '',
  `contact` varchar(50) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinic_availabilities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campus_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `provider_type` varchar(20) NOT NULL,
  `available_date` date NOT NULL,
  `opens_at` time NOT NULL,
  `closes_at` time NOT NULL,
  `capacity` tinyint(3) UNSIGNED NOT NULL,
  `available_times` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`available_times`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinic_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_type` varchar(20) NOT NULL DEFAULT 'student',
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(150) NOT NULL,
  `body` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinic_providers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `provider_type` varchar(20) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `license_no` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinic_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'Medical',
  `description` text DEFAULT NULL,
  `duration_minutes` varchar(20) DEFAULT NULL,
  `requires_appointment` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinic_visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `bp` varchar(20) DEFAULT NULL,
  `temp` decimal(4,1) DEFAULT NULL,
  `pulse` int(11) DEFAULT NULL,
  `rr` int(11) DEFAULT NULL,
  `o2_sat` int(11) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `nurse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `physician_id` bigint(20) UNSIGNED DEFAULT NULL,
  `disposition` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultation_recordings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consultation_request_id` bigint(20) UNSIGNED NOT NULL,
  `student_voice_data` longblob DEFAULT NULL,
  `student_voice_mime` varchar(100) DEFAULT NULL,
  `student_voice_filename` varchar(255) DEFAULT NULL,
  `official_audio_data` longblob DEFAULT NULL,
  `official_audio_mime` varchar(100) DEFAULT NULL,
  `official_audio_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultation_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nurse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `physician_id` bigint(20) UNSIGNED DEFAULT NULL,
  `clinic_visit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `appointment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `referral_id` bigint(20) UNSIGNED DEFAULT NULL,
  `medical_transcription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `triage_log_id` bigint(20) UNSIGNED DEFAULT NULL,
  `care_type` varchar(20) NOT NULL DEFAULT 'medical',
  `concern` text NOT NULL,
  `symptom_onset` varchar(120) DEFAULT NULL,
  `severity` tinyint(3) UNSIGNED DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'submitted',
  `decline_reason` text DEFAULT NULL,
  `triage_priority` varchar(20) DEFAULT NULL,
  `triage_confidence` decimal(5,4) DEFAULT NULL,
  `triage_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`triage_details`)),
  `vitals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vitals`)),
  `student_voice_path` varchar(255) DEFAULT NULL,
  `student_photo_path` varchar(255) DEFAULT NULL,
  `official_audio_path` varchar(255) DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `identity_confirmed_at` timestamp NULL DEFAULT NULL,
  `recording_consent_at` timestamp NULL DEFAULT NULL,
  `recording_started_at` timestamp NULL DEFAULT NULL,
  `audio_uploaded_at` timestamp NULL DEFAULT NULL,
  `nurse_reviewed_at` timestamp NULL DEFAULT NULL,
  `physician_reviewed_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `nurse_summary` text DEFAULT NULL,
  `staff_notes` text DEFAULT NULL,
  `nursing_advice` text DEFAULT NULL,
  `approved_instructions` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `follow_up` text DEFAULT NULL,
  `emergency_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dental_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `dentist_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dentist_provider_id` bigint(20) UNSIGNED DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `record_date` date NOT NULL,
  `chief_complaint` text DEFAULT NULL,
  `dental_history` text DEFAULT NULL,
  `oral_exam_findings` text DEFAULT NULL,
  `tooth_chart` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tooth_chart`)),
  `treatment_done` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `next_appointment` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `clinic_visit_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` text NOT NULL,
  `exception` text NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `forms_certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `form_type` varchar(50) NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `referral_id` bigint(20) UNSIGNED DEFAULT NULL,
  `staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`form_data`)),
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `printed_count` int(11) NOT NULL DEFAULT 0,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `emailed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `health_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `record_date` date DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `middle_name` varchar(80) NOT NULL DEFAULT '',
  `contact` varchar(30) NOT NULL DEFAULT '',
  `course_year` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `ec_name` varchar(150) DEFAULT NULL,
  `ec_relationship` varchar(80) DEFAULT NULL,
  `ec_address` varchar(255) DEFAULT NULL,
  `ec_contact` varchar(50) DEFAULT NULL,
  `allergy_detail` text DEFAULT NULL,
  `accident_detail` text DEFAULT NULL,
  `hospitalization_detail` text DEFAULT NULL,
  `surgical_detail` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `past_medical_history` text DEFAULT NULL,
  `sym_headache` char(1) NOT NULL DEFAULT 'N',
  `sym_dizziness` char(1) NOT NULL DEFAULT 'N',
  `sym_fainting` char(1) NOT NULL DEFAULT 'N',
  `sym_blurring_vision` char(1) NOT NULL DEFAULT 'N',
  `sym_hearing_difficulty` char(1) NOT NULL DEFAULT 'N',
  `sym_back_pain` char(1) NOT NULL DEFAULT 'N',
  `sym_chest_pain` char(1) NOT NULL DEFAULT 'N',
  `sym_breathing_difficulty` char(1) NOT NULL DEFAULT 'N',
  `sym_cough` char(1) NOT NULL DEFAULT 'N',
  `sym_sore_throat` char(1) NOT NULL DEFAULT 'N',
  `sym_runny_nose` char(1) NOT NULL DEFAULT 'N',
  `sym_nose_bleed` char(1) NOT NULL DEFAULT 'N',
  `sym_vomiting` char(1) NOT NULL DEFAULT 'N',
  `sym_nausea` char(1) NOT NULL DEFAULT 'N',
  `sym_abdominal_pain` char(1) NOT NULL DEFAULT 'N',
  `sym_fever` char(1) NOT NULL DEFAULT 'N',
  `sym_frequent_urination` char(1) NOT NULL DEFAULT 'N',
  `sym_weight_loss` char(1) NOT NULL DEFAULT 'N',
  `sym_difficulty_sleeping` char(1) NOT NULL DEFAULT 'N',
  `sym_nervousness` char(1) NOT NULL DEFAULT 'N',
  `imm_chicken_pox` tinyint(1) NOT NULL DEFAULT 0,
  `imm_typhoid` tinyint(1) NOT NULL DEFAULT 0,
  `imm_hepatitis_a` tinyint(1) NOT NULL DEFAULT 0,
  `imm_hepatitis_b` tinyint(1) NOT NULL DEFAULT 0,
  `imm_tetanus` tinyint(1) NOT NULL DEFAULT 0,
  `imm_covid` tinyint(1) NOT NULL DEFAULT 0,
  `menstrual_regular` tinyint(1) DEFAULT NULL,
  `menstrual_irregular` tinyint(1) DEFAULT NULL,
  `excessive_blood_flow` varchar(10) NOT NULL DEFAULT '',
  `avg_days` varchar(10) DEFAULT NULL,
  `dysmenorrhea` varchar(10) NOT NULL DEFAULT '',
  `bp` varchar(20) DEFAULT NULL,
  `pr` varchar(20) DEFAULT NULL,
  `temp` decimal(4,1) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'submitted',
  `findings_recommendation` text DEFAULT NULL,
  `signature_name` varchar(150) DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `imported_medical_history` text DEFAULT NULL,
  `imported_booking_choice` text DEFAULT NULL,
  `imported_reason_symptoms` text DEFAULT NULL,
  `imported_priority_level` varchar(50) DEFAULT NULL,
  `imported_prescriber` varchar(150) DEFAULT NULL,
  `imported_prescription` text DEFAULT NULL,
  `source_dataset` varchar(255) DEFAULT NULL,
  `source_record_no` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` text NOT NULL,
  `attempts` smallint(6) NOT NULL,
  `reserved_at` int(11) DEFAULT NULL,
  `available_at` int(11) NOT NULL,
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` text NOT NULL,
  `options` text DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medical_transcriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `consultation_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nurse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `physician_id` bigint(20) UNSIGNED DEFAULT NULL,
  `clinic_visit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `consultation_date` date NOT NULL,
  `raw_transcript` text DEFAULT NULL,
  `ai_draft` text DEFAULT NULL,
  `approved_notes` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `audio_duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `processing_duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `processing_started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `dosage` varchar(80) DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `purpose` varchar(150) DEFAULT NULL,
  `school_kept` tinyint(1) NOT NULL DEFAULT 0,
  `consent_on_file` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `prescribed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` varchar(20) NOT NULL DEFAULT 'staff',
  `body` text NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_unsent` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `pinned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `forwarded_from_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_disk` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `seen_at` timestamp NULL DEFAULT NULL,
  `external_message_id` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_attachments` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `data` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_channels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'campus',
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `participant_a` bigint(20) UNSIGNED DEFAULT NULL,
  `participant_b` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `messenger_psid` varchar(191) DEFAULT NULL,
  `assigned_staff_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_channel_user_states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `channel_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `hidden_before_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `person_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `reported_by` bigint(20) UNSIGNED NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ml_anomaly_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `log_date` date NOT NULL,
  `input_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input_features`)),
  `anomaly_score` decimal(8,6) DEFAULT NULL,
  `is_anomaly` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `reviewed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ml_traffic_predictions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `prediction_date` date NOT NULL,
  `predicted_count` int(11) NOT NULL,
  `actual_count` int(11) DEFAULT NULL,
  `input_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`input_features`)),
  `mae` decimal(8,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ml_triage_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `clinic_visit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `input_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input_features`)),
  `predicted_priority` varchar(20) NOT NULL,
  `confidence_score` decimal(5,4) DEFAULT NULL,
  `reviewed_by_staff` tinyint(1) NOT NULL DEFAULT 0,
  `final_priority` varchar(20) DEFAULT NULL,
  `model_version` varchar(50) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `referrals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `referral_no` varchar(30) NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `from_campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `referred_by` bigint(20) UNSIGNED DEFAULT NULL,
  `referred_to_name` varchar(150) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `referral_type` varchar(30) NOT NULL DEFAULT 'inter-campus',
  `route_to` varchar(20) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `history` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `management_given` text DEFAULT NULL,
  `urgency` varchar(20) NOT NULL DEFAULT 'routine',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `source_appointment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vital_signs` text DEFAULT NULL,
  `signature_name` varchar(150) DEFAULT NULL,
  `signature_role` varchar(150) DEFAULT NULL,
  `signature_license_no` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `staff` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'staff',
  `license_no` varchar(50) DEFAULT NULL,
  `contact` varchar(30) NOT NULL DEFAULT '',
  `specialization` varchar(150) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `profile_picture` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` varchar(30) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `course_year` varchar(100) NOT NULL DEFAULT '',
  `department` varchar(120) NOT NULL DEFAULT '',
  `section` varchar(30) NOT NULL DEFAULT '',
  `contact` varchar(30) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `sex` varchar(20) NOT NULL DEFAULT '',
  `civil_status` varchar(20) NOT NULL DEFAULT '',
  `age` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `profile_picture` mediumtext DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'student',
  `patient_type` varchar(20) NOT NULL DEFAULT 'student',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `campus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`setting_value`)),
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_triage_log_id_foreign` (`triage_log_id`),
  ADD KEY `restore_appointments_student_id_fk` (`student_id`),
  ADD KEY `restore_appointments_created_by_fk` (`created_by`),
  ADD KEY `restore_appointments_campus_id_fk` (`campus_id`),
  ADD KEY `appointments_assigned_professional_id_foreign` (`assigned_professional_id`),
  ADD KEY `appointments_availability_id_foreign` (`availability_id`);

ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `campuses`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `clinic_availabilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clinic_availability_day_unique` (`campus_id`,`provider_type`,`available_date`),
  ADD KEY `clinic_availabilities_created_by_foreign` (`created_by`);

ALTER TABLE `clinic_notifications`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `clinic_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_providers_provider_type_is_active_index` (`provider_type`,`is_active`),
  ADD KEY `clinic_providers_campus_id_foreign` (`campus_id`);

ALTER TABLE `clinic_services`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `clinic_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_clinic_visits_student_id_fk` (`student_id`),
  ADD KEY `restore_clinic_visits_nurse_id_fk` (`nurse_id`),
  ADD KEY `restore_clinic_visits_physician_id_fk` (`physician_id`),
  ADD KEY `restore_clinic_visits_campus_id_fk` (`campus_id`);

ALTER TABLE `consultation_recordings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consultation_recordings_consultation_request_id_unique` (`consultation_request_id`);

ALTER TABLE `consultation_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consultation_requests_appointment_id_unique` (`appointment_id`),
  ADD KEY `consultation_requests_clinic_visit_id_foreign` (`clinic_visit_id`),
  ADD KEY `consultation_requests_referral_id_foreign` (`referral_id`),
  ADD KEY `consultation_requests_medical_transcription_id_foreign` (`medical_transcription_id`),
  ADD KEY `consultation_requests_triage_log_id_foreign` (`triage_log_id`),
  ADD KEY `consultation_requests_campus_id_status_index` (`campus_id`,`status`),
  ADD KEY `consultation_requests_student_id_created_at_index` (`student_id`,`created_at`),
  ADD KEY `consultation_requests_nurse_id_status_index` (`nurse_id`,`status`),
  ADD KEY `consultation_requests_physician_id_status_index` (`physician_id`,`status`);

ALTER TABLE `dental_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dental_records_clinic_visit_id_unique` (`clinic_visit_id`),
  ADD KEY `restore_dental_records_student_id_fk` (`student_id`),
  ADD KEY `restore_dental_records_dentist_id_fk` (`dentist_id`),
  ADD KEY `restore_dental_records_campus_id_fk` (`campus_id`),
  ADD KEY `dental_records_dentist_provider_id_foreign` (`dentist_provider_id`);

ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `forms_certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_forms_certificates_student_id_fk` (`student_id`),
  ADD KEY `restore_forms_certificates_staff_id_fk` (`staff_id`),
  ADD KEY `restore_forms_certificates_campus_id_fk` (`campus_id`),
  ADD KEY `forms_certificates_referral_id_foreign` (`referral_id`);

ALTER TABLE `health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_health_records_student_id_fk` (`student_id`),
  ADD KEY `restore_health_records_campus_id_fk` (`campus_id`);

ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `medical_transcriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medical_transcriptions_consultation_request_id_foreign` (`consultation_request_id`),
  ADD KEY `restore_medical_transcriptions_student_id_fk` (`student_id`),
  ADD KEY `restore_medical_transcriptions_nurse_id_fk` (`nurse_id`),
  ADD KEY `restore_medical_transcriptions_physician_id_fk` (`physician_id`),
  ADD KEY `restore_medical_transcriptions_clinic_visit_id_fk` (`clinic_visit_id`),
  ADD KEY `restore_medical_transcriptions_campus_id_fk` (`campus_id`);

ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_medications_student_id_fk` (`student_id`),
  ADD KEY `restore_medications_prescribed_by_fk` (`prescribed_by`),
  ADD KEY `restore_medications_campus_id_fk` (`campus_id`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `messages_external_message_id_unique` (`external_message_id`),
  ADD KEY `messages_created_at_index` (`created_at`),
  ADD KEY `messages_channel_updated_at_id_index` (`channel_id`,`updated_at`,`id`),
  ADD KEY `messages_forwarded_from_id_index` (`forwarded_from_id`),
  ADD KEY `messages_channel_id_id_index` (`channel_id`,`id`);

ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`message_id`);

ALTER TABLE `message_channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dm_participants` (`participant_a`,`participant_b`),
  ADD UNIQUE KEY `message_channels_messenger_psid_unique` (`messenger_psid`),
  ADD KEY `message_channels_participant_a_index` (`participant_a`),
  ADD KEY `message_channels_participant_b_index` (`participant_b`),
  ADD KEY `restore_message_channels_campus_id_fk` (`campus_id`),
  ADD KEY `message_channels_assigned_staff_id_foreign` (`assigned_staff_id`);

ALTER TABLE `message_channel_user_states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_channel_user_states_channel_id_staff_id_unique` (`channel_id`,`staff_id`),
  ADD KEY `message_channel_user_states_staff_id_foreign` (`staff_id`);

ALTER TABLE `message_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_reports_message_id_reported_by_unique` (`message_id`,`reported_by`);

ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ml_anomaly_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ml_anomaly_logs_campus_id_log_date_index` (`campus_id`,`log_date`);

ALTER TABLE `ml_traffic_predictions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ml_traffic_predictions_campus_id_prediction_date_unique` (`campus_id`,`prediction_date`);

ALTER TABLE `ml_triage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_ml_triage_logs_student_id_fk` (`student_id`),
  ADD KEY `restore_ml_triage_logs_clinic_visit_id_fk` (`clinic_visit_id`),
  ADD KEY `restore_ml_triage_logs_campus_id_fk` (`campus_id`);

ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_referrals_student_id_fk` (`student_id`),
  ADD KEY `restore_referrals_from_campus_id_fk` (`from_campus_id`),
  ADD KEY `restore_referrals_to_campus_id_fk` (`to_campus_id`),
  ADD KEY `restore_referrals_referred_by_fk` (`referred_by`),
  ADD KEY `referrals_source_appointment_id_foreign` (`source_appointment_id`);

ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_staff_campus_id_fk` (`campus_id`);

ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restore_students_campus_id_fk` (`campus_id`),
  ADD KEY `students_patient_type_is_active_index` (`patient_type`,`is_active`);

ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `campuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `clinic_availabilities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `clinic_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `clinic_providers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `clinic_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `clinic_visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `consultation_recordings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `consultation_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `dental_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `forms_certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `health_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `medical_transcriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `medications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `message_channels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `message_channel_user_states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `message_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `ml_anomaly_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `ml_traffic_predictions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `ml_triage_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `referrals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `staff`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `system_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, ;

ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_assigned_professional_id_foreign` FOREIGN KEY (`assigned_professional_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_availability_id_foreign` FOREIGN KEY (`availability_id`) REFERENCES `clinic_availabilities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_triage_log_id_foreign` FOREIGN KEY (`triage_log_id`) REFERENCES `ml_triage_logs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_appointments_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_appointments_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_appointments_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `clinic_availabilities`
  ADD CONSTRAINT `clinic_availabilities_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clinic_availabilities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

ALTER TABLE `clinic_providers`
  ADD CONSTRAINT `clinic_providers_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;

ALTER TABLE `clinic_visits`
  ADD CONSTRAINT `restore_clinic_visits_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_clinic_visits_nurse_id_fk` FOREIGN KEY (`nurse_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_clinic_visits_physician_id_fk` FOREIGN KEY (`physician_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_clinic_visits_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `consultation_recordings`
  ADD CONSTRAINT `consultation_recordings_consultation_request_id_foreign` FOREIGN KEY (`consultation_request_id`) REFERENCES `consultation_requests` (`id`) ON DELETE CASCADE;

ALTER TABLE `consultation_requests`
  ADD CONSTRAINT `consultation_requests_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultation_requests_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultation_requests_clinic_visit_id_foreign` FOREIGN KEY (`clinic_visit_id`) REFERENCES `clinic_visits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultation_requests_medical_transcription_id_foreign` FOREIGN KEY (`medical_transcription_id`) REFERENCES `medical_transcriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultation_requests_nurse_id_foreign` FOREIGN KEY (`nurse_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultation_requests_physician_id_foreign` FOREIGN KEY (`physician_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultation_requests_referral_id_foreign` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultation_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultation_requests_triage_log_id_foreign` FOREIGN KEY (`triage_log_id`) REFERENCES `ml_triage_logs` (`id`) ON DELETE SET NULL;

ALTER TABLE `dental_records`
  ADD CONSTRAINT `dental_records_clinic_visit_id_foreign` FOREIGN KEY (`clinic_visit_id`) REFERENCES `clinic_visits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dental_records_dentist_provider_id_foreign` FOREIGN KEY (`dentist_provider_id`) REFERENCES `clinic_providers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_dental_records_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_dental_records_dentist_id_fk` FOREIGN KEY (`dentist_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_dental_records_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `forms_certificates`
  ADD CONSTRAINT `forms_certificates_referral_id_foreign` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_forms_certificates_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_forms_certificates_staff_id_fk` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_forms_certificates_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `health_records`
  ADD CONSTRAINT `restore_health_records_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_health_records_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `medical_transcriptions`
  ADD CONSTRAINT `medical_transcriptions_consultation_request_id_foreign` FOREIGN KEY (`consultation_request_id`) REFERENCES `consultation_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_medical_transcriptions_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_medical_transcriptions_clinic_visit_id_fk` FOREIGN KEY (`clinic_visit_id`) REFERENCES `clinic_visits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_medical_transcriptions_nurse_id_fk` FOREIGN KEY (`nurse_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_medical_transcriptions_physician_id_fk` FOREIGN KEY (`physician_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_medical_transcriptions_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `medications`
  ADD CONSTRAINT `restore_medications_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_medications_prescribed_by_fk` FOREIGN KEY (`prescribed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_medications_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `messages`
  ADD CONSTRAINT `restore_messages_channel_id_fk` FOREIGN KEY (`channel_id`) REFERENCES `message_channels` (`id`) ON DELETE CASCADE;

ALTER TABLE `message_attachments`
  ADD CONSTRAINT `message_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

ALTER TABLE `message_channels`
  ADD CONSTRAINT `message_channels_assigned_staff_id_foreign` FOREIGN KEY (`assigned_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_message_channels_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;

ALTER TABLE `message_channel_user_states`
  ADD CONSTRAINT `message_channel_user_states_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `message_channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_channel_user_states_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

ALTER TABLE `message_reports`
  ADD CONSTRAINT `message_reports_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

ALTER TABLE `ml_anomaly_logs`
  ADD CONSTRAINT `ml_anomaly_logs_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;

ALTER TABLE `ml_traffic_predictions`
  ADD CONSTRAINT `ml_traffic_predictions_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;

ALTER TABLE `ml_triage_logs`
  ADD CONSTRAINT `restore_ml_triage_logs_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_ml_triage_logs_clinic_visit_id_fk` FOREIGN KEY (`clinic_visit_id`) REFERENCES `clinic_visits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_ml_triage_logs_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_source_appointment_id_foreign` FOREIGN KEY (`source_appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_referrals_from_campus_id_fk` FOREIGN KEY (`from_campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_referrals_referred_by_fk` FOREIGN KEY (`referred_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restore_referrals_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `restore_referrals_to_campus_id_fk` FOREIGN KEY (`to_campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;

ALTER TABLE `staff`
  ADD CONSTRAINT `restore_staff_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;

ALTER TABLE `students`
  ADD CONSTRAINT `restore_students_campus_id_fk` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;
