<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{

    public $users;
    public $selectedUser;
    public $newMessage;
    public $messages;
    public $loginId;


    public function mount()
    {
        $this->users = User::query()->whereNot("id", Auth::id())->latest()->get();
        $this->selectedUser = $this->users->first();
        $this->loadMessages();
        $this->loginId = Auth::id();
    }

    public function loadMessages(){
        $this->messages = ChatMessage::query()->where(function($q){
            $q->where("sender_id", Auth::id())
            ->where("receiver_id", $this->selectedUser->id);
        })
        ->orWhere(function($q){
            $q->where("sender_id", $this->selectedUser->id)
            ->where("receiver_id", Auth::id());
        })->get();
    }

    public function selectUser($userId)
    {
        $this->selectedUser = User::find($userId);
        $this->loadMessages();
    }

    public function sendMessage(){
        if(empty($this->newMessage)){
            return;
        }

        $message = ChatMessage::create([
            "sender_id" => Auth::id(),
            "receiver_id" => $this->selectedUser->id,
            "message" => $this->newMessage,
        ]);

        $this->messages->push($message);

        $this->newMessage = '';

        broadcast(new MessageSent($message));

    }

    public function updatedNewMessage($value){
        $this->dispatch("userTyping", userID: $this->loginId, userName: Auth::user()->name, selectedUserID: $this->selectedUser->id);
    }

    public function getListeners(){
        return [
            "echo-private:chat.{$this->loginId},MessageSent" => "newChatMessageNotification",
        ];
    }

    public function newChatMessageNotification($message){
        if($message['sender_id'] == $this->selectedUser->id){
            $messageObj = ChatMessage::find($message['id']);

            $this->messages->push($messageObj);
        }
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
