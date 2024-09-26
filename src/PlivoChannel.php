<?php

namespace NotificationChannels\Plivo;

use Illuminate\Notifications\Notification;
use NotificationChannels\Plivo\Exceptions\CouldNotSendNotification;

class PlivoChannel
{
    /**
     * @var \NotificationChannels\Plivo\Plivo;
     */
    protected $plivo;

    /**
     * The phone number notifications should be sent from.
     *
     * @var string
     */
    protected $from;

    /**
     * The webhook url that plivo will send status change notifications to.
     *
     * @var string
     */
    protected $webhook;

    /**
     * @return  void
     */
    public function __construct(Plivo $plivo)
    {
        $this->plivo = $plivo;
        $this->from = $this->plivo->from();
        $this->webhook = $this->plivo->webhook();
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @throws \NotificationChannels\Plivo\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        if (!$to = $notifiable->routeNotificationFor('plivo')) {
            return;
        }

        $message = $notification->toPlivo($notifiable);

        if (is_string($message)) {
            $message = new PlivoMessage($message);
        }

        $response = $this->plivo->messages->create(
            $message->from ?: $this->from,
            [$to],
            trim($message->content),
            ['url' => $message->webhook ?: $this->webhook]
        );
        
        $response->message = $message;

        if ($response->statusCode !== 202) {
            throw CouldNotSendNotification::serviceRespondedWithAnError($response);
        }

        return $response;
    }
}
