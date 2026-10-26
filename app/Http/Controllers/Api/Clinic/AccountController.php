<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Staff;
use App\Models\Student;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function show(Request $request): JsonResponse
    {
        $account = $this->account($request);

        return response()->json([
            'success' => true,
            'profile' => $this->profilePayload($account),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $isStudent = $account instanceof Student;
        $fields = $isStudent
            ? ['first_name', 'last_name', 'middle_name', 'contact', 'address', 'department', 'course_year', 'section', 'sex', 'civil_status']
            : ['first_name', 'last_name', 'middle_name', 'contact', 'specialization'];

        $rules = array_fill_keys($fields, ['sometimes', 'nullable', 'string']);
        $rules['first_name'] = ['sometimes', 'required', 'string', 'max:80'];
        $rules['last_name'] = ['sometimes', 'required', 'string', 'max:80'];
        $rules['middle_name'] = ['sometimes', 'nullable', 'string', 'max:80'];
        $rules['contact'] = ['sometimes', 'nullable', 'string', 'max:30'];

        foreach ($fields as $field) {
            if (! in_array($field, ['first_name', 'last_name', 'middle_name', 'contact'], true)) {
                $rules[$field] = ['sometimes', 'nullable', 'string', 'max:'.match ($field) {
                    'address' => 255,
                    'department', 'specialization' => 150,
                    'course_year' => 100,
                    'section' => 30,
                    'sex', 'civil_status' => 20,
                    default => 255,
                }];
            }
        }

        $rules['current_password'] = ['required_with:new_password', 'string'];
        $rules['new_password'] = ['sometimes', 'required', 'string', 'min:6', 'max:128', 'confirmed'];
        $data = $request->validate($rules);

        $updates = array_map(
            static fn (mixed $value): mixed => $value === null ? '' : $value,
            array_intersect_key($data, array_flip($fields)),
        );

        if (isset($data['new_password'])) {
            if (! Hash::check($data['current_password'], $account->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 422);
            }

            $updates['password'] = $data['new_password'];
        }

        if ($updates === []) {
            return response()->json(['error' => 'Nothing to update'], 422);
        }

        $account->update($updates);
        $this->audit->log(
            $request,
            $account->id,
            $isStudent ? 'student' : 'staff',
            'update_profile',
            $isStudent ? 'students' : 'staff',
            $account->id,
        );

        return response()->json([
            'success' => true,
            'profile' => $this->profilePayload($account->fresh()),
        ]);
    }

    public function updatePicture(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $data = $request->validate([
            'picture' => ['required', 'string', 'max:2800000'],
        ]);

        if (! preg_match('/^data:image\/(jpeg|png|webp);base64,([A-Za-z0-9+\/=]+)$/', $data['picture'], $matches)) {
            return response()->json(['error' => 'Choose a valid JPG, PNG, or WEBP image.'], 422);
        }

        $contents = base64_decode($matches[2], true);
        $image = $contents === false ? false : getimagesizefromstring($contents);
        $mimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (
            $contents === false
            || strlen($contents) > 2 * 1024 * 1024
            || $image === false
            || ! in_array($image['mime'], $mimeTypes, true)
            || $image['mime'] !== 'image/'.$matches[1] && ! ($matches[1] === 'jpg' && $image['mime'] === 'image/jpeg')
        ) {
            return response()->json(['error' => 'Choose a valid JPG, PNG, or WEBP image up to 2 MB.'], 422);
        }

        $account->update(['profile_picture' => $data['picture']]);
        $this->audit->log(
            $request,
            $account->id,
            $account instanceof Student ? 'student' : 'staff',
            'update_profile_picture',
            $account instanceof Student ? 'students' : 'staff',
            $account->id,
        );

        return response()->json(['success' => true, 'picture' => $account->profile_picture]);
    }

    private function account(Request $request): Student|Staff
    {
        $identity = $request->attributes->get('clinic_identity');

        return $identity['role'] === 'student'
            ? Student::query()->findOrFail($identity['id'])
            : Staff::query()->findOrFail($identity['id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(Student|Staff $account): array
    {
        $isStudent = $account instanceof Student;

        return [
            'id' => $account->id,
            'account_id' => $isStudent ? $account->student_id : $account->employee_id,
            'email' => $account->email,
            'campus_name' => Campus::query()->whereKey($account->campus_id)->value('name') ?? '',
            'first_name' => $account->first_name,
            'last_name' => $account->last_name,
            'middle_name' => $account->middle_name,
            'contact' => $account->contact,
            'profile_picture' => $account->profile_picture,
            'address' => $isStudent ? $account->address : null,
            'department' => $isStudent ? $account->department : null,
            'course_year' => $isStudent ? $account->course_year : null,
            'section' => $isStudent ? $account->section : null,
            'sex' => $isStudent ? $account->sex : null,
            'civil_status' => $isStudent ? $account->civil_status : null,
            'specialization' => $isStudent ? null : $account->specialization,
        ];
    }
}
