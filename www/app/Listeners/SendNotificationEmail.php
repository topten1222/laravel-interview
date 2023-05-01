<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\NotificationMail;
use App\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(OrderCreated $event)
    {
        $users = User::where(['group' => 'Admin'])->get();
        foreach ($users as $user) {
            Mail::to($user->email)->send(new NotificationMail($event->order));
        }
    }
}
