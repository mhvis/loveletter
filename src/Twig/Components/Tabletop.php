<?php

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\GameState;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class Tabletop
{
    use DefaultActionTrait;

    public Game $game;
    public int $player;

    public function getGameState(): GameState
    {
        return new GameState(
            $this->game->getGroupSize(),
            $this->game->getState()
        );
    }
}
