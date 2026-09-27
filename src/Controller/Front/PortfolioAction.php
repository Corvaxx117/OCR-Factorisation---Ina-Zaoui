<?php

namespace App\Controller\Front;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Affiche le portfolio d'Ina, filtrable par album.
 * Sans filtre, affiche les médias de l'admin.
 */
class PortfolioAction extends AbstractController
{
    #[Route(path: '/portfolio/{id?}', name: 'portfolio')]
    public function __invoke(AlbumRepository $albumRepository, MediaRepository $mediaRepository, ?int $id = null): Response
    {
        $albums = $albumRepository->findAll();
        $album = $id ? $albumRepository->find($id) : null;
        $medias = $mediaRepository->findPortfolioMedias($album);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
        ]);
    }
}
