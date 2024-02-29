<?php

namespace App\Twig\Components;

use App\Entity\Game;
use App\Service\Turn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsLiveComponent]
class Tabletop extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public Game $game;
    #[LiveProp]
    public int $player;

//    #[LiveProp]
//    public string $tokenName;
//    #[LiveProp]
//    public string $tokenValue;

//    public FormView $form;

    protected function instantiateForm(): FormInterface
    {
        return $this->createFormBuilder(new Turn($this->game, $this->player))
            ->add('card')
            ->add('target')
            ->add('guess')
            ->getForm();
    }


}
