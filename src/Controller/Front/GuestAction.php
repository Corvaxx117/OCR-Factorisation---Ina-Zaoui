<?php

namespace App\Controller\Front;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class GuestAction extends AbstractController
{
    #[Route(path: '/guest/{id}', name: 'guest')]
    public function __invoke(int $id, UserRepository $userRepository)
    {
        $guest = $userRepository->find($id);

        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }
}
