<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
	protected $table = 'sent_emails';
    protected $fillable = [
        'action_name',
        'sent_from',
        'sent_to',
        'cc_emails',
        'subject',
        'template',
        'email_added_date',
        'email_added_time',
        'status',
		'applicant_id',
        'sale_id'
    ];
}
