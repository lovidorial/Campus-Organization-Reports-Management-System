<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDeadline extends Model
{
    public const TYPE_GPOA = 'gpoa';
    public const TYPE_ACTIVITY_REQUEST = 'activity_request';
    public const TYPE_SUMMARY_REPORT = 'summary_report';
    public const TYPE_ACTIVITY_REPORT = 'activity_report';

    public const TYPES = [
        self::TYPE_GPOA,
        self::TYPE_ACTIVITY_REQUEST,
        self::TYPE_SUMMARY_REPORT,
        self::TYPE_ACTIVITY_REPORT,
    ];

    protected $fillable = [
        'document_type',
        'term',
        'school_year',
        'deadline_date',
    ];

    protected $casts = [
        'deadline_date' => 'date',
    ];

    public static function forPeriod(
        string $documentType,
        string $term,
        string $schoolYear
    ): ?self {
        return static::where('document_type', $documentType)
            ->where('term', $term)
            ->where('school_year', $schoolYear)
            ->first();
    }
}
