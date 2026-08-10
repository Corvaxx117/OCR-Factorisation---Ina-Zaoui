<?php

namespace App\Controller\Front;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class GuestsAction extends AbstractController
{
    #[Route(path: '/guests', name: 'guests')]
    public function __invoke(UserRepository $userRepository)
    {
        $guests = $userRepository->findBy(['admin' => false, 'active' => true]);

        return $this->render('front/guests.html.twig', [
            'guests' => $guests
        ]);
    }
}
