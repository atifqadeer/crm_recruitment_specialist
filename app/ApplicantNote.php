<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class ApplicantNote extends Model
{
    protected $table = 'applicant_notes';
	protected $fillable = [
			'details',
			'applicant_id',
			'moved_tab_to',
			'added_date',
			'added_time',
			'user_id',
			'status'
		];
}
