<?php

namespace App\Controller;

use App\Entity\Game;
use App\Service\GameService;
use App\Service\GameState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/', name: 'game_home')]
    public function home(
        Request                $request,
        EntityManagerInterface $entityManager,
        GameService            $gameService
    ): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('new', $request->getPayload()->get('token'))) {
                throw new BadRequestHttpException();
            }

            // Create a new game
            $groupSize = $request->getPayload()->getInt('group_size');
            if ($groupSize < 2 || $groupSize > 4) {
                throw new BadRequestHttpException();
            }

            $game = new Game($groupSize);
            $game->enterPlayer(1);
            $gameService->initialize($game);

            $entityManager->persist($game);
            $entityManager->flush();

            return $this->redirectToRoute(
                'game',
                ['player' => '1', 'code' => $game->getPlayer(1)]
            );
        }

        return $this->render('home.html.twig', []);

    }

//    #[Route('/new/', name: 'game_new', methods: ['POST'])]
//    public function new(): Response
//    {
//
//    }

    #[Route('/join/{code}/', name: 'game_join')]
    public function join(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('join', $request->getPayload()->get('token'))) {
                throw new BadRequestHttpException();
            }

            $spot = $game->getFreeSpot();
            if (!$spot) {
                throw new BadRequestHttpException();
            }

            // There is a race condition possible here when 2 people join at the same
            // time. But this race condition won't lead to issues.

            $game->enterPlayer($spot);
            $entityManager->flush();

            return $this->redirectToRoute(
                'game',
                ['player' => $spot, 'code' => $game->getPlayer($spot)]
            );

        }
        return $this->render('join.html.twig', ['game' => $game]);
    }

    #[Route('/game/{player<(1|2|3|4)>}/{code}/', name: 'game')]
    public function game(
        #[MapEntity(expr: 'repository.findOneBy({("player" ~ player): code})')]
        Game $game,
        int  $player
    ): Response
    {
        return $this->render('game.html.twig', [
            'game' => $game,
            'player' => $player
        ]);
    }

//    #[Route('/game/{player<(1|2|3|4)>}/{code}/', name: 'game')]
//    public function game(int $player, string $code, GameRepository $gameRepository): Response
//    {
//        $game = $gameRepository->findOneBy();
//
//        if (!$game) {
//            throw $this->createNotFoundException();
//        }
//
//        return $this->render();
//    }
}
