<?php

namespace Horsefly\Exports;

use Carbon\Carbon;
use Horsefly\Office;
use Horsefly\Sale;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClosedSalesEmailExport implements FromCollection ,WithHeadings
{
    protected $job_category;
    protected $duration;
    /**
     * @return \Illuminate\Support\Collection
     */
    function __construct($job_category, $duration) {
        $this->job_category = $job_category;
        $this->duration = $duration;
    }

    public function collection()
    {
        $query = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
           ->select('offices.office_name', 'units.unit_name', 'sales.postcode',
                'units.contact_email','units.contact_name','units.contact_phone_number', 'sales.job_category')
            ->where(['sales.status' => 'disable', 'sales.is_on_hold' => '0']);

        if ($this->job_category == 'specialist'){
            $query->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereIn('sales.job_category', ['nurse', 'nonnurse'])
                        ->whereIn('sales.job_title', ['nurse specialist', 'nonnurse specialist']);
                })->orWhereNotIn('sales.job_category', ['nurse', 'nonnurse']);
            });
        }elseif ($this->job_category == 'nurse'){
            $query->where('sales.job_category', 'nurse')
                ->whereNotIn('sales.job_title', ['nurse specialist']);
        }elseif($this->job_category == 'nonnurse'){
            $query->where('sales.job_category', 'nonnurse')
                ->whereNotIn('sales.job_title', ['nonnurse specialist']);
        }

        if($this->duration == 'last_21_days'){
            $query->where('sales.updated_at', '>=', Carbon::now()->subDays(21));
        }elseif($this->duration == 'last_3_months'){    
            $endDate = Carbon::now()->subDays(21);  // Date 21 days ago
            $startDate = $endDate->copy()->subMonths(3); // Subtract 3 months from the end date
           
            $query->whereBetween('sales.updated_at', [$startDate, $endDate]);
        }elseif($this->duration == 'last_6_months'){    
            $endDate = Carbon::now()->subMonths(3)->subDays(21);  // Date 3 months and 21 days ago
            $startDate = $endDate->copy()->subMonths(6); // Subtract 6 months from the end date

            $query->whereBetween('sales.updated_at', [$startDate, $endDate]);
        }elseif($this->duration == 'last_12_months'){
            $endDate = Carbon::now()->subMonths(9)->subDays(23);  // Date 9 months and 21 days ago
            $startDate = $endDate->copy()->subYear();  // Subtract 12 months from $endDate

            $query->whereBetween('sales.updated_at', [$startDate, $endDate]);
        }elseif($this->duration == '12_months_old'){
            $endDate = Carbon::now()->subMonths(21)->subDays(23);

            $query->whereDate('sales.updated_at', '<', $endDate);
        }

        $result = $query->groupBy('units.unit_name')
            ->orderBy('sales.updated_at', 'desc')
            ->get();

        return $result;
    }
    public function headings(): array
    {
        return [
            'Head Office',
            'Unit',
            'PostCode',
            'Email',
            'Contact Name',
            'Phone',
            'Job Title'
        ];
    }
}
