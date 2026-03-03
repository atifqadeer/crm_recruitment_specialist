<?php

namespace Horsefly\Exports;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Horsefly\Applicant;

class ApplicantsExportIncorrectPostcode implements FromCollection, WithHeadings
{
    public function __construct() {
        // Optional constructor logic
    }

    public function collection()
    {
        return Applicant::select(
            'applicant_name',
            'applicant_phone',
            'applicant_homePhone',
            'applicant_job_title',
            'applicant_postcode',
            'applicant_source',
            'lat',
            'lng'
        )
			->where("lat", '0.000000')
			->where('status','active')
			->where('is_blocked','0')
			->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Landline',
            'Job Title',
            'Postcode',
            'Source',
            'Latitude',
            'Longitude',
        ];
    }
}
