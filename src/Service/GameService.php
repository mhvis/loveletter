<?php

namespace App\Service;

use App\Entity\Game;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Methods that implement the game rules for Love Letter.
 */
class GameService
{
    /**
     * Performs the game preparation.
     *
     * The steps are:
     *
     * 1. Shuffle cards.
     * 2. Remove the top card from the deck.
     * 3. If the group size is 2, put three cards aside.
     * 4. Deal one card to each hand.
     * 5. Pick a random starting player and deal a card.
     *
     * @param int|null $seed If provided, will be used when shuffling the deck.
     */
    public function initialize(Game $game, ?int $seed = null): void
    {
        $r = new Randomizer(new Xoshiro256StarStar($seed));

        // Shuffle cards
        $game->setDeck($r->shuffleArray($game->getDeck()));

        // Remove top card
        $game->setWithdrawn($game->draw());

        if (2 === $game->getGroupSize()) {
            // Put 3 cards aside
            $game->setAside([
                $game->draw(),
                $game->draw(),
                $game->draw(),
            ]);
        }

        // Deal cards
        for ($i = 1; $i <= $game->getGroupSize(); ++$i) {
            $game->addToHand($i, $game->draw());
        }

        // Pick starting player and deal another card
        $game->addToHand($r->getInt(1, $game->getGroupSize()), $game->draw());
    }

    /**
     * Advances the turn to the next player after given player.
     */
    public function advanceTurn(Game $game, int $player): void
    {
        if ($game->roundOver()) {
            return;
        }


        $alive = $game->getAlive();
        $card = $game->draw();
        if (count($alive) == 1 || !$card) {
            // Round is ended: one player left or deck is empty
            return;
        }

        // We assume the $alive array is 0-indexed without gaps
        $next = $alive[(array_search($player, $alive) + 1) % count($alive)];

        $game->addToHand($next, $card);
    }
}
