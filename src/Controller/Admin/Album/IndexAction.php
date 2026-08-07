<?php

namespace App\Controller\Admin\Album;

use App\Repository\AlbumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class IndexAction extends AbstractController
{
    #[Route(path: '/admin/album', name: 'admin_album_index')]
    public function __invoke(AlbumRepository $albumRepository)
    {
        $albums = $albumRepository->findAll();

        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }
}
