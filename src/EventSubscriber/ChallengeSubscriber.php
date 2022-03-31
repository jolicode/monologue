<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ChallengeSubscriber implements EventSubscriberInterface
{
    public function onRequestEvent(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $payload = json_decode((string) $event->getRequest()->getContent(), true);
        if (!$payload) {
            return;
        }

        if ('url_verification' === $payload['type']) {
            $event->setResponse(new Response($payload['challenge']));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onRequestEvent', 1024],
        ];
    }
}
