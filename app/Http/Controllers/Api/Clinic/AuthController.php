<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Staff;
use App\Models\Student;
use App\Services\AuditService;
use App\Support\CampusCourseCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private AuditService $audit) {}

    // -----------------------------------------------------------------------
    // POST /api/clinic/auth/login
    // -----------------------------------------------------------------------
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $email = strtolower($data['email']);
        $student = Student::with('campus')
            ->where('email', $email)
            ->where('is_active', true)
            ->first();
        $staff = Staff::with('campus')
            ->where('email', $email)
            ->where('is_active', true)
            ->first();
        $studentMatches = $this->passwordMatches($data['password'], $student?->password);
        $staffMatches = $this->passwordMatches($data['password'], $staff?->password);

        if ($studentMatches === $staffMatches) {
            $this->audit->log($request, null, 'unknown', 'login_failed', null, null, null, ['email' => $email]);

            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        if ($studentMatches && $student) {
            $user = $student;
            $role = 'student';
        } elseif ($staffMatches && $staff) {
            $user = $staff;
            $role = $staff->role;
        } else {
            $this->audit->log($request, null, 'unknown', 'login_failed', null, null, null, ['email' => $email]);

            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        $validStaffRoles = ['staff', 'nurse', 'nurse_head', 'physician', 'dentist', 'admin'];
        if ($user instanceof Staff && ! in_array($role, $validStaffRoles, true)) {
            $this->audit->log($request, null, 'unknown', 'login_failed', null, null, null, ['email' => $email]);

            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        if (password_needs_rehash(
            $user->password,
            PASSWORD_BCRYPT,
            ['cost' => (int) config('hashing.bcrypt.rounds', 12)],
        )) {
            DB::table($user->getTable())
                ->where($user->getKeyName(), $user->getKey())
                ->update([
                    'password' => Hash::make($data['password']),
                    'updated_at' => now(),
                ]);
        }

        $this->audit->log($request, $user->id, $role, 'login', $role === 'student' ? 'students' : 'staff');

        $secureCookie = (bool) (config('session.secure') ?? $request->isSecure());
        $identityCookie = cookie(
            'clinic_identity',
            Crypt::encryptString(json_encode(['id' => $user->id, 'role' => $role])),
            480,
            '/',
            null,
            $secureCookie,
            true,
            false,
            'lax',
        );

        return response()->json([
            'success' => true,
            'user' => $this->buildSessionPayload($user, $role),
        ])->withCookie($identityCookie);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/auth/register
    // -----------------------------------------------------------------------
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:30'],
            'last_name' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:students,email'],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'terms_accepted' => ['accepted'],
            'department' => ['required', 'string', 'max:120'],
            'campus_id' => ['required', 'integer', 'exists:campuses,id,is_active,1'],
            'course_year' => ['required', 'string', 'max:100'],
            'section' => ['sometimes', 'nullable', 'string', 'max:30'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sex' => ['sometimes', 'nullable', 'in:Male,Female'],
            'civil_status' => ['sometimes', 'nullable', 'in:Single,Married'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $campus = Campus::query()->active()->findOrFail($data['campus_id']);
        if (! CampusCourseCatalog::offersCourse($campus->code, $data['department'])) {
            return response()->json([
                'message' => 'The selected course is not offered at the selected campus.',
                'errors' => [
                    'department' => ['Select a course offered at the chosen campus.'],
                ],
            ], 422);
        }

        $studentNo = strtoupper(trim($data['student_id']));

        if (Student::where('student_id', $studentNo)->exists()) {
            return response()->json(['error' => 'Email or Student ID is already registered'], 409);
        }

        $student = Student::create([
            'student_id' => $studentNo,
            'last_name' => strip_tags(trim($data['last_name'])),
            'first_name' => strip_tags(trim($data['first_name'])),
            'middle_name' => strip_tags(trim($data['middle_name'] ?? '')),
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'department' => strip_tags(trim($data['department'])),
            'campus_id' => (int) $data['campus_id'],
            'course_year' => strip_tags(trim($data['course_year'] ?? '')),
            'section' => strip_tags(trim($data['section'] ?? '')),
            'contact' => strip_tags(trim($data['contact'] ?? '')),
            'address' => strip_tags(trim($data['address'] ?? '')),
            'sex' => $data['sex'] ?? '',
            'civil_status' => $data['civil_status'] ?? '',
            'age' => $data['age'] ?? null,
            'role' => 'student',
            'patient_type' => 'student',
        ]);

        $this->audit->log($request, $student->id, 'student', 'register', 'students', $student->id, null, ['student_id' => $studentNo, 'email' => $student->email]);

        return response()->json([
            'success' => true,
            'student_db_id' => $student->id,
            'message' => 'Registration successful! You can now sign in.',
        ], 201);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/auth/logout
    // -----------------------------------------------------------------------
    public function logout(Request $request): JsonResponse
    {
        $this->audit->log(
            $request,
            $request->query('user_id'),
            $request->query('role', 'unknown'),
            'logout',
        );

        return response()->json(['success' => true, 'message' => 'Logged out'])
            ->withCookie(cookie()->forget('clinic_identity'));
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/auth/campuses
    // -----------------------------------------------------------------------
    public function campuses(): JsonResponse
    {
        $campuses = Campus::active()
            ->orderBy('id')
            ->get(['id', 'name', 'code']);
        $campuses = $campuses->map(fn (Campus $campus) => [
            'id' => $campus->id,
            'name' => $campus->name,
            'code' => $campus->code,
            'courses' => CampusCourseCatalog::courses($campus->code),
        ]);

        return response()->json(['success' => true, 'campuses' => $campuses]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function passwordMatches(string $password, ?string $hashedPassword): bool
    {
        $dummyHash = '$2y$12$gwqOe3o/wxlOPSNtao8nw.NR7iilAXlPWTHWd2FeDzFkcApEPmuUa';
        $hash = $hashedPassword ?: $dummyHash;
        $algorithm = password_get_info($hash)['algoName'] ?? 'unknown';

        if (! in_array($algorithm, ['bcrypt', 'argon2i', 'argon2id'], true)) {
            password_verify($password, $dummyHash);

            return false;
        }

        return password_verify($password, $hash);
    }

    /**
     * Build the session payload returned on successful login.
     */
    private function buildSessionPayload(Student|Staff $user, string $role): array
    {
        $isStudent = $user instanceof Student;

        return [
            'id' => $user->id,
            'identifier' => $isStudent ? $user->student_id : $user->employee_id,
            'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
            'firstName' => $user->first_name ?? '',
            'lastName' => $user->last_name ?? '',
            'middleName' => $user->middle_name ?? '',
            'email' => $user->email ?? '',
            'dept' => $isStudent ? ($user->department ?? '') : '',
            'section' => $isStudent ? ($user->section ?? '') : '',
            'contact' => $user->contact ?? '',
            'address' => $isStudent ? ($user->address ?? '') : '',
            'sex' => $isStudent ? ($user->sex ?? '') : '',
            'civilStatus' => $isStudent ? ($user->civil_status ?? '') : '',
            'age' => $isStudent ? $user->age : null,
            'bloodType' => $isStudent ? ($user->blood_type ?? null) : null,
            'patientType' => $isStudent ? ($user->patient_type ?? 'student') : null,
            'profilePicture' => $user->profile_picture ?? null,
            'role' => $user->role ?? $role,
            'licenseNo' => $isStudent ? null : ($user->license_no ?? null),
            'specialization' => $isStudent ? null : ($user->specialization ?? null),
            'campusId' => $user->campus_id,
            'campusName' => $user->campus?->name ?? 'All Campuses',
        ];
    }
}
