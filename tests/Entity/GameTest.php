<?php

namespace App\Tests\Entity;

use App\Entity\Game;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    /**
     * Tests the construction of a new game.
     *
     * The deck must be unshuffled and full. Hands must be empty.
     */
    public function testConstruct()
    {
        $game = new Game(3);
        $this->assertEquals(
            [1, 1, 1, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 7, 8],
            $game->getDeck()
        );
        $this->assertEquals([1 => [], 2 => [], 3 => []], $game->getHands());
        $this->assertEquals([], $game->getAside());
        $this->assertEquals([1 => [], 2 => [], 3 => []], $game->getOpen());
    }
}
