<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class SignatureSubscriber implements EventSubscriberInterface
{
    private $signinSecret;

    public function __construct(string $signinSecret)
    {
        $this->signinSecret = $signinSecret;
    }

    public function onRequestEvent(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->attributes->get('slack', false)) {
            return;
        }

        $body = (string) $request->getContent();
        $signature = $request->headers->get('X-Slack-Signature');
        $timestamp = $request->headers->get('X-Slack-Request-Timestamp');

        $payload = "v0:$timestamp:$body";

        $signatureTmp = 'v0='.hash_hmac('sha256', $payload, $this->signinSecret);

        if ($signatureTmp !== $signature) {
            $event->setResponse(new Response('You are not slack', 401));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => ['onRequestEvent', 32 - 1],
        ];
    }
}
