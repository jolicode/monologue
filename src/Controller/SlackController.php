<?php

namespace App\Controller;

use App\ControlTower\BigBrowser;
use App\ControlTower\DebtAcker;
use App\Slack\DebtListBlockBuilder;
use App\Slack\DebtListPoster;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SlackController extends AbstractController
{
    private $bigBrowser;
    private $debtAcker;
    private $debtListBlockBuider;
    private $debtListPoster;
    private $em;

    public function __construct(BigBrowser $bigBrowser, DebtAcker $debtAcker, DebtListBlockBuilder $debtListBlockBuider, DebtListPoster $debtListPoster, ObjectManager $em)
    {
        $this->bigBrowser = $bigBrowser;
        $this->debtAcker = $debtAcker;
        $this->debtListBlockBuider = $debtListBlockBuider;
        $this->debtListPoster = $debtListPoster;
        $this->em = $em;
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

        if ('debt_list' === $payload['actions'][0]['value']) {
            $this->debtListPoster->postDebtList();
        } elseif (preg_match('{^ack\-(.*)$}', $payload['actions'][0]['value'] ?? '', $m)) {
            $debtId = $m[1];
            try {
                $this->debtAcker->ackDebt($payload, $debtId);
            } catch (\DomainException $e) {
                return new Response($e->getMessage(), 400);
            }
            $this->em->flush();
            $this->debtListPoster->postDebtList($payload['response_url']);
        } else {
            return new Response('Payload not supported.', 400);
        }

        return new Response('OK');
    }

    /** @Route("/command/list", methods="POST") */
    public function commandList(Request $request)
    {
        return $this->json([
            'text' => 'Pending debts', // Not used but mandatory
            'blocks' => $this->debtListBlockBuider->buildBlocks(),
        ]);
    }
}
