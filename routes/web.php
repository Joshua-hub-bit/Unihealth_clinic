<?php

use App\Models\Campus;
use App\Models\ClinicVisit;
use App\Models\Referral;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// -----------------------------------------------------------------------
// Landing & public pages
// -----------------------------------------------------------------------
Route::get('/', function () {
    $activeStudents = Student::active()->students()->count();
    $activeStaff = Staff::active()
        ->whereIn('role', ['nurse', 'nurse_head', 'physician', 'dentist'])
        ->count();
    $activeCampuses = Campus::active()->count();

    return view('clinic.landing', compact('activeStudents', 'activeStaff', 'activeCampuses'));
})->name('clinic.landing');

// -----------------------------------------------------------------------
// Dashboard routes — protected by clinic.auth middleware
// -----------------------------------------------------------------------
Route::middleware('clinic.auth:admin')->group(function () {
    Route::get('/dashboard/admin', fn () => view('clinic.admin'))->name('clinic.admin');
});

Route::middleware('clinic.auth:nurse,nurse_head')->group(function () {
    Route::get('/dashboard/nurse', fn () => view('clinic.nurse'))->name('clinic.nurse');
});

// Medical certificate preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/medcert', function () {
    $data = [
        'date' => request('date', ''),
        'surname' => request('surname', ''),
        'firstname' => request('firstname', ''),
        'middlename' => request('middlename', ''),
        'age' => request('age', ''),
        'sex' => request('sex', ''),
        'civil' => request('civil', ''),
        'address' => request('address', ''),
        'bp' => request('bp', ''),
        'temp' => request('temp', ''),
        'assessment' => request('assessment', ''),
        'sig_name' => request('sig_name', ''),
        'sig_license' => request('sig_license', ''),
        'sig_title' => 'School Physician',
    ];

    return view('clinic.forms.medcert', $data);
})->name('clinic.forms.medcert');

// Dental certificate preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/dentalcert', function () {
    $data = [
        'date' => request('date', ''),
        'surname' => request('surname', ''),
        'firstname' => request('firstname', ''),
        'middlename' => request('middlename', ''),
        'full_name' => request('full_name', ''),
        'age' => request('age', ''),
        'sex' => request('sex', ''),
        'civil' => request('civil', ''),
        'address' => request('address', ''),
        'diagnosed' => request('diagnosed', ''),
        'treated' => request('treated', ''),
        'proc_oral_prophylaxis' => request('proc_oral_prophylaxis', ''),
        'proc_temp_filling' => request('proc_temp_filling', ''),
        'tooth_temp' => request('tooth_temp', ''),
        'proc_perm_restoration' => request('proc_perm_restoration', ''),
        'tooth_perm' => request('tooth_perm', ''),
        'proc_extraction' => request('proc_extraction', ''),
        'tooth_extract' => request('tooth_extract', ''),
        'proc_orthodontic' => request('proc_orthodontic', ''),
        'proc_subgingival' => request('proc_subgingival', ''),
        'proc_impaction' => request('proc_impaction', ''),
        'proc_root_canal' => request('proc_root_canal', ''),
        'proc_prosthodontic' => request('proc_prosthodontic', ''),
        'proc_other' => request('proc_other', ''),
        'proc_other_text' => request('proc_other_text', ''),
        'purpose' => request('purpose', ''),
        'sig_name' => request('sig_name', ''),
        'sig_license' => request('sig_license', ''),
        'sig_title' => 'School Dentist',
        'sig_ptr' => request('sig_ptr', ''),
    ];

    return view('clinic.forms.dentalcert', $data);
})->name('clinic.forms.dentalcert');

// Prescription form preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/prescription', function () {
    return view('clinic.forms.prescription');
})->name('clinic.forms.prescription');

Route::post('/clinic/forms/prescription', function (Request $request) {
    return view('clinic.forms.prescription', array_merge(
        ['role' => 'physician', 'autoPrint' => true],
        $request->only(['full_name', 'age', 'date', 'notes', 'physician_name', 'physician_lic']),
    ));
});

// Prescription form preview — dentist variant (used by iframe in nurse dashboard)
Route::get('/clinic/forms/prescription/dentist', function () {
    return view('clinic.forms.prescription', ['role' => 'dentist']);
})->name('clinic.forms.prescription.dentist');

Route::post('/clinic/forms/prescription/dentist', function (Request $request) {
    return view('clinic.forms.prescription', array_merge(
        ['role' => 'dentist', 'autoPrint' => true],
        $request->only(['full_name', 'age', 'date', 'notes', 'physician_name', 'physician_lic']),
    ));
});

// Referral form preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/referral', function () {
    return view('clinic.forms.referral');
})->name('clinic.forms.referral');

Route::post('/clinic/forms/referral', function (Request $request) {
    $formData = $request->validate([
        'name' => ['nullable', 'string', 'max:255'],
        'age' => ['nullable', 'string', 'max:10'],
        'address' => ['nullable', 'string', 'max:500'],
        'urgency' => ['nullable', 'string', 'in:routine,urgent,emergency'],
        'chief_complaint' => ['nullable', 'string', 'max:2000'],
        'vital_signs' => ['nullable', 'string', 'max:500'],
        'remarks' => ['nullable', 'string', 'max:2000'],
        'physician_name' => ['nullable', 'string', 'max:255'],
        'physician_lic' => ['nullable', 'string', 'max:100'],
    ]);

    return view('clinic.forms.referral', [
        'role' => 'nurse',
        'autoPrint' => true,
        'formData' => $formData,
    ]);
});

// Referral form preview — dentist variant (used by iframe in nurse dashboard)
Route::get('/clinic/forms/referral/dentist', function () {
    return view('clinic.forms.referral', ['role' => 'nurse']);
})->name('clinic.forms.referral.dentist');

Route::post('/clinic/forms/referral/dentist', function (Request $request) {
    $formData = $request->validate([
        'name' => ['nullable', 'string', 'max:255'],
        'age' => ['nullable', 'string', 'max:10'],
        'address' => ['nullable', 'string', 'max:500'],
        'urgency' => ['nullable', 'string', 'in:routine,urgent,emergency'],
        'chief_complaint' => ['nullable', 'string', 'max:2000'],
        'vital_signs' => ['nullable', 'string', 'max:500'],
        'remarks' => ['nullable', 'string', 'max:2000'],
        'physician_name' => ['nullable', 'string', 'max:255'],
        'physician_lic' => ['nullable', 'string', 'max:100'],
    ]);

    return view('clinic.forms.referral', [
        'role' => 'nurse',
        'autoPrint' => true,
        'formData' => $formData,
    ]);
});

// X-Ray form preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/xray', function (Request $request) {
    $formData = $request->validate([
        'name' => ['nullable', 'string', 'max:255'],
        'course' => ['nullable', 'string', 'max:255'],
        'staff' => ['nullable', 'string', 'max:255'],
        'checked' => ['nullable', 'boolean'],
    ]);

    return view('clinic.forms.xray', $formData);
})->name('clinic.forms.xray');

// Lactating Mother accommodation form preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/lactating', function () {
    return view('clinic.forms.lactating');
})->name('clinic.forms.lactating');

// Breastfeeding Room User Agreement form preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/breastfeeding', function () {
    return view('clinic.forms.breastfeeding');
})->name('clinic.forms.breastfeeding');

// Laboratory request form preview (used by iframe in nurse dashboard)
Route::get('/clinic/forms/lab', function () {
    $role = request('role', 'physician');

    $data = [
        'name' => request('name', ''),
        'age' => request('age', ''),
        'sex' => request('sex', ''),
        'dob' => request('dob', ''),
        'address' => request('address', ''),
        'date' => request('date', ''),
        'phys' => request('phys', ''),
        'role' => $role,
        'license' => request('lic', $role === 'dentist' ? 'DMD-0006828' : '85606'),
        'tests' => array_values(array_filter((array) request()->query('tests', []), 'is_string')),
    ];

    return view('clinic.forms.lab', $data);
})->name('clinic.forms.lab');

Route::middleware('clinic.auth:nurse_head')->group(function () {
    Route::get('/dashboard/nurse-head', fn () => view('clinic.nurse_head'))->name('clinic.nurse_head');

    // Read-only "View full record" print pages for the nurse-head
    // dashboard detail modal. The dispatch table maps the {type} segment
    // to the matching model + Blade view.
    Route::get('/clinic/records/print/{type}/{id}', function (string $type, int $id) {
        $map = [
            'medical-records' => [
                'model' => ClinicVisit::class,
                'view' => 'clinic.records.print-medical',
                'eager' => ['student', 'nurse', 'physician', 'campus'],
            ],
            'vital' => [
                'model' => ClinicVisit::class,
                'view' => 'clinic.records.print-vital',
                'eager' => ['student', 'campus'],
            ],
            'referral' => [
                'model' => Referral::class,
                'view' => 'clinic.records.print-referral',
                'eager' => ['student', 'fromCampus', 'toCampus', 'referredBy'],
            ],
        ];

        if (! isset($map[$type])) {
            abort(404, 'Unknown record type.');
        }

        $cfg = $map[$type];
        $record = $cfg['model']::with($cfg['eager'])->find($id);
        if (! $record) {
            abort(404);
        }

        return view($cfg['view'], ['record' => $record]);
    })->whereNumber('id');

});

Route::middleware(['clinic.auth:nurse,nurse_head', 'clinic.identity:nurse,nurse_head'])
    ->get('/clinic/referrals/{id}/form', function (Request $request, int $id) {
        $identity = $request->attributes->get('clinic_identity');
        $query = Referral::query()
            ->with(['student.latestClinicVisit', 'student.latestHealthRecord', 'fromCampus', 'toCampus', 'referredBy']);

        if ($identity['role'] === 'nurse') {
            $campusId = $identity['campus_id'] ?? null;
            abort_unless($campusId !== null, 403, 'Your account is not assigned to a campus.');
            $query->forCampus($campusId);
        }

        $record = $query->findOrFail($id);
        $student = $record->student;
        $studentName = $student
            ? trim(implode(' ', array_filter([
                $student->first_name,
                $student->middle_name,
                $student->last_name,
            ])))
            : '';
        $clinicVisit = $student?->latestClinicVisit;
        $healthRecord = $student?->latestHealthRecord;
        $referredBy = $record->referredBy;
        $bloodPressure = $clinicVisit?->bp ?: $healthRecord?->bp;
        $temperature = $clinicVisit?->temp ?: $healthRecord?->temp;
        $pulse = $clinicVisit?->pulse ?: $healthRecord?->pr;
        $vitalSigns = $record->vital_signs ?: collect([
            $bloodPressure ? 'BP: '.$bloodPressure : null,
            $temperature ? 'Temp: '.$temperature.' °C' : null,
            $pulse ? 'PR: '.$pulse.' bpm' : null,
            $clinicVisit?->rr ? 'RR: '.$clinicVisit->rr.'/min' : null,
            $clinicVisit?->o2_sat ? 'SpO2: '.$clinicVisit->o2_sat.'%' : null,
        ])->filter()->implode(' · ');
        $remarks = collect([
            $record->management_given ? 'Management given: '.$record->management_given : null,
            $record->diagnosis ? 'Diagnosis: '.$record->diagnosis : null,
            $record->history ? 'History: '.$record->history : null,
        ])->filter()->implode("\n");
        $referredByName = $record->signature_name ?: ($referredBy
            ? trim(implode(' ', array_filter([$referredBy->first_name, $referredBy->last_name])))
            : '');

        return view('clinic.forms.referral', [
            'role' => $record->route_to === 'dentist' ? 'dentist' : 'physician',
            'viewOnly' => true,
            'embedded' => $request->boolean('embedded'),
            'formData' => [
                'name' => $studentName,
                'age' => $student?->age ?? $healthRecord?->age,
                'address' => $student?->address,
                'urgency' => $record->urgency,
                'chief_complaint' => $record->reason,
                'vital_signs' => $vitalSigns,
                'remarks' => $remarks,
                'physician_name' => $referredByName,
                'physician_lic' => $record->signature_license_no ?: $referredBy?->license_no,
            ],
        ]);
    })->whereNumber('id');



Route::middleware('clinic.auth:student')->group(function () {
    Route::get('/dashboard/student',    fn () => view('clinic.student'))->name('clinic.student');
    Route::get('/dashboard/faculty',    fn () => view('clinic.student'))->name('clinic.faculty');
    Route::get('/dashboard/staff',      fn () => view('clinic.student'))->name('clinic.staff');
    Route::get('/dashboard/instructor', fn () => view('clinic.student'))->name('clinic.instructor');
});

// Generic /dashboard → redirect to login (role determined client-side)
Route::get('/dashboard', fn () => redirect('/login'))->name('dashboard');

// -----------------------------------------------------------------------
// Clinic logout — clears the role cookie and session storage hint
// -----------------------------------------------------------------------
Route::post('/clinic/logout', function () {
    return redirect('/login')
        ->withCookie(cookie()->forget('clinic_role'))
        ->withCookie(cookie()->forget('clinic_identity'));
})->name('clinic.logout');

// -----------------------------------------------------------------------
// Laravel starter-kit settings routes
// -----------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';

// The clinic auth page. Registered AFTER auth.php so it wins the GET /login slot.
// Named 'login' satisfies Laravel's auth guard redirect + Livewire Volt auth views.
Route::get('/login', fn () => view('clinic.auth'))->name('login');
