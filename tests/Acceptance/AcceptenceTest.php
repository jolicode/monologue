<?php

namespace App\Tests\Acceptance;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AcceptenceTest extends WebTestCase
{
    private const int DAY = 86400;

    private Connection $conn;

    protected function setUp(): void
    {
        $this->conn = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->conn->executeStatement('DELETE FROM event');
        $this->conn->executeStatement('DELETE FROM debt');
        $this->conn->executeStatement('DELETE FROM amnesty');

        self::ensureKernelShutdown();
    }

    public function testWithAck(): void
    {
        $client = self::createClient();
        // We want to be able to mock some response
        $client->disableReboot();
        /** @var MockHttpClient */
        $mockHttpClient = self::getContainer()->get('http_client.transport');

        // #1 message from user A

        $client->request('POST', '/message', content: $this->getFixtures('001_message_user_A'));

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame(1, $this->conn->fetchOne('SELECT COUNT(*) FROM event'));
        self::assertSame(0, $this->conn->fetchOne('SELECT COUNT(*) FROM debt'));

        // #2 message from user A

        $client->request('POST', '/message', content: $this->getFixtures('002_message_user_A'));

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame(2, $this->conn->fetchOne('SELECT COUNT(*) FROM event'));
        self::assertSame(0, $this->conn->fetchOne('SELECT COUNT(*) FROM debt'));

        // #1 message from user B

        $mockHttpClient->setResponseFactory(function (string $method, string $url, array $options = []): MockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://slack.com/api/chat.postMessage', $url);
            $this->assertSame('{"channel":"MY_CHANNEL_ID","text":"Fraud detected.","blocks":[{"type":"context","elements":[{"type":"mrkdwn","text":"Thanks \u003C@UMYK1MQ3E\u003E for the next breakfast! Reason: message posted."}]}]}', $options['body']);

            return new MockResponse('{"ok": true}');
        });
        $client->request('POST', '/message', content: $this->getFixtures('003_message_user_B'));

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame(3, $this->conn->fetchOne('SELECT COUNT(*) FROM event'));
        self::assertSame(1, $this->conn->fetchOne('SELECT COUNT(*) FROM debt'));

        // /monologue from user A

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'U0FLDV6UW',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(4, $responseDecoded['blocks']);
        self::assertSame('*Pending debts*', $responseDecoded['blocks'][0]['text']['text']);
        self::assertSame(\sprintf('<@UMYK1MQ3E>, 1 debt: %s.', $this->daysAgo('1668615833')), $responseDecoded['blocks'][2]['text']['text']);
        self::assertSame('actions', $responseDecoded['blocks'][3]['type']);
        self::assertSame(['Mark as paid'], $this->getButtonLabels($responseDecoded['blocks'][3]));

        // /monologue from user B

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(3, $responseDecoded['blocks']);
        self::assertSame('*Pending debts*', $responseDecoded['blocks'][0]['text']['text']);
        self::assertSame('section', $responseDecoded['blocks'][2]['type']);

        // user A marks debt for user B as paid

        $debId = $this->conn->fetchOne('select id from debt');
        $mockHttpClient->setResponseFactory([
            function (string $method, string $url, array $options = []): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://hooks.slack.com/actions/T0FLD8LEM/4375527081910/uouQFvOW3NHFQjJmyAv53ZZF', $url);
                $this->assertSame('{"channel":"MY_CHANNEL_ID","text":"Pending debts.","blocks":[{"type":"section","text":{"type":"mrkdwn","text":"*There are no more debts*"}}]}', $options['body']);

                return new MockResponse('{"ok": true}');
            },
            function (string $method, string $url, array $options = []): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://slack.com/api/chat.postMessage', $url);
                $this->assertSame('{"channel":"MY_CHANNEL_ID","text":"\u003C@UMYK1MQ3E\u003E\u0027s debt was marked as paid by \u003C@U0FLDV6UW\u003E !","blocks":[]}', $options['body']);

                return new MockResponse('{"ok": true}');
            },
        ]);

        $client->request('POST', '/action', parameters: [
            'payload' => str_replace('DEBT_ID', $debId, $this->getFixtures('004_mark_as_paid')),
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());

        // /monologue from user B

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $responseDecoded['blocks']);
        self::assertSame('*There are no more debts*', $responseDecoded['blocks'][0]['text']['text']);
    }

    public function testWithAckFirstAndAckAll(): void
    {
        $client = self::createClient();
        // We want to be able to mock some response
        $client->disableReboot();
        /** @var MockHttpClient */
        $mockHttpClient = self::getContainer()->get('http_client.transport');

        // The "Fraud detected" notifications are not the subject of this test
        $mockHttpClient->setResponseFactory(static fn (): MockResponse => new MockResponse('{"ok": true}'));

        // Day 1, 2 and 3: user A speaks first, then user B: 3 debts for user B

        foreach ([0, 1, 2] as $day) {
            $client->request('POST', '/message', content: $this->getFixtures('001_message_user_A', shift: $day * self::DAY));
            self::assertSame(200, $client->getResponse()->getStatusCode());

            $client->request('POST', '/message', content: $this->getFixtures('003_message_user_B', shift: $day * self::DAY));
            self::assertSame(200, $client->getResponse()->getStatusCode());
        }

        // Day 4: user B speaks first, then user A: 1 debt for user A

        $client->request('POST', '/message', content: $this->getFixtures('003_message_user_B', shift: 3 * self::DAY));
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('POST', '/message', content: $this->getFixtures('001_message_user_A', shift: 3 * self::DAY + 3600));
        self::assertSame(200, $client->getResponse()->getStatusCode());

        self::assertSame(3, $this->conn->fetchOne("SELECT COUNT(*) FROM debt WHERE author = 'UMYK1MQ3E'"));
        self::assertSame(1, $this->conn->fetchOne("SELECT COUNT(*) FROM debt WHERE author = 'U0FLDV6UW'"));
        $oldestDebtId = $this->conn->fetchOne("SELECT id FROM debt WHERE author = 'UMYK1MQ3E' ORDER BY created_at ASC LIMIT 1");

        // /monologue from user A: debts are grouped by user, oldest first

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'U0FLDV6UW',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $blocks = json_decode($client->getResponse()->getContent(), true)['blocks'];
        self::assertCount(5, $blocks);
        self::assertSame('*Pending debts*', $blocks[0]['text']['text']);
        self::assertSame('divider', $blocks[1]['type']);
        self::assertSame(\sprintf(
            '<@UMYK1MQ3E>, 3 debts: %s, %s, %s.',
            $this->daysAgo('1668615833'),
            $this->daysAgo('1668615833', 1 * self::DAY),
            $this->daysAgo('1668615833', 2 * self::DAY),
        ), $blocks[2]['text']['text']);
        self::assertSame('actions', $blocks[3]['type']);
        self::assertSame(['Mark first as paid', 'Mark all as paid'], $this->getButtonLabels($blocks[3]));
        self::assertSame('ack-' . $oldestDebtId, $blocks[3]['elements'][0]['value']);
        self::assertSame('ack-all-UMYK1MQ3E', $blocks[3]['elements'][1]['value']);
        // No button for its own debt
        self::assertSame(\sprintf('<@U0FLDV6UW>, 1 debt: %s.', $this->daysAgo('1668614312', 3 * self::DAY + 3600)), $blocks[4]['text']['text']);

        // /monologue from user B

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $blocks = json_decode($client->getResponse()->getContent(), true)['blocks'];
        self::assertCount(5, $blocks);
        // No button for its own debts
        self::assertStringStartsWith('<@UMYK1MQ3E>, 3 debts: ', $blocks[2]['text']['text']);
        self::assertStringStartsWith('<@U0FLDV6UW>, 1 debt: ', $blocks[3]['text']['text']);
        self::assertSame('actions', $blocks[4]['type']);
        self::assertSame(['Mark as paid'], $this->getButtonLabels($blocks[4]));

        // user B can not mark its own debts as paid

        $client->request('POST', '/action', parameters: [
            'payload' => str_replace('U0FLDV6UW', 'UMYK1MQ3E', $this->getFixtures('005_mark_all_as_paid')),
        ]);

        self::assertSame(400, $client->getResponse()->getStatusCode());
        self::assertSame('You can not ACK your own debts.', $client->getResponse()->getContent());
        self::assertSame(0, $this->conn->fetchOne('SELECT COUNT(*) FROM debt WHERE paid'));

        // user A marks the first (oldest) debt of user B as paid

        $mockHttpClient->setResponseFactory([
            function (string $method, string $url, array $options = []): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://hooks.slack.com/actions/T0FLD8LEM/4375527081910/uouQFvOW3NHFQjJmyAv53ZZF', $url);
                $blocks = json_decode($options['body'], true)['blocks'];
                $this->assertCount(5, $blocks);
                $this->assertSame(\sprintf(
                    '<@UMYK1MQ3E>, 2 debts: %s, %s.',
                    $this->daysAgo('1668615833', 1 * self::DAY),
                    $this->daysAgo('1668615833', 2 * self::DAY),
                ), $blocks[2]['text']['text']);
                $this->assertSame(['Mark first as paid', 'Mark all as paid'], $this->getButtonLabels($blocks[3]));

                return new MockResponse('{"ok": true}');
            },
            function (string $method, string $url, array $options = []): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://slack.com/api/chat.postMessage', $url);
                $this->assertSame("<@UMYK1MQ3E>'s debt was marked as paid by <@U0FLDV6UW> !", json_decode($options['body'], true)['text']);

                return new MockResponse('{"ok": true}');
            },
        ]);

        $client->request('POST', '/action', parameters: [
            'payload' => str_replace('DEBT_ID', $oldestDebtId, $this->getFixtures('004_mark_as_paid')),
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame([$oldestDebtId], $this->conn->fetchFirstColumn('SELECT id FROM debt WHERE paid'));

        // user A marks all the remaining debts of user B as paid

        $mockHttpClient->setResponseFactory([
            function (string $method, string $url, array $options = []): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://hooks.slack.com/actions/T0FLD8LEM/4375527081910/uouQFvOW3NHFQjJmyAv53ZZF', $url);
                $blocks = json_decode($options['body'], true)['blocks'];
                $this->assertCount(3, $blocks);
                $this->assertStringStartsWith('<@U0FLDV6UW>, 1 debt: ', $blocks[2]['text']['text']);

                return new MockResponse('{"ok": true}');
            },
            function (string $method, string $url, array $options = []): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://slack.com/api/chat.postMessage', $url);
                $this->assertSame("<@UMYK1MQ3E>'s 2 debts were marked as paid by <@U0FLDV6UW> !", json_decode($options['body'], true)['text']);

                return new MockResponse('{"ok": true}');
            },
        ]);

        $client->request('POST', '/action', parameters: [
            'payload' => $this->getFixtures('005_mark_all_as_paid'),
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame(0, $this->conn->fetchOne("SELECT COUNT(*) FROM debt WHERE NOT paid AND author = 'UMYK1MQ3E'"));
        self::assertSame(1, $this->conn->fetchOne("SELECT COUNT(*) FROM debt WHERE NOT paid AND author = 'U0FLDV6UW'"));

        // There is nothing left to mark as paid for user B

        $client->request('POST', '/action', parameters: [
            'payload' => $this->getFixtures('005_mark_all_as_paid'),
        ]);

        self::assertSame(400, $client->getResponse()->getStatusCode());
        self::assertSame('There are no pending debts for this user.', $client->getResponse()->getContent());
    }

    public function testWithAmnesty(): void
    {
        $client = self::createClient();
        // We want to be able to mock some response
        $client->disableReboot();
        /** @var MockHttpClient */
        $mockHttpClient = self::getContainer()->get('http_client.transport');

        // #1 message from user A

        $client->request('POST', '/message', content: $this->getFixtures('001_message_user_A'));

        self::assertSame(200, $client->getResponse()->getStatusCode());

        // #1 message from user B

        $mockHttpClient->setResponseFactory(new MockResponse('{"ok": true}'));
        $client->request('POST', '/message', content: $this->getFixtures('003_message_user_B'));

        self::assertSame(200, $client->getResponse()->getStatusCode());

        // /amnesty from user A

        $client->request('POST', '/command/amnesty', parameters: [
            'user_id' => 'U0FLDV6UW',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('{"text":"More people need to ask for amnesty to complete it! (1\/2)"}', $client->getResponse()->getContent());

        // /amnesty from user B

        $mockHttpClient->setResponseFactory(function (string $method, string $url, array $options = []): MockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://slack.com/api/chat.postMessage', $url);
            $this->assertSame('{"channel":"MY_CHANNEL_ID","text":"The amnesty has been redeemed. All debts have been wiped. \ud83c\udf86","blocks":[]}', $options['body']);

            return new MockResponse('{"ok": true}');
        });

        $client->request('POST', '/command/amnesty', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('', $client->getResponse()->getContent());

        // /monologue from user B

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $responseDecoded['blocks']);
        self::assertSame('*There are no more debts*', $responseDecoded['blocks'][0]['text']['text']);
    }

    /**
     * @param int $shift Shifts the timestamps of a message fixture, in seconds
     */
    private function getFixtures(string $name, int $shift = 0): string
    {
        $content = file_get_contents(__DIR__ . '/fixtures/' . $name . '.json');

        if (!$shift) {
            return $content;
        }

        $payload = json_decode($content, true);
        [$seconds, $microseconds] = explode('.', $payload['event']['ts']);
        $ts = ($seconds + $shift) . '.' . $microseconds;
        $payload['event']['ts'] = $ts;
        $payload['event']['event_ts'] = $ts;
        $payload['event_time'] = $seconds + $shift;

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function daysAgo(string $ts, int $shift = 0): string
    {
        $days = new \DateTimeImmutable()->diff(new \DateTimeImmutable('@' . ($ts + $shift)))->format('%a');

        return \sprintf('%s days ago', $days);
    }

    private function getButtonLabels(array $actionsBlock): array
    {
        return array_map(static fn (array $element) => $element['text']['text'], $actionsBlock['elements']);
    }
}
