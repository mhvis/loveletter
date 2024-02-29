<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameRepository::class)]
// We don't need to specify indexes because the fields have unique: true.
// #[ORM\Index(fields: ['player1'])]
// #[ORM\Index(fields: ['player2'])]
// #[ORM\Index(fields: ['player3'])]
// #[ORM\Index(fields: ['player4'])]
class Game
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $code;

    #[ORM\Column]
    private ?int $groupSize = null;

    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: true)]
    private ?Uuid $player1 = null;

    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: true)]
    private ?Uuid $player2 = null;

    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: true)]
    private ?Uuid $player3 = null;

    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: true)]
    private ?Uuid $player4 = null;

    #[ORM\Column]
    private array $state = [];

    /**
     * Resets the game state to the starting position with an unshuffled deck.
     *
     * Does not reset chip count.
     */
    public function resetState(): void
    {
        $this->state['deck'] = [1, 1, 1, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 7, 8];
        $this->state['aside'] = [];
        $this->state['withdrawn'] = null;
        $this->state['last_turn'] = null;
        for ($i = 1; $i <= $this->groupSize; $i++) {
            $this->state['hands'][$i] = [];
            $this->state['open'][$i] = [];
            $this->state['immune'][$i] = false;
        }
    }

    /**
     * Constructs a new game instance with a full unshuffled deck and empty hands.
     */
    public function __construct(int $groupSize)
    {
        if ($groupSize < 1 || $groupSize > 4) {
            throw new \ValueError('Invalid group size');
        }
        $this->groupSize = $groupSize;
        $this->state = [
            'deck' => [1, 1, 1, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 7, 8],
            'hands' => [],
            'open' => [],
            'chips' => [],
            'aside' => [],
            // We slapped this on later, we should refactor this
            'immune' => [],
            'withdrawn' => null,  // This is the card which is removed from the game at the start
            'last_turn' => null,
        ];
        for ($i = 1; $i <= $this->groupSize; $i++) {
            $this->state['hands'][$i] = [];
            $this->state['open'][$i] = [];
            $this->state['chips'][$i] = 0;
            $this->state['immune'][$i] = false;
        }
    }

    public function getDeck(): array
    {
        return $this->state['deck'];
    }

    public function setDeck(array $deck): void
    {
        $this->state['deck'] = $deck;
    }

    /**
     * Draws a card from the deck and removes it from the deck.
     */
    public function draw(): ?int
    {
        return array_pop($this->state['deck']);
    }

    /**
     * Returns an array with the hands of all players.
     *
     * @return array An array with [playerNumber => [cardNumber]].
     */
    public function getHands(): array
    {
        return $this->state['hands'];
    }

    /**
     * Returns the cards that are put aside at the start of the game.
     */
    public function getAside(): array
    {
        return $this->state['aside'];
    }

    public function setAside(array $aside): void
    {
        $this->state['aside'] = $aside;
    }

    /**
     * Returns the open cards for each player.
     *
     * @return array Array with format [playerNumber => [cardNumber]].
     */
    public function getOpen(): array
    {
        return $this->state['open'];
    }

    /**
     * Opens a card in a hand by moving it from the hand to the open cards.
     */
    public function openCard(int $player, int $card): static
    {
        if (!array_key_exists($player, $this->state['hands'])) {
            throw new \ValueError();
        }
        $idx = array_search($card, $this->state['hands'][$player]);

        unset($this->state['hands'][$player][$idx]);
        $this->state['open'][$player][] = $card;

        return $this;
    }

    public function addToHand(int $player, int $card)
    {
        if (!array_key_exists($player, $this->state['hands'])) {
            throw new \ValueError();
        }
        $this->state['hands'][$player][] = $card;
    }

//    public function removeFromHand(int $player, int $card)
//    {
//        if (!array_key_exists($player, $this->state['hands'])) {
//            throw new \ValueError();
//        }
//        $key = array_search($card, $this->state['hands'][$player]);
//        unset($this->state['hands'][$player][$key]);
//    }

    /**
     * Swaps the hand between two players.
     */
    public function swapHands(int $player1, int $player2)
    {
        $swap = $this->state['hands'][$player1];
        $this->state['hands'][$player1] = $this->state['hands'][$player2];
        $this->state['hands'][$player2] = $swap;
    }

    /**
     * If the given player has a single card in their hand, return its value.
     */
    public function getHandValue(int $player): int
    {
        $hand = $this->state['hands'][$player];
        if (count($hand) != 1) {
            throw new \ValueError();
        }
        return array_values($hand)[0];
    }

    /**
     * @return array An array with format [playerNumber => chipCount].
     */
    public function getChips(): array
    {
        return $this->state['chips'];
    }

    public function incrementChips(int $player): void
    {
        if (!array_key_exists($player, $this->state['chips'])) {
            throw new \ValueError();
        }
        $this->state['chips'][$player]++;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getGroupSize(): ?int
    {
        return $this->groupSize;
    }

    public function getPlayer(int $player): ?Uuid
    {
        return match ($player) {
            1 => $this->player1,
            2 => $this->player2,
            3 => $this->player3,
            4 => $this->player4,
            default => throw new \ValueError('Invalid player'),
        };
    }

    public function clearPlayer(int $player): static
    {
        switch ($player) {
            case 1:
                $this->player1 = null;
                break;
            case 2:
                $this->player2 = null;
                break;
            case 3:
                $this->player3 = null;
                break;
            case 4:
                $this->player4 = null;
                break;
            default:
                throw new \ValueError();
        }

        return $this;
    }

    public function enterPlayer(int $player): static
    {
        $uuid = Uuid::v4();
        switch ($player) {
            case 1:
                $this->player1 = $uuid;
                break;
            case 2:
                $this->player2 = $uuid;
                break;
            case 3:
                $this->player3 = $uuid;
                break;
            case 4:
                $this->player4 = $uuid;
                break;
            default:
                throw new \ValueError();
        }

        return $this;
    }

    /**
     * Returns the players with their uid, or null when they don't have an uid yet.
     */
    public function getPlayers(): array
    {
        $players = [
            1 => $this->player1,
            2 => $this->player2,
        ];
        if ($this->groupSize >= 3) {
            $players[3] = $this->player3;
        }
        if ($this->groupSize >= 4) {
            $players[4] = $this->player4;
        }
        return $players;
    }

    /**
     * Returns the player number for a spot that has not yet been claimed.
     *
     * @return int|null A player number or null if all spots are occupied.
     */
    public function getFreeSpot(): ?int
    {
        foreach ($this->getPlayers() as $player => $uid) {
            if ($uid === null) {
                return $player;
            }
        }
        return null;
    }

    /**
     * Returns the player number who's turn it currently is.
     */
    public function turn(): ?int
    {
        foreach ($this->state['hands'] as $player => $hand) {
            if (count($hand) === 2) {
                return $player;
            }
        }
        return null;
    }

    public function isAlive(int $player): bool
    {
        if (!array_key_exists($player, $this->state['hands'])) {
            throw new \ValueError();
        }
        return count($this->state['hands'][$player]) > 0;
    }

    /**
     * Returns the players who have not been discarded.
     */
    public function getAlive(): array
    {
        $alive = [];
        foreach ($this->state['hands'] as $player => $hand) {
            if (count($hand) > 0) {
                $alive[] = $player;
            }
        }
        return $alive;
    }

    public function setImmune(int $player, bool $value): static
    {
        if (!array_key_exists($player, $this->state['immune'])) {
            throw new \ValueError();
        }
        $this->state['immune'][$player] = $value;

        return $this;
    }

    /**
     * Returns true iff the given player played card 4 this round (Kamermeisje).
     */
    public function isImmune(int $player): bool
    {
        // Check that the player has 1 card in his hand
        return (
            count($this->state['hands'][$player]) == 1 && $this->state['immune'][$player]
        );
    }

    /**
     * Returns the alive players who are not immune.
     */
    public function getNonImmune(): array
    {
        $value = [];
        foreach ($this->getAlive() as $player) {
            if (!$this->isImmune($player)) {
                $value[] = $player;
            }
        }
        return $value;
    }

    /**
     * Sets the card that is discarded at the start of the game.
     */
    public function setWithdrawn(?int $card): void
    {
        $this->state['withdrawn'] = $card;
    }

    /**
     * Returns and clears the withdrawn card.
     */
    public function grabWithdrawn(): ?int
    {
        $val = $this->state['withdrawn'];
        $this->state['withdrawn'] = null;
        return $val;
    }

    public function setLastTurn(int $player, int $card, ?int $target, ?int $guess): void
    {
        $this->state['last_turn'] = array(
            'player' => $player,
            'card' => $card,
            'target' => $target,
            'guess' => $guess,
        );
    }

    public function getLastTurn(): ?array
    {
        return $this->state['last_turn'];
    }

    /**
     * Returns true when the round is over, i.e. the deck is empty or there is one player left.
     */
    public function roundOver(): bool
    {
        return empty($this->state['deck']) || count($this->getAlive()) == 1;
    }

    public function roundWinner(): int
    {
        if (!$this->roundOver()) {
            throw new \ValueError('Round is not over');
        }
        $alive = $this->getAlive();

        // Win condition 1: only one player left alive
        if (count($alive) == 1) {
            return $alive[0];
        }

        // Win condition 2: highest hand value
        $hands = [];
        foreach ($alive as $player) {
            $hands[$player] = $this->state['hands'][$player];
        }
        $maxs = array_keys($hands, max($hands));
        if (count($maxs) == 1) {
            return $maxs[0];
        }

        // Win condition 3: highest open card value
        $open = [];
        foreach ($alive as $player) {
            $open[$player] = array_sum($this->state['open'][$player]);
        }
        $maxs = array_keys($open, max($open));
        if (count($maxs) != 1) {
            throw new \RuntimeException('Unexpected game state');
        }
        return $maxs[0];
    }
}
