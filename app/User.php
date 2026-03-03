<?php

namespace Horsefly;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Horsefly\Events\Models\User as UserEvent;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','is_active','is_admin'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     *  The event map for the model.
     *
     * @var array
     */
//    protected $dispatchesEvents = [
//        'created' => UserEvent::class,
//        'updated' => UserEvent::class,
//    ];

    /**
     * Get all audits associated with the user.
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
	public function messages()
    {
        return $this->hasMany(Applicant_message::class);
    }
}
