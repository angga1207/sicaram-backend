<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Kutia\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class GlobalNotification extends Notification
{
    use Queueable;
    public $type, $modelId, $byUserId, $toUserId = [], $uri, $title, $message, $datas = [];

    public function __construct($type, $modelId, $byUserId, $toUserId, $uri = null, $title = null, $message = null, $datas = [])
    {
        $this->type = $type;
        $this->modelId = $modelId;
        $this->byUserId = $byUserId;
        $this->uri = $uri;
        $this->title = $title;
        $this->message = $message;
        $this->toUserId = $toUserId;
        $this->datas = $datas;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'firebase'];
        // return ['firebase'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    // to firebase
    public function toFirebase($notifiable)
    {
        $fcmTokens = User::whereIn('id', $this->toUserId)->pluck('fcm_token')->toArray();
        $return = (new FirebaseMessage)
            ->withTitle($this->title)
            ->withBody($this->message)
            ->withAdditionalData([
                'type' => $this->type,
                'modelId' => $this->modelId,
                'byUserId' => $this->byUserId,
                'toUserId' => $this->toUserId,
                'uri' => $this->uri,
                'title' => $this->title,
                'message' => $this->message,
                'datas' => $this->datas,
            ])
            ->withPriority('high')
            ->asNotification($fcmTokens);

        return $return;
    }

    public function toArray($notifiable)
    {
        $return = [
            'type' => $this->type,
            'modelId' => $this->modelId,
            'byUserId' => $this->byUserId,
            'toUserId' => $this->toUserId,
            'uri' => $this->uri,
            'title' => $this->title,
            'message' => $this->message,
            'datas' => $this->datas,
        ];
        return $return;
    }
}
