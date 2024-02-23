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
        ];
        for ($i = 1; $i <= $groupSize; $i++) {
            $this->state['hands'][$i] = [];
            $this->state['open'][$i] = [];
            $this->state['chips'][$i] = 0;
        }

    }


    public function getDeck(): array
    {
        return $this->state['deck'];
    }

    public function setDeck(array $deck)
    {
        $this->state['deck'] = $deck;
    }

    /**
     * Draws a card from the deck and removes it from the deck.
     */
    public function draw(): int
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
     * Adds a card to the list of open cards for a player.
     */
    public function addOpen(int $player, int $card): void
    {
        if (!array_key_exists($player, $this->state['open'])) {
            throw new \ValueError();
        }
        $this->state['open'][$player][] = $card;
    }

    public function addToHand(int $player, int $card)
    {
        if (!array_key_exists($player, $this->state['hands'])) {
            throw new \ValueError();
        }
        $this->state['hands'][$player][] = $card;
    }

    public function removeFromHand(int $player, int $card)
    {
        if (!array_key_exists($player, $this->state['hands'])) {
            throw new \ValueError();
        }
        $key = array_search($card, $this->state['hands'][$player]);
        unset($this->state['hands'][$player][$key]);
    }

    /**
     * @return array An array with format [playerNumber => chipCount].
     */
    public function getChips(): array
    {
        return $this->state['chips'];
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
}
