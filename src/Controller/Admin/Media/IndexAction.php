<?php

namespace App\Controller\Admin\Media;

use App\Entity\User;
use App\Pagination\PaginatedResult;
use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Liste les médias avec pagination (20/page).
 * Les non-admins ne voient que leurs propres médias.
 */
class IndexAction extends AbstractController
{
    #[Route(path: '/admin/media', name: 'admin_media_index')]
    public function __invoke(Request $request, MediaRepository $mediaRepository, #[CurrentUser] User $currentUser): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pagination = $mediaRepository->paginateForUser(
            $this->isGranted('ROLE_ADMIN') ? null : $currentUser,
            $page,
            PaginatedResult::DEFAULT_PER_PAGE,
        );

        return $this->render('admin/media/index.html.twig', [
            'medias' => $pagination->items,
            'pagination' => $pagination,
        ]);
    }
}
