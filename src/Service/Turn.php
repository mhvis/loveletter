<?php

namespace App\Service;

use App\Entity\Game;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a turn in the game.
 */
class Turn
{
    #[Assert\Choice(callback: 'validCards')]
    #[Assert\NotNull]
    public ?int $card = null;

//    #[Assert\Choice(callback: 'validTargets')]
//    #[Assert\NotBlank]
    public ?int $target = null;

//    #[Assert\Choice(callback: 'validGuesses')]
//    #[Assert\NotBlank]
    public ?int $guess = null;

    private Game $game;
    private int $currentPlayer;

    public function __construct(Game $game, int $currentPlayer)
    {
        $this->game = $game;
        $this->currentPlayer = $currentPlayer;
    }

    #[Assert\IsTrue]
    public function isPlayersTurn(): bool
    {
        return $this->game->turn() == $this->currentPlayer;
    }

    /**
     * Returns cards in the hand that can be played.
     */
    public function validCards(): array
    {
        $choices = $this->game->getHands()[$this->currentPlayer];

        // Rule: if you have 7 (Gravin) with 5 or 6, you have to play 7
        if (in_array(7, $choices) && (
                in_array(5, $choices) || in_array(6, $choices)
            )) {
            return [7];
        }

        return $choices;
    }

    #[Assert\IsTrue]
    public function isValidTarget(): bool
    {
        if (in_array($this->card, [4, 7, 8])) {
            // Cards 4, 7 and 8 should not have a target
            return $this->target === null;
        }

        $choices = [];
        foreach ($this->game->getAlive() as $player) {
            if ($player == $this->currentPlayer) {
                if ($this->card == 5) {
                    $choices[] = $player;
                }
            } elseif (!$this->game->isImmune($player)) {
                $choices[] = $player;
            }
        }

        if (empty($choices)) {
            // No available players
            return $this->target === null;
        }

        return in_array($this->target, $choices);
    }

    #[Assert\IsTrue]
    public function isValidGuess(): bool
    {
        if ($this->card == 1) {
            return in_array($this->guess, [2, 3, 4, 5, 6, 7, 8]);
        }
        return $this->guess === null;
    }

//    #[Assert\IsTrue]
//    public function isValidMove(): bool
//    {
//        // Validate that it's your turn
//        if ($this->game->turn() != $this->currentPlayer) {
//            return false;
//        }
//
//        // Validate that you can play the card
//        $choices = $this->game->getHands()[$this->currentPlayer];
//
//        // Rule: if you have 7 (Gravin) with 5 or 6, you have to play 7
//        if (in_array(7, $choices) && (
//                in_array(5, $choices) || in_array(6, $choices)
//            )) {
//            $choices = [7];
//        }
//        if (!in_array($this->card, $choices)) {
//            return false;
//        }
//
//        // Cards 1, 2, 3, 5, 6 must target an alive and non-immune player
//        if (in_array($this->card, [1, 2, 3, 5, 6])
//            && !in_array($this->target, $this->game->getAlive())) {
//            return false;
//        }
//
//        // Cards 1, 2, 3, 6 must target a *different* player
//        if (in_array($this->card, [1, 2, 3, 6]) && $this->target == $this->currentPlayer) {
//            return false;
//        }
//
//        // Card 1 must have a guess, which is not 1
//        if ($this->card == 1 && !in_array($this->guess, [2, 3, 4, 5, 6, 7, 8])) {
//            return false;
//        }
//        return true;
//    }

    /**
     * Modifies the game object by applying the action in this turn.
     */
    public function apply(): void
    {
        // Clear immune state
        $this->game->setImmune($this->currentPlayer, false);

        // Play the card by putting it open
        $this->game->openCard($this->currentPlayer, $this->card);

        // Actions 1, 2, 3, 6 have no effect when there is no target available (i.e.
        // all other players are protected by 4)
        if ($this->target) {
            if ($this->card == 1) {
                // Wachter: when the target has the guessed card, open it
                if (in_array(
                    $this->guess,
                    $this->game->getHands()[$this->target]
                )) {
                    $this->game->openCard($this->target, $this->guess);
                }
            } elseif ($this->card == 2) {
                // Priester: show card


            } elseif ($this->card == 3) {
                // Baron: lowest card is discarded
                $self = $this->game->getHandValue($this->currentPlayer);
                $opposing = $this->game->getHandValue($this->target);

                if ($self < $opposing) {
                    $this->game->openCard($this->currentPlayer, $self);
                } elseif ($opposing < $self) {
                    $this->game->openCard($this->target, $opposing);
                }
            } elseif ($this->card == 6) {
                $this->game->swapHands($this->currentPlayer, $this->target);
            }
        }

        if ($this->card == 4) {
            // Card 4 sets immunity
            $this->game->setImmune($this->currentPlayer, true);

        } elseif ($this->card == 5) {
            // Prins: draw a new card
            $this->game->openCard(
                $this->target,
                $this->game->getHandValue($this->target)
            );
            $new = $this->game->draw();
            // When the deck is empty, we take the withdrawn card
            if ($new === null) {
                $new = $this->game->grabWithdrawn();
            }

            $this->game->addToHand($this->target, $new);

        } elseif ($this->card == 8) {
            // Card 8 discards your own hand
            $this->game->openCard(
                $this->currentPlayer,
                $this->game->getHandValue($this->currentPlayer)
            );
        }

    }
}
