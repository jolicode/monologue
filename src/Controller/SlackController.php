<?php

namespace App\Controller;

use App\ControlTower\BigBrowser;
use App\ControlTower\DebtAcker;
use App\ControlTower\Government;
use App\Slack\DebtAckPoster;
use App\Slack\DebtListBlockBuilder;
use App\Slack\DebtListPoster;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('', defaults: ['slack' => true])]
class SlackController extends AbstractController
{
    public function __construct(
        private BigBrowser $bigBrowser,
        private DebtAcker $debtAcker,
        private DebtListBlockBuilder $debtListBlockBuilder,
        private DebtListPoster $debtListPoster,
        private EntityManagerInterface $em,
        private DebtAckPoster $debAckPoster,
        private Government $government,
        private ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    #[Route('/message', methods: 'POST')]
    public function messages(Request $request): Response
    {
        $this->bigBrowser->control(json_decode((string) $request->getContent(), true));

        return new Response('OK');
    }

    #[Route('/action', methods: 'POST')]
    public function action(Request $request): Response
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

    #[Route('/command/list', methods: 'POST')]
    public function commandList(): JsonResponse
    {
        return $this->json([
            'text' => 'Dettes en attente', // Not used but mandatory
            'blocks' => $this->debtListBlockBuilder->buildBlocks(),
        ]);
    }

    #[Route('/command/amnesty', methods: 'POST')]
    public function commandAmnesty(Request $request): JsonResponse
    {
        try {
            $this->government->redeem($request->request->get('user_id'));
        } catch (\DomainException $e) {
            return $this->json([
                'text' => $e->getMessage(),
            ]);
        } finally {
            $this->em->flush();
        }

        return $this->json([
            'text' => 'Amnistie générale 🎆',
        ]);
    }
}
