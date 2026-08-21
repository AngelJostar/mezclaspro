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
        return auth()->user()->notifications()->latest()->limit($this->count)->get();
    }

    public function getUnreadCountProperty(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function readNotification($id){
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
    }

    public function resetNotification(){
        auth()->user()->unreadNotifications->markAsRead();
    }
    public function incrementCount(){
        $this->count +=3;
    }
    public function render()
    {
        $notifications = auth()->user()->notifications()->latest()->get();
        return view('livewire.notifications', compact('notifications'));
    }
}
