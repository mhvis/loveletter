<?php
namespace App\Service;

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Keeps track of the cards and chips on the table.
 */
class GameState
{
    /**
     * For each player, the cards that lay open in front of them.
     */
    private array $open;

    /**
     * Cards that are discarded at the start of game and put aside.
     */
    private array $aside;

    /**
     * Cards in the hands of players.
     */
    private array $hands;

    /**
     * Chip counts.
     */
    private array $chips;

    /**
     * Cards that have not been played yet.
     */
    private array $deck;

    /**
     * @param int|null $seed when provided, is used to seed the randomizer
     */
    public function __construct(int $groupSize, ?array $state = null, ?int $seed = null)
    {
        if (null !== $state) {
            $this->open = $state['open'];
            $this->deck = $state['deck'];
            $this->chips = $state['chips'];
            $this->hands = $state['hands'];
            $this->aside = $state['aside'];
        } else {
            // No state given, generate initial state.
            $r = new Randomizer(new Xoshiro256StarStar($seed));
            $cards = [1, 1, 1, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 7, 8];

            // *Preparation*
            //
            // 1. Shuffle cards
            // 2. Discard one at the top
            // 3. In the case of 2 players, put 3 cards open on the table
            // 4. Deal a card to each player
            // 5. Pick a random starting player

            $this->deck = $r->shuffleArray($cards);

            array_pop($this->deck);

            $this->aside = [];
            if (2 === $groupSize) {
                array_push(
                    $this->aside,
                    array_pop($this->deck),
                    array_pop($this->deck),
                    array_pop($this->deck)
                );
            }

            $this->hands = [];
            $this->open = [];
            for ($i = 1; $i <= $groupSize; ++$i) {
                // Deal a card to each player
                $this->hands[$i] = [array_pop($this->deck)];

                // Initialize chip count
                $this->chips[$i] = 0;

                // Initialize open cards
                $this->open[$i] = [];
            }

            // Starting player
            $starting = $r->getInt(1, $groupSize);
            $this->hands[$starting][] = array_pop($this->deck);
        }
    }

    /**
     * Returns the game state as a JSON serializable array.
     *
     * Format:
     *
     *     [
     *         'open' => [playerNumber => [cards]],
     *         'aside' => [cards],
     *         'hands' => [playerNumber => [cards]],
     *         'chips' => [playerNumber => chipCount],
     *         'deck' => [cards],
     *     ]
     */
    public function getState(): array
    {
        return [
            'open' => $this->open,
            'aside' => $this->aside,
            'hands' => $this->hands,
            'chips' => $this->chips,
            'deck' => $this->deck,
        ];
    }
}
