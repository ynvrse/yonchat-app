<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $senderId;
    public $receiverId;
    public $status;

    public function __construct($senderId, $receiverId, $status)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->status = $status;
    }
    public function broadcastOn(): array
    {

        return [new Channel('sending')];
    }
}
