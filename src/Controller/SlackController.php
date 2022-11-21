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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('', defaults: ['slack' => true])]
class SlackController extends AbstractController
{
    public function __construct(
        private readonly BigBrowser $bigBrowser,
        private readonly DebtAcker $debtAcker,
        private readonly DebtListBlockBuilder $debtListBlockBuilder,
        private readonly DebtListPoster $debtListPoster,
        private readonly EntityManagerInterface $em,
        private readonly DebtAckPoster $debAckPoster,
        private readonly Government $government,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[Route('/message', methods: 'POST')]
    public function messages(Request $request): Response
    {
        $payload = $request->toArray();

        $this->bigBrowser->control($payload);

        return new Response('OK');
    }

    #[Route('/action', methods: 'POST')]
    public function action(Request $request): Response
    {
        try {
            $payload = json_decode((string) $request->request->get('payload'), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new Response('No payload', 400);
        }

        if (!preg_match('{^ack\-(.*)$}', $payload['actions'][0]['value'] ?? '', $m)) {
            return new Response('Payload not supported.', 400);
        }

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
        $this->debtListPoster->postDebtList($payload['user']['id'], $payload['response_url']);
        $this->debAckPoster->postDebtAck($debt, $payload['user']['id']);

        return new Response('ok');
    }

    #[Route('/command/list', methods: 'POST')]
    public function commandList(Request $request): Response
    {
        return $this->json([
            'text' => 'Pending debts', // Not used but mandatory
            'blocks' => $this->debtListBlockBuilder->buildBlocks($request->request->getAlnum('user_id')),
        ]);
    }

    #[Route('/command/amnesty', methods: 'POST')]
    public function commandAmnesty(Request $request): Response
    {
        try {
            $this->government->redeem((string) $request->request->get('user_id'));
        } catch (\DomainException $e) {
            return $this->json([
                'text' => $e->getMessage(),
            ]);
        } finally {
            $this->em->flush();
        }

        return new Response();
    }
}
