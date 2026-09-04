<?php

namespace App\Controller\Admin\Album;

use App\Repository\AlbumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Liste tous les albums (admin uniquement).
 */
#[IsGranted('ROLE_ADMIN')]
class IndexAction extends AbstractController
{
    #[Route(path: '/admin/album', name: 'admin_album_index')]
    public function __invoke(AlbumRepository $albumRepository): Response
    {
        $albums = $albumRepository->findAll();

        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }
}
