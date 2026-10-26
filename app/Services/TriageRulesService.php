<?php

namespace App\Services;

class TriageRulesService
{
    /**
     * @return array{priority: string, reason: string|null}
     */
    public function classify(array $features): array
    {
        $text = strtolower(implode(' ', array_filter([
            $features['complaint'] ?? null,
            $features['diagnosis'] ?? null,
            $features['symptoms'] ?? null,
        ])));
        $careType = $features['care_type'] ?? 'medical';

        $emergencyTerms = [
            'difficulty breathing',
            'shortness of breath',
            'unconscious',
            'seizure',
            'severe trauma',
            'uncontrolled bleeding',
        ];

        if ($careType === 'medical') {
            $emergencyTerms[] = 'chest pain';
        }

        if ($careType === 'dental') {
            $emergencyTerms = array_merge($emergencyTerms, [
                'airway swelling',
                'facial swelling',
                'facial trauma',
                'dental abscess',
            ]);
        }

        foreach ($emergencyTerms as $term) {
            if (str_contains($text, $term)) {
                return ['priority' => 'Priority 1', 'reason' => $term];
            }
        }

        foreach ($this->urgentTerms($careType) as $term) {
            if (str_contains($text, $term)) {
                return ['priority' => 'Priority 2', 'reason' => $term];
            }
        }

        if (is_numeric($features['severity'] ?? null) && (int) $features['severity'] >= 8) {
            return ['priority' => 'Priority 2', 'reason' => 'reported severity 8/10 or higher'];
        }

        return ['priority' => 'Priority 3', 'reason' => null];
    }

    /**
     * @return list<string>
     */
    private function urgentTerms(string $careType): array
    {
        $terms = ['fever', 'vomiting', 'severe pain', 'injury', 'trauma'];

        if ($careType === 'dental') {
            $terms = array_merge($terms, ['toothache', 'tooth pain', 'gum swelling', 'dental pain']);
        } else {
            $terms = array_merge($terms, ['abdominal pain', 'headache']);
        }

        return $terms;
    }
}
