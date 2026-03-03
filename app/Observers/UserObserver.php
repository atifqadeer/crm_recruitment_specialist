<?php

namespace Horsefly\Observers;

use Horsefly\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    /**
     * Handle the user "created" event.
     *
     * @param  \Horsefly\User  $user
     * @return void
     */
    public function created(User $user)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $user->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($user),
            "message" => "User {$user->name} has been created successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the user "updated" event.
     *
     * @param \Horsefly\User $user
     * @param null $message
     * @param null $col
     * @return void
     */
    public function updated(User $user, $message = null, $col = null)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $columns = $user->getDirty();
        $user['changes_made'] = $col == null ? $columns : $col;

        $user->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($user),
            "message" => $message == null ? "User {$user->name} has been updated successfully at {$time} on {$date}" : $message . " at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }
}
