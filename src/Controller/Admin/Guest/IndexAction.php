<?php

namespace App\Controller\Admin\Guest;

use App\Pagination\PaginatedResult;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Liste tous les invités (admin uniquement).
 */
#[IsGranted('ROLE_ADMIN')]
class IndexAction extends AbstractController
{
    #[Route(path: '/admin/guest', name: 'admin_guest_index')]
    public function __invoke(Request $request, UserRepository $userRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pagination = $userRepository->paginateGuests($page, PaginatedResult::DEFAULT_PER_PAGE);

        return $this->render('admin/guest/index.html.twig', [
            'guests' => $pagination->items,
            'pagination' => $pagination,
        ]);
    }
}
