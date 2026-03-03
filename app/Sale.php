<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Horsefly\Events\Models\Sale as SaleEvent;

class Sale extends Model
{
//    public function getDateFormat()
//    {
//        return 'Y-m-d H:i:s.u';
//    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = 
    [
        'sale_uid', 
        'user_id', 
        'head_office', 
        'head_office_unit', 
        'job_category', 
        'job_title', 
        'job_title_prof', 
        'postcode', 
        'job_type', 
        'timing', 
        'salary', 
        'experience', 
        'qualification', 
        'benefits', 
        'posted_date', 
        'lat', 
        'lng', 
        'sale_added_date', 
        'sale_added_time', 
        'status', 
        'is_on_hold',
        'send_cv_limit',
        'is_re_open',
        'job_description'
    ];

	/**
     *  The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
//        'created' => SaleEvent::class,
        'updated' => SaleEvent::class
    ];
	
    /**
     * Get the office that owns the sale.
     */
    public function office()
    {
        return $this->belongsTo(Office::class, 'head_office');
    }

    /**
     * Get the unit that owns the sale.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'head_office_unit');
    }

    /**
     * Get all audits associated with the sale.
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /**
     * Get all module_notes associated with the sale.
     */
    public function module_notes()
    {
        return $this->morphMany(ModuleNote::class, 'module_noteable');
    }

	/**
     * Get latest module_note associated with the sale.
     */
    public function latest_module_note()
    {
        return $this->morphMany(ModuleNote::class, 'module_noteable')->where('status', 'active')->latest()->first();
    }

    /**
     * Get latest sale_note associated with the sale.
     */
    public function latest_sale_note()
    {
        return $this->hasMany(Sales_notes::class)->where('status', 'active')->latest()->first();
    }
	
	/**
     * Get the active sent cv count for the sale.
     */
    public function active_cvs()
    {
        return $this->hasMany(Cv_note::class, 'sale_id', 'id')->where('status', '=', 'active');
    }
	
	/**
     * Get all updated by audits associated with the sale.
     */
    public function updated_by_audits()
    {
        return $this->morphMany(Audit::class, 'auditable')->with('user')
            ->where('message', 'like', '%has been updated%');
    }

    /**
     * Get all updated by audits associated with the sale.
     */
    public function created_by_audit()
    {
        return $this->morphOne(Audit::class, 'auditable')->with('user')
            ->where('message', 'like', '%has been created%');
    }
	
	public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
	
	public function sale_documents(){
        return $this->hasMany(SaleDocument::class, 'sale_id', 'id');
    }
	
}
