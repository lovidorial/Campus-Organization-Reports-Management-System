<?php

namespace Database\Seeders;

use App\Models\OrganizationClassification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationClassificationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $items = [
            'CSC / Aparri Campus Student Council' => ['Major', 'Campus-wide'],
            'GS-SC' => ['Minor', 'Graduate School'],
            'CBI' => ['Specialized', 'College-based / specialized'],
            'iTOUCH' => ['Specialized', 'CICS'],
            'IKSIND-AKAD' => ['Specialized', 'CTE'],
            'SARIHADA' => ['Specialized', 'CICS'],
            'SIKLAHON' => ['Specialized', 'CBEA'],
            'STA' => ['Specialized', 'CTE'],
            'SARIGAWAN' => ['Specialized', 'CTE'],
            'The Aquarius' => ['Specialized', 'Campus Publication'],
            'The Academia' => ['Specialized', 'Education / academic publication'],
            'The Banquet' => ['Specialized', 'CHM'],
            'The Caliber' => ['Specialized', 'CCJE'],
            'The Conduit' => ['Specialized', 'CICS'],
            'The Ledger' => ['Specialized', 'CBEA'],
            'The Mentor' => ['Specialized', 'CTE'],
            'The Waterworld' => ['Specialized', 'CFAS'],
            'CHM-SC' => ['Minor', 'College of Hospitality Management'],
            'CIT-SC' => ['Minor', 'College of Industrial Technology'],
            'CTE-SC' => ['Minor', 'College of Teacher Education'],
            'CCJE-SC' => ['Minor', 'College of Criminal Justice Education'],
            'CICS-SC' => ['Minor', 'College of Information & Computing Sciences'],
            'CFAS-SC' => ['Minor', 'College of Fisheries & Aquatic Sciences'],
            'JEWEL' => ['Specialized', 'CTE'],
            'MARAHUYO' => ['Specialized', 'CCJE'],
            'ASDHS' => ['Specialized', 'College-based'],
            'FAS-SCO' => ['Specialized', 'Fisheries / aquatic sciences'],
            'FAME' => ['Specialized', 'College-based'],
            'HMS' => ['Specialized', 'Hospitality Management'],
            'SAB-CHM' => ['Specialized', 'CHM'],
            'PAssFEEd / PASSFEED' => ['Specialized', 'CTE'],
        ];

        foreach ($items as $orgName => [$classification, $collegeArea]) {
            OrganizationClassification::updateOrCreate(
                ['org_name' => $orgName],
                ['aliases' => $orgName === 'CCJE-SC' ? ['CCTE-SC'] : null,
                 'classification' => $classification,
                 'college_area' => $collegeArea]
            );
        }
    }
}
