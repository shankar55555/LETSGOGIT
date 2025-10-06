<?php

namespace Modules\AlertAndNotification\Events;

use App\Constants\CommonConst;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AlertAndNotification\Models\NotificationLog;

class NotificationMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user_id;
    public $type;

    /**
     * Create a new event instance.
     */
    public function __construct($message, $user_id, $type)
    {
        $this->message = $message;
        $this->user_id = $user_id;
        $this->type = $type;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        if ($this->type == CommonConst::BELL_NOTIFICATION) {
            return new Channel('notification-bell-channel-' . $this->user_id);
        } else if ($this->type == CommonConst::EMAIL) {
            return new Channel('notification-email-channel-' . $this->user_id);
        } else if ($this->type == CommonConst::WHATSAPP) {
            return new Channel('notification-whats-app-channel-' . $this->user_id);
        } else if ($this->type == CommonConst::SMS) {
            return new Channel('notification-sms-channel-' . $this->user_id);
        } else  if ($this->type == CommonConst::APP) {
            return new Channel('notification-app-channel-' . $this->user_id);
        }
    }

    /**
     * Broadcast event name
     */
    public function broadcastAs()
    {
        return 'new-notification';
    }
}
