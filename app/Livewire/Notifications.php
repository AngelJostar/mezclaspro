<?php

namespace App\Livewire;

use Livewire\Component;

class Notifications extends Component
{
    public $count = 3;

    public function getListeners(){
        return [
            'echo-notification:App.Models.User.'.auth()->id().',MessageSent' => 'render',
        ];
    }
    public function getNotificationsProperty(){
        return auth()->user()->notifications->take($this->count);
    }

    public function readNotification($id){
        auth()->user()->notifications->find($id)->markAsRead();
    }

    public function resetNotification(){
        auth()->user()->notification = 0;
        auth()->user()->save();
    }
    public function incrementCount(){
        $this->count +=3;
    }
    public function render()
    {
        $notifications = auth()->user()->notifications;
        return view('livewire.notifications', compact('notifications'));
    }
}
