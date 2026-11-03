<?php

return [
    'max_size_kb' => 20480,
    'types' => [
        'communication_letter' => [
            'max_size_kb' => 20480,
            'mimes' => ['application/pdf'],
            'minimum_text_length' => 20,
            'require_organization_name' => true,
            'require_activity_match' => true,
            'require_date' => true,
            'require_signature_indicator' => true,
            'required_keywords' => [],
            'require_non_blank' => true,
        ],
        'narrative_report' => [
            'max_size_kb' => 20480,
            'mimes' => ['application/pdf'],
            'minimum_text_length' => 20,
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
    'ocr' => [
        'enabled' => env('DOCUMENT_VALIDATION_OCR_ENABLED', true),
        'tesseract_path' => env('DOCUMENT_VALIDATION_TESSERACT_PATH'),
        'language' => env('DOCUMENT_VALIDATION_OCR_LANGUAGE', 'eng'),
        'dpi' => (int) env('DOCUMENT_VALIDATION_OCR_DPI', 200),
        'maximum_pages' => (int) env('DOCUMENT_VALIDATION_OCR_MAXIMUM_PAGES', 100),
    ],
];