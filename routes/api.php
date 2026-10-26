<?php

use App\Http\Controllers\Api\Clinic\AccountController;
use App\Http\Controllers\Api\Clinic\AdminController;
use App\Http\Controllers\Api\Clinic\AvailabilityController;
use App\Http\Controllers\Api\Clinic\AppointmentWorkflowController;
use App\Http\Controllers\Api\Clinic\AuthController;
use App\Http\Controllers\Api\Clinic\ConsultationController;
use App\Http\Controllers\Api\Clinic\DecisionSupportController;
use App\Http\Controllers\Api\Clinic\MessageController;
use App\Http\Controllers\Api\Clinic\NotificationController;
use App\Http\Controllers\Api\Clinic\NurseController;
use App\Http\Controllers\Api\Clinic\NurseHeadController;
use App\Http\Controllers\Api\Clinic\ReferralController;
use App\Http\Controllers\Api\Clinic\ServiceController;
use App\Http\Controllers\Api\Clinic\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('clinic')->group(function () {

    Route::middleware('clinic.identity:nurse,nurse_head')->prefix('availability')->group(function () {
        Route::get('/', [AvailabilityController::class, 'index']);
        Route::post('/', [AvailabilityController::class, 'store']);
        Route::delete('{id}', [AvailabilityController::class, 'destroy'])->whereNumber('id');
    });

    Route::middleware('clinic.identity')->prefix('account')->group(function () {
        Route::get('profile', [AccountController::class, 'show']);
        Route::put('profile', [AccountController::class, 'update']);
        Route::post('profile/picture', [AccountController::class, 'updatePicture']);
    });

    // -----------------------------------------------------------------------
    // Auth  (public)
    // -----------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('campuses', [AuthController::class, 'campuses']);
    });

    // -----------------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------------
    Route::prefix('admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('stats', [AdminController::class, 'stats']);
        Route::middleware(['clinic.identity:admin,nurse_head,nurse,student', 'throttle:5,1'])->group(function () {
            Route::post('decision-support/analyze', [DecisionSupportController::class, 'analyze']);
        });

        // Students
        Route::get('students', [AdminController::class, 'students']);
        Route::post('students', [AdminController::class, 'createStudent']);
        Route::get('students/{studentNo}', [AdminController::class, 'student']);
        Route::put('students/{studentNo}', [AdminController::class, 'updateStudent']);
        Route::delete('students/{studentNo}', [AdminController::class, 'deleteStudent']);

        // Admin-managed non-student patient accounts
        Route::middleware('clinic.identity:admin')->prefix('patient-accounts')->group(function () {
            Route::get('/', [AdminController::class, 'additionalPatientAccounts']);
            Route::post('/', [AdminController::class, 'createAdditionalPatientAccount']);
            Route::put('{id}', [AdminController::class, 'updateAdditionalPatientAccount'])->whereNumber('id');
            Route::patch('{id}/status', [AdminController::class, 'updateAdditionalPatientAccountStatus'])->whereNumber('id');
        });

        Route::middleware('clinic.identity:admin')->prefix('clinicians')->group(function () {
            Route::get('/', [AdminController::class, 'clinicians']);
            Route::post('/', [AdminController::class, 'storeClinician']);
            Route::put('{id}', [AdminController::class, 'updateClinician'])->whereNumber('id');
            Route::delete('{id}', [AdminController::class, 'deleteClinician'])->whereNumber('id');
        });

        // Staff / users
        Route::get('staff', [AdminController::class, 'staff']);
        Route::post('staff', [AdminController::class, 'addStaff']);
        Route::get('staff/{employeeId}', [AdminController::class, 'getStaff']);
        Route::put('staff/{employeeId}', [AdminController::class, 'updateStaff']);
        Route::delete('staff/{employeeId}', [AdminController::class, 'deleteStaff']);
        Route::post('staff/{employeeId}/campus', [AdminController::class, 'assignStaffCampus']);

        // Appointments
        Route::get('appointments', [AdminController::class, 'appointments']);
        Route::patch('appointments/{id}', [AdminController::class, 'updateAppointmentStatus']);

        Route::middleware('clinic.identity:admin')->group(function () {
            Route::get('settings', [AdminController::class, 'settings']);
            Route::put('settings', [AdminController::class, 'updateSetting']);
            Route::post('settings/models', [AdminController::class, 'saveAiModelSettings']);
            Route::post('settings/save', [AdminController::class, 'saveClinicSettings']);
            Route::delete('referrals/{id}', [AdminController::class, 'deleteReferral'])->whereNumber('id');
            Route::get('referrals/{id}/prescription', [AdminController::class, 'referralPrescription'])->whereNumber('id');
            Route::get('referrals/{id}/prescription/view', [AdminController::class, 'viewReferralPrescription'])->whereNumber('id');
            Route::get('referrals/{id}/prescription/download', [AdminController::class, 'downloadReferralPrescription'])->whereNumber('id');
        });

        // Records & reports
        Route::get('records', [AdminController::class, 'records']);
        Route::get('reports', [AdminController::class, 'reports']);
        Route::middleware('clinic.identity:admin')->group(function () {
            Route::get('medical-records/campuses', [AdminController::class, 'medicalRecordCampuses']);
            Route::get('medical-records/students', [AdminController::class, 'searchMedicalRecordStudents']);
            Route::get('medical-records/students/{studentId}/records', [AdminController::class, 'medicalRecordsForStudent'])->whereNumber('studentId');
            Route::get('medical-records', [AdminController::class, 'medicalRecords']);
            Route::post('medical-records', [AdminController::class, 'storeMedicalRecord']);
            Route::get('dental-records/campuses', [AdminController::class, 'dentalRecordCampuses']);
            Route::get('dental-records', [AdminController::class, 'dentalRecordsForCampus']);
            Route::get('dental-records/students/{studentId}/records', [AdminController::class, 'dentalRecordsForStudent'])->whereNumber('studentId');
            Route::get('dental-records/{id}', [AdminController::class, 'dentalRecord'])->whereNumber('id');
            Route::post('dental-records', [AdminController::class, 'storeDentalRecord']);
        });

        // Audit log
        Route::get('audit-log', [AdminController::class, 'auditLog']);

        // Campuses
        Route::get('campuses', [AdminController::class, 'campuses']);

        // System settings
        // Services
        Route::get('services', [AdminController::class, 'services']);
        Route::post('services', [AdminController::class, 'addService']);
        Route::delete('services/{id}', [AdminController::class, 'deleteService']);

        // Backup
        Route::post('backup/restore', [AdminController::class, 'restoreBackup']);

        // Data export
        Route::get('export', [AdminController::class, 'export']);
    });

    // -----------------------------------------------------------------------
    // Nurse Head
    // -----------------------------------------------------------------------
    Route::prefix('nurse-head')->group(function () {
        Route::middleware('clinic.identity:nurse_head')->prefix('consultations')->group(function () {
            Route::get('campuses', [ConsultationController::class, 'nurseConsultationCampuses']);
            Route::get('/', [ConsultationController::class, 'nurseIndex']);
            Route::get('{consultation}', [ConsultationController::class, 'nurseShow'])->whereNumber('consultation');
            Route::delete('{consultation}', [ConsultationController::class, 'destroyNurseRequest'])->whereNumber('consultation');
            Route::post('{consultation}/accept', [ConsultationController::class, 'accept'])->whereNumber('consultation');
            Route::post('{consultation}/decline', [ConsultationController::class, 'decline'])->whereNumber('consultation');
            Route::post('{consultation}/refer', [ConsultationController::class, 'referToClinician'])->whereNumber('consultation');
            Route::post('{consultation}/recording/transcribe', [ConsultationController::class, 'retryTranscription'])->whereNumber('consultation')->middleware('throttle:5,1');
            Route::post('{consultation}/recording/retry', [ConsultationController::class, 'retryTranscription'])->whereNumber('consultation')->middleware('throttle:5,1');
            Route::get('{consultation}/media/{kind}', [ConsultationController::class, 'downloadMedia'])->whereNumber('consultation');
        });

        // Staff
        Route::get('staff', [NurseHeadController::class, 'staff']);
        Route::post('staff', [NurseHeadController::class, 'addStaff']);
        Route::get('staff/{employeeId}', [NurseHeadController::class, 'getStaff']);
        Route::put('staff/{employeeId}', [NurseHeadController::class, 'updateStaff']);
        Route::delete('staff/{employeeId}', [NurseHeadController::class, 'deleteStaff']);
        Route::post('staff/{employeeId}/campus', [NurseHeadController::class, 'assignStaffCampus']);

        // Campuses (used by the Add Staff modal & campus filter)
        Route::get('campuses', [NurseHeadController::class, 'campuses']);

        // Records & reports
        Route::get('records', [NurseHeadController::class, 'records']);
        Route::get('records-vital', [NurseHeadController::class, 'recordsVital']);
        Route::get('records-referral', [NurseHeadController::class, 'recordsReferral']);
        Route::middleware('clinic.identity:nurse_head')->group(function () {
            Route::get('search-student', [NurseController::class, 'searchStudent']);
            Route::get('student-form/{studentNo}', [NurseController::class, 'studentForForm']);
            Route::get('forms', [NurseController::class, 'forms']);
            Route::post('forms', [NurseController::class, 'saveForm']);
            Route::post('forms/{id}/print', [NurseController::class, 'printForm'])->whereNumber('id');
            Route::post('visits', [NurseController::class, 'saveVisit']);
            Route::patch('visits/{id}', [NurseController::class, 'updateVitalSigns'])->whereNumber('id');
            Route::delete('visits/{id}', [NurseController::class, 'deleteVitalSigns'])->whereNumber('id');
            Route::post('visits/{id}/delete', [NurseController::class, 'deleteVitalSigns'])->whereNumber('id');
            Route::get('medical-records/campuses', [NurseHeadController::class, 'medicalRecordCampuses']);
            Route::get('medical-records/students', [NurseHeadController::class, 'searchMedicalRecordStudents']);
            Route::get('medical-records/students/{studentId}/records', [NurseHeadController::class, 'medicalRecordsForStudent'])->whereNumber('studentId');
            Route::get('medical-records', [NurseHeadController::class, 'medicalRecords']);
            Route::post('medical-records', [NurseHeadController::class, 'storeMedicalRecord']);
            Route::get('dental-records/campuses', [NurseHeadController::class, 'dentalRecordCampuses']);
            Route::get('dental-records', [NurseHeadController::class, 'dentalRecordsForCampus']);
            Route::get('dental-records/students/{studentId}/records', [NurseHeadController::class, 'dentalRecordsForStudent'])->whereNumber('studentId');
            Route::get('dental-records/{id}', [NurseHeadController::class, 'dentalRecord'])->whereNumber('id');
            Route::post('dental-records', [NurseHeadController::class, 'storeDentalRecord']);
        });

        // Single-record detail endpoints (used by the View modal in
        // the nurse-head dashboard). The {id} is constrained to digits
        // so the routes resolve cleanly even if a stray string slips in.
        Route::get('records/{id}', [NurseHeadController::class, 'recordMedical'])->whereNumber('id');
        Route::put('records/{id}', [NurseHeadController::class, 'updateMedicalRecord'])->whereNumber('id');
        Route::delete('records/{id}', [NurseHeadController::class, 'deleteMedicalRecord'])->whereNumber('id');
        Route::get('records-vital/{id}', [NurseHeadController::class, 'recordVital'])->whereNumber('id');
        Route::get('records-referral/{id}', [NurseHeadController::class, 'recordReferral'])->whereNumber('id');

        // Students (Patient Information — list, update, delete)
        Route::get('students', [NurseHeadController::class, 'students']);
        Route::get('students/{studentNo}', [NurseHeadController::class, 'student']);
        Route::put('students/{studentNo}', [NurseHeadController::class, 'updateStudent']);
        Route::delete('students/{studentNo}', [NurseHeadController::class, 'deleteStudent']);

        Route::get('reports', [NurseHeadController::class, 'reports']);

        // Appointments
        Route::get('appointments', [NurseHeadController::class, 'appointments']);
        Route::post('appointments/{id}/triage', [NurseHeadController::class, 'triageAppointment'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse_head');
        Route::patch('appointments/{id}', [NurseHeadController::class, 'updateAppointmentStatus'])
            ->middleware('clinic.identity:nurse_head');
        Route::post('appointments/{id}/accept', [AppointmentWorkflowController::class, 'accept'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse_head');
        Route::post('appointments/{id}/schedule', [AppointmentWorkflowController::class, 'schedule'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse_head');
        Route::post('appointments/{id}/checkup-schedule', [AppointmentWorkflowController::class, 'scheduleCheckup'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse_head');
        Route::post('appointments/{id}/checkup-complete', [AppointmentWorkflowController::class, 'completeCheckup'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse_head');
        Route::get('signing-personnel', [NurseController::class, 'signingPersonnel']);
    });

    // -----------------------------------------------------------------------
    // Nurse
    // -----------------------------------------------------------------------
    Route::prefix('nurse')->group(function () {
        Route::middleware('clinic.identity:nurse')->prefix('consultations')->group(function () {
            Route::get('campuses', [ConsultationController::class, 'nurseConsultationCampuses']);
            Route::get('/', [ConsultationController::class, 'nurseIndex']);
            Route::get('{consultation}', [ConsultationController::class, 'nurseShow'])->whereNumber('consultation');
            Route::delete('{consultation}', [ConsultationController::class, 'destroyNurseRequest'])->whereNumber('consultation');
            Route::post('{consultation}/accept', [ConsultationController::class, 'accept'])->whereNumber('consultation');
            Route::post('{consultation}/decline', [ConsultationController::class, 'decline'])->whereNumber('consultation');
            Route::post('{consultation}/recording/transcribe', [ConsultationController::class, 'retryTranscription'])->whereNumber('consultation')->middleware('throttle:5,1');
            Route::post('{consultation}/recording/retry', [ConsultationController::class, 'retryTranscription'])->whereNumber('consultation')->middleware('throttle:5,1');
            Route::post('{consultation}/review', [ConsultationController::class, 'completeNurseReview'])->whereNumber('consultation');
            Route::post('{consultation}/advice', [ConsultationController::class, 'giveNursingAdvice'])->whereNumber('consultation');
            Route::post('{consultation}/refer', [ConsultationController::class, 'referToClinician'])->whereNumber('consultation');
            Route::get('{consultation}/media/{kind}', [ConsultationController::class, 'downloadMedia'])->whereNumber('consultation');
        });
        Route::get('search-student', [NurseController::class, 'searchStudent']);
        Route::get('student-form/{studentNo}', [NurseController::class, 'studentForForm']);
        Route::get('patients', [NurseController::class, 'patients']);
        Route::get('visits', [NurseController::class, 'visits']);
        Route::post('visits', [NurseController::class, 'saveVisit']);
        Route::get('visits/{id}', [NurseController::class, 'visit']);
        Route::patch('visits/{id}', [NurseController::class, 'updateVitalSigns'])->whereNumber('id')->middleware('clinic.identity:nurse');
        Route::delete('visits/{id}', [NurseController::class, 'deleteVitalSigns'])->whereNumber('id')->middleware('clinic.identity:nurse');
        Route::post('visits/{id}/delete', [NurseController::class, 'deleteVitalSigns'])->whereNumber('id')->middleware('clinic.identity:nurse');
        Route::middleware('clinic.identity:nurse')->group(function () {
            Route::get('forms', [NurseController::class, 'forms']);
            Route::post('forms', [NurseController::class, 'saveForm']);
            Route::post('forms/{id}/print', [NurseController::class, 'printForm'])->whereNumber('id');
            Route::get('medical-records/campuses', [NurseController::class, 'medicalRecordCampuses']);
            Route::get('medical-records/students', [NurseController::class, 'searchMedicalRecordStudents']);
            Route::get('medical-records/students/{studentId}/records', [NurseController::class, 'medicalRecordsForStudent'])->whereNumber('studentId');
            Route::get('medical-records', [NurseController::class, 'medicalRecords']);
            Route::post('medical-records', [NurseController::class, 'storeMedicalRecord']);
            Route::get('dental-records/campuses', [NurseController::class, 'dentalRecordCampuses']);
            Route::get('dental-records', [NurseController::class, 'dentalRecordsForCampus']);
            Route::get('dental-records/students/{studentId}/records', [NurseController::class, 'dentalRecordsForStudent'])->whereNumber('studentId');
            Route::get('dental-records/{id}', [NurseController::class, 'dentalRecord'])->whereNumber('id');
            Route::post('dental-records', [NurseController::class, 'storeDentalRecord']);
        });
        Route::get('queue', [NurseController::class, 'queue']);
        Route::patch('queue/{type}/{id}', [NurseController::class, 'updateQueueStatus'])
            ->middleware('clinic.identity:nurse');
        Route::post('appointments/{id}/accept', [AppointmentWorkflowController::class, 'accept'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse');
        Route::post('appointments/{id}/schedule', [AppointmentWorkflowController::class, 'schedule'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse');
        Route::post('appointments/{id}/checkup-schedule', [AppointmentWorkflowController::class, 'scheduleCheckup'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse');
        Route::post('appointments/{id}/checkup-complete', [AppointmentWorkflowController::class, 'completeCheckup'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse');
        Route::post('queue/appointment/{id}/triage', [NurseController::class, 'triageAppointment'])
            ->whereNumber('id')
            ->middleware('clinic.identity:nurse');
        Route::middleware('clinic.identity:nurse')->group(function () {
            Route::get('referrals', [NurseController::class, 'referrals']);
            Route::get('referrals/{id}', [NurseController::class, 'referral'])->whereNumber('id');
        });
        Route::get('stats', [NurseController::class, 'nurseStats']);
        Route::get('signing-personnel', [NurseController::class, 'signingPersonnel']);
    });

    // -----------------------------------------------------------------------
    // Referrals (readable by clinic staff)
    // -----------------------------------------------------------------------
    Route::prefix('referrals')->group(function () {
        Route::middleware('clinic.identity:admin,nurse_head,nurse,student')->group(function () {
            Route::get('awaiting-schedule', [ReferralController::class, 'awaitingSchedule'])
                ->middleware('clinic.identity:nurse_head,nurse');
            Route::get('/', [ReferralController::class, 'index']);
            Route::get('stats', [ReferralController::class, 'stats']);
            Route::get('{id}', [ReferralController::class, 'show'])->whereNumber('id');
        });
        Route::post('/', [ReferralController::class, 'store'])->middleware('clinic.identity:nurse,nurse_head');
    });

    // -----------------------------------------------------------------------
    // Messages
    // -----------------------------------------------------------------------
    Route::prefix('messages')->group(function () {
        Route::get('channels', [MessageController::class, 'channels']);
        Route::post('channels', [MessageController::class, 'createChannel']);
        Route::post('channels/seed', [MessageController::class, 'seedChannels']);
        // DM-specific endpoints (must be before the {channelId} wildcard)
        Route::middleware('clinic.identity:nurse,nurse_head')->group(function () {
            Route::post('presence', [MessageController::class, 'presence']);
            Route::get('staff-list', [MessageController::class, 'staffList']);
            Route::post('direct', [MessageController::class, 'direct']);
            Route::get('conversations', [MessageController::class, 'conversations']);
            Route::get('staff/{staffId}/profile-picture', [MessageController::class, 'staffPicture']);
            Route::delete('{channelId}/conversation', [MessageController::class, 'deleteConversation'])->whereNumber('channelId');
            Route::delete('{channelId}/person', [MessageController::class, 'deletePerson'])->whereNumber('channelId');
            Route::get('{channelId}/{messageId}/attachment', [MessageController::class, 'attachment'])->whereNumber(['channelId', 'messageId']);
            Route::patch('{channelId}/{messageId}', [MessageController::class, 'updateMessage'])->whereNumber(['channelId', 'messageId']);
            Route::delete('{channelId}/{messageId}', [MessageController::class, 'unsend'])->whereNumber(['channelId', 'messageId']);
            Route::patch('{channelId}/{messageId}/pin', [MessageController::class, 'pin'])->whereNumber(['channelId', 'messageId']);
            Route::post('{channelId}/{messageId}/report', [MessageController::class, 'report'])->whereNumber(['channelId', 'messageId']);
            Route::post('{channelId}/{messageId}/forward', [MessageController::class, 'forward'])->whereNumber(['channelId', 'messageId']);
            Route::get('{channelId}', [MessageController::class, 'messages']);
            Route::post('{channelId}', [MessageController::class, 'send']);
        });
    });

    // -----------------------------------------------------------------------
    // Notifications
    // -----------------------------------------------------------------------
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::patch('{id}/read', [NotificationController::class, 'markRead']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);
    });

    // -----------------------------------------------------------------------
    // Clinic services catalog
    // -----------------------------------------------------------------------
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('{id}', [ServiceController::class, 'update']);
        Route::delete('{id}', [ServiceController::class, 'destroy']);
    });

    // -----------------------------------------------------------------------
    // Staff personal health-record access (read-only)
    // -----------------------------------------------------------------------
    Route::middleware('clinic.auth:admin,nurse,nurse_head')
        ->prefix('staff')
        ->group(function () {
            Route::get('students/{studentNo}/health-record', [StudentController::class, 'staffHealthRecord']);
        });

    // -----------------------------------------------------------------------
    // Student (self-service)
    // -----------------------------------------------------------------------
    Route::prefix('student')->group(function () {
        Route::middleware('clinic.identity:student')->group(function () {
            Route::get('prescription-forms', [StudentController::class, 'prescriptionForms']);
            Route::get('prescription-forms/{id}/pdf', [StudentController::class, 'downloadPrescriptionForm'])->whereNumber('id');
        });
        Route::middleware('clinic.identity:student')->prefix('consultations')->group(function () {
            Route::get('/', [ConsultationController::class, 'studentIndex']);
            Route::post('/', [ConsultationController::class, 'store'])->middleware('throttle:10,1');
            Route::post('preview-transcript', [ConsultationController::class, 'previewTranscript'])->middleware('throttle:6,1');
            Route::get('{consultation}', [ConsultationController::class, 'studentShow'])->whereNumber('consultation');
            Route::put('{consultation}', [ConsultationController::class, 'updateStudentRequest'])->whereNumber('consultation');
            Route::delete('{consultation}', [ConsultationController::class, 'destroyStudentRequest'])->whereNumber('consultation');
            Route::get('{consultation}/media/{kind}', [ConsultationController::class, 'downloadMedia'])->whereNumber('consultation');
        });
        Route::get('health-record', [StudentController::class, 'getHealthRecord']);
        Route::post('health-record', [StudentController::class, 'saveHealthRecord']);
        Route::get('profile', [StudentController::class, 'profile']);
        Route::put('profile', [StudentController::class, 'updateProfile']);
        Route::post('profile/picture', [StudentController::class, 'uploadPicture']);
        Route::get('visits', [StudentController::class, 'visits']);
        Route::get('appointments', [StudentController::class, 'appointments'])
            ->middleware('clinic.identity:student');
        Route::get('availability', [AvailabilityController::class, 'studentIndex'])
            ->middleware('clinic.identity:student');
        Route::post('appointments', [StudentController::class, 'saveAppointment'])
            ->middleware('clinic.identity:student');
        Route::delete('appointments/{id}', [StudentController::class, 'cancelAppointment'])
            ->middleware('clinic.identity:student');
        Route::get('medications', [StudentController::class, 'medications']);
        Route::post('medications', [StudentController::class, 'saveMedication']);
        Route::delete('medications/{id}', [StudentController::class, 'deleteMedication']);
        Route::get('stats', [StudentController::class, 'stats']);
        Route::get('search', [StudentController::class, 'searchStudent']);
    });
});
