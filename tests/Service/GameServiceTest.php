<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Service\GameService;
use PHPUnit\Framework\TestCase;

final class GameServiceTest extends TestCase
{
    public function testInitialize(): void
    {
        $gameService = new GameService();
        $game = new Game(3);

        $gameService->initialize($game, seed: 1);

        $this->assertEquals([2, 1, 7, 8, 1, 5, 4, 3, 6, 5, 1], $game->getDeck());
        $this->assertEquals([1 => [1, 3], 2 => [4], 3 => [1]], $game->getHands());
        $this->assertEquals([1 => [], 2 => [], 3 => []], $game->getOpen());
        $this->assertEquals([], $game->getAside());
    }
}
