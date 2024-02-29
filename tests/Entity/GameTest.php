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
    public function testConstruct(): void
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

    /**
     * Tests that opening a card moves it from a hand to the open list.
     */
    public function testOpenCard(): void
    {
        $game = new Game(2);
        $game->addToHand(1, 2);
        $game->openCard(1, 2);

        $this->assertEquals([2], $game->getOpen()[1], 'In open');
        $this->assertEquals([], $game->getHands()[1], 'Not in hand');
    }

    public function testRoundWinner(): void
    {
        // One player alive
        $game = new Game(3);
        // Empty the deck so that the round appears to be over
        $game->setDeck([]);
        $game->addToHand(2, 2);
        $this->assertEquals(2, $game->roundWinner());

        // Two players alive: highest hand
        $game = new Game(3);
        $game->setDeck([]);
        $game->addToHand(2, 3);
        $game->addToHand(3, 4);
        $this->assertEquals(3, $game->roundWinner());

        // Two players alive: highest open value
        $game = new Game(3);
        $game->setDeck([]);
        $game->addToHand(1, 3);
        $game->addToHand(1, 4);
        $game->addToHand(2, 1);
        $game->addToHand(2, 4);
        $game->openCard(1, 3);
        $game->openCard(2, 1);
        $this->assertEquals(1, $game->roundWinner());
    }
}
