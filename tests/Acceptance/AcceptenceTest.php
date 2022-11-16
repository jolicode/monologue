<?php

namespace App\Tests\Acceptance;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AcceptenceTest extends WebTestCase
{
    private Connection $conn;

    protected function setUp(): void
    {
        $this->conn = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->conn->executeStatement('DELETE FROM event');
        $this->conn->executeStatement('DELETE FROM debt');
        $this->conn->executeStatement('DELETE FROM amnesty');

        self::ensureKernelShutdown();
    }

    public function testWithAck()
    {
        $client = self::createClient();
        // We want to be able to mock some response
        $client->disableReboot();
        /** @var MockHttpClient */
        $mockHttpClient = self::getContainer()->get('http_client');

        // #1 message from user A

        $client->request('POST', '/message', content: $this->getFixtures('001_message_user_A'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(1, $this->conn->fetchOne('SELECT COUNT(*) FROM event'));
        $this->assertSame(0, $this->conn->fetchOne('SELECT COUNT(*) FROM debt'));

        // #2 message from user A

        $client->request('POST', '/message', content: $this->getFixtures('002_message_user_A'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(2, $this->conn->fetchOne('SELECT COUNT(*) FROM event'));
        $this->assertSame(0, $this->conn->fetchOne('SELECT COUNT(*) FROM debt'));

        // #1 message from user B

        $mockHttpClient->setResponseFactory(function (string $method, string $url, array $options = []): MockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://slack.com/api/chat.postMessage', $url);
            $this->assertSame('{"channel":"MY_CHANNEL_ID","text":"Fraud detected.","blocks":[{"type":"context","elements":[{"type":"mrkdwn","text":"Thanks \u003C@UMYK1MQ3E\u003E for the next breakfast! Reason: message posted."}]}]}', $options['body']);

            return new MockResponse('{"ok": true}');
        });
        $client->request('POST', '/message', content: $this->getFixtures('003_message_user_B'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(3, $this->conn->fetchOne('SELECT COUNT(*) FROM event'));
        $this->assertSame(1, $this->conn->fetchOne('SELECT COUNT(*) FROM debt'));

        // /monologue from user A

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'U0FLDV6UW',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(3, $responseDecoded['blocks']);
        $this->assertSame('*Pending debts*', $responseDecoded['blocks'][0]['text']['text']);
        $this->assertArrayHasKey('accessory', $responseDecoded['blocks'][2]);

        // /monologue from user B

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(3, $responseDecoded['blocks']);
        $this->assertSame('*Pending debts*', $responseDecoded['blocks'][0]['text']['text']);
        $this->assertFalse(\array_key_exists('accessory', $responseDecoded['blocks'][2]));

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

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // /monologue from user B

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseDecoded['blocks']);
        $this->assertSame('*There are no more debts*', $responseDecoded['blocks'][0]['text']['text']);
    }

    public function testWithAmnesty()
    {
        $client = self::createClient();
        // We want to be able to mock some response
        $client->disableReboot();
        /** @var MockHttpClient */
        $mockHttpClient = self::getContainer()->get('http_client');

        // #1 message from user A

        $client->request('POST', '/message', content: $this->getFixtures('001_message_user_A'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // #1 message from user B

        $mockHttpClient->setResponseFactory(new MockResponse('{"ok": true}'));
        $client->request('POST', '/message', content: $this->getFixtures('003_message_user_B'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // /amnesty from user A

        $client->request('POST', '/command/amnesty', parameters: [
            'user_id' => 'U0FLDV6UW',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('{"text":"More people need to ask for amnesty to complete it! (1\/2)"}', $client->getResponse()->getContent());

        // /amnesty from user B

        $mockHttpClient->setResponseFactory(function (string $method, string $url, array $options = []): MockResponse {
            $this->assertSame('POST', $method);
            $this->assertSame('https://slack.com/api/chat.postMessage', $url);
            $this->assertSame('{"channel":"MY_CHANNEL_ID","text":"The amnesty has been redeemed. All debts have been acknowledged. \ud83c\udf86","blocks":[]}', $options['body']);

            return new MockResponse('{"ok": true}');
        });

        $client->request('POST', '/command/amnesty', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('', $client->getResponse()->getContent());

        // /monologue from user B

        $client->request('POST', '/command/list', parameters: [
            'user_id' => 'UMYK1MQ3E',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseDecoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseDecoded['blocks']);
        $this->assertSame('*There are no more debts*', $responseDecoded['blocks'][0]['text']['text']);
    }

    private function getFixtures(string $name): string
    {
        return file_get_contents(__DIR__ . '/fixtures/' . $name . '.json');
    }
}
