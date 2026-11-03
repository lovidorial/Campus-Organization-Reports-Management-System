<?php

return [
    'max_size_kb' => 20480,
    'types' => [
        'communication_letter' => [
            'max_size_kb' => 20480,
            'mimes' => ['application/pdf'],
            'require_organization_name' => true,
            'require_activity_match' => true,
            'require_date' => true,
            'require_signature_indicator' => true,
            'required_keywords' => [],
            'require_non_blank' => true,
        ],
        'gpoa' => [
            'max_size_kb' => 20480,
            'mimes' => ['application/pdf'],
            'require_organization_name' => true,
            'require_activity_match' => false,
            'require_date' => true,
            'require_signature_indicator' => true,
            'required_keywords' => [],
            'require_non_blank' => true,
        ],
        'workflow_submissions' => [
            'default' => [
                'max_size_kb' => 20480,
                'mimes' => ['application/pdf', 'image/jpeg', 'image/png', 'image/tiff'],
                'require_organization_name' => true,
                'require_activity_match' => false,
                'require_date' => true,
                'require_signature_indicator' => true,
                'required_keywords' => [],
                'require_non_blank' => true,
            ],
            'communication_letter' => [
                'require_activity_match' => true,
            ],
        ],
    ],
];