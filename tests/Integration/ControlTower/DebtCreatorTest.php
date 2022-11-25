<?php

namespace App\Tests\Integration\ControlTower;

use App\ControlTower\DebtCreator;
use App\Entity\Debt;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DebtCreatorTest extends KernelTestCase
{
    private DebtCreator $bigBrowser;

    protected function setUp(): void
    {
        $this->bigBrowser = self::getContainer()->get(DebtCreator::class);

        self::getContainer()
            ->get('doctrine.dbal.default_connection')
            ->executeStatement('DELETE FROM event')
        ;
    }

    public function testControlFirstMessage()
    {
        // Author: foobar

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'client_msg_id' => '86fe7235-8c10-4401-8f44-688d46039db2',
                'type' => 'message',
                'text' => 'FINAL',
                'user' => 'foobar',
                'ts' => '1567006789.003100',
                'team' => 'T0FLD8LEM',
                'channel' => 'MY_CHANNEL_ID',
                'event_ts' => '1567006789.003100',
                'channel_type' => 'channel',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMVBZR18E',
            'event_time' => '1567006784',
            'authed_users' => [
                'foobar',
            ],
        ];

        $this->assertNull($this->bigBrowser->createDebtIfNeeded($payload));

        // Author: foobar, a bit later

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'client_msg_id' => '86fe7235-8c10-4401-8f44-688d46039db2',
                'type' => 'message',
                'text' => 'FINAL',
                'user' => 'foobar',
                'ts' => '1567006790.003100',
                'team' => 'T0FLD8LEM',
                'channel' => 'MY_CHANNEL_ID',
                'event_ts' => '1567006790.003100',
                'channel_type' => 'channel',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMVBZR18E',
            'event_time' => '1567006784',
            'authed_users' => [
                'foobar',
            ],
        ];

        $this->assertNull($this->bigBrowser->createDebtIfNeeded($payload));

        // Author: baz

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'client_msg_id' => '86fe7235-8c10-4401-8f44-688d46039db2',
                'type' => 'message',
                'text' => 'FINAL',
                'user' => 'baz',
                'ts' => '1567006790.003100',
                'team' => 'T0FLD8LEM',
                'channel' => 'MY_CHANNEL_ID',
                'event_ts' => '1567006790.003100',
                'channel_type' => 'channel',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMVBZR18E',
            'event_time' => '1567006784',
            'authed_users' => [
                'baz',
            ],
        ];

        $this->assertInstanceOf(Debt::class, $this->bigBrowser->createDebtIfNeeded($payload));

        // Author: baz, a bit later

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'client_msg_id' => '86fe7235-8c10-4401-8f44-688d46039db2',
                'type' => 'message',
                'text' => 'FINAL',
                'user' => 'baz',
                'ts' => '1567006791.003100',
                'team' => 'T0FLD8LEM',
                'channel' => 'MY_CHANNEL_ID',
                'event_ts' => '1567006791.003100',
                'channel_type' => 'channel',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMVBZR18E',
            'event_time' => '1567006784',
            'authed_users' => [
                'baz',
            ],
        ];

        $this->assertNull($this->bigBrowser->createDebtIfNeeded($payload));

        // Author: foobar, a bit later, again

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'client_msg_id' => '86fe7235-8c10-4401-8f44-688d46039db2',
                'type' => 'message',
                'text' => 'FINAL',
                'user' => 'foobar',
                'ts' => '1567006791.603100',
                'team' => 'T0FLD8LEM',
                'channel' => 'MY_CHANNEL_ID',
                'event_ts' => '1567006791.603100',
                'channel_type' => 'channel',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMVBZR18E',
            'event_time' => '1567006784',
            'authed_users' => [
                'foobar',
            ],
        ];

        $this->assertNull($this->bigBrowser->createDebtIfNeeded($payload));

        // Author: foo, a bit later

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'client_msg_id' => '86fe7235-8c10-4401-8f44-688d46039db2',
                'type' => 'message',
                'text' => 'FINAL',
                'user' => 'foo',
                'ts' => '1567006792.003100',
                'team' => 'T0FLD8LEM',
                'channel' => 'MY_CHANNEL_ID',
                'event_ts' => '1567006792.003100',
                'channel_type' => 'channel',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMVBZR18E',
            'event_time' => '1567006784',
            'authed_users' => [
                'foo',
            ],
        ];

        $this->assertInstanceOf(Debt::class, $this->bigBrowser->createDebtIfNeeded($payload));

        // Reaction, author Jean, later

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'type' => 'reaction_added',
                'user' => 'Jean',
                'item' => [
                    'type' => 'message',
                    'channel' => 'MY_CHANNEL_ID',
                    'ts' => '1567006791.003100',
                ],
                'reaction' => 'slightly_smiling_face',
                'event_ts' => '1567006791.003100',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMZ5HFMNC',
            'event_time' => 1567525929,
            'authed_users' => [
                0 => 'U0FLDV6UW',
            ],
        ];

        $this->assertInstanceOf(Debt::class, $this->bigBrowser->createDebtIfNeeded($payload));

        // Reaction, author Jean, later

        $payload = [
            'token' => '6X6M8tbvmeO3aOVAbjUopW2Y',
            'team_id' => 'T0FLD8LEM',
            'api_app_id' => 'AMV1PTJBH',
            'event' => [
                'type' => 'reaction_added',
                'user' => 'Jean',
                'item' => [
                    'type' => 'message',
                    'channel' => 'MY_CHANNEL_ID',
                    'ts' => '1567006792.003100',
                ],
                'reaction' => 'slightly_smiling_face',
                'event_ts' => '1567006792.003100',
            ],
            'type' => 'event_callback',
            'event_id' => 'EvMZ5HFMNC',
            'event_time' => 1567525929,
            'authed_users' => [
                0 => 'U0FLDV6UW',
            ],
        ];

        $this->assertNull($this->bigBrowser->createDebtIfNeeded($payload));
    }
}
