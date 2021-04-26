<?php

namespace App\Controller;

use App\ControlTower\BigBrowser;
use App\ControlTower\DebtAcker;
use App\Slack\DebtAckPoster;
use App\Slack\DebtListBlockBuilder;
use App\Slack\DebtListPoster;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("", defaults={"slack"=true}) */
class SlackController extends AbstractController
{
    private $bigBrowser;
    private $debtAcker;
    private $debtListBlockBuider;
    private $debtListPoster;
    private $em;
    private $debAckPoster;
    private $logger;

    public function __construct(BigBrowser $bigBrowser, DebtAcker $debtAcker, DebtListBlockBuilder $debtListBlockBuider, DebtListPoster $debtListPoster, EntityManagerInterface $em, DebtAckPoster $debAckPoster, ?LoggerInterface $logger = null)
    {
        $this->bigBrowser = $bigBrowser;
        $this->debtAcker = $debtAcker;
        $this->debtListBlockBuider = $debtListBlockBuider;
        $this->debtListPoster = $debtListPoster;
        $this->em = $em;
        $this->debAckPoster = $debAckPoster;
        $this->logger = $logger ?? new NullLogger();
    }

    /** @Route("/message", methods="POST") */
    public function messages(Request $request)
    {
        $this->bigBrowser->control(json_decode((string) $request->getContent(), true));

        return new Response('OK');
    }

    /** @Route("/action", methods="POST") */
    public function action(Request $request)
    {
        $payload = json_decode($request->request->get('payload'), true);
        if (!$payload) {
            return new Response('No payload', 400);
        }

        if (preg_match('{^ack\-(.*)$}', $payload['actions'][0]['value'] ?? '', $m)) {
            $debtId = $m[1];
            try {
                $debt = $this->debtAcker->ackDebt($payload, $debtId);
            } catch (\DomainException $e) {
                $this->logger->warning('Something went wrong.', [
                    'exception' => $e,
                ]);

                return new Response($e->getMessage(), 400);
            }
            $this->em->flush();
            $this->debtListPoster->postDebtList($payload['response_url']);
            $this->debAckPoster->postDebtAck($debt, $payload['user']['id']);
        } else {
            return new Response('Payload not supported.', 400);
        }

        return new Response('OK');
    }

    /** @Route("/command/list", methods="POST") */
    public function commandList(Request $request)
    {
        return $this->json([
            'text' => 'Dettes en attente', // Not used but mandatory
            'blocks' => $this->debtListBlockBuider->buildBlocks(),
        ]);
    }
}
