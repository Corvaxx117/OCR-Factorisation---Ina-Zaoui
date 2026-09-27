<?php

namespace App\Controller\Front;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Affiche la liste des photographes invités actifs.
 */
class GuestsAction extends AbstractController
{
    #[Route(path: '/guests', name: 'guests')]
    public function __invoke(UserRepository $userRepository): Response
    {
        $guests = $userRepository->findActiveGuestsWithMedias();

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }
}
