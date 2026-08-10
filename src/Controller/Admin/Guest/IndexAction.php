<?php

namespace App\Controller\Admin\Guest;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class IndexAction extends AbstractController
{
    #[Route(path: '/admin/guest', name: 'admin_guest_index')]
    public function __invoke(UserRepository $userRepository): Response
    {
        $guests = $userRepository->findBy(['admin' => false]);

        return $this->render('admin/guest/index.html.twig', [
            'guests' => $guests,
        ]);
    }
}
