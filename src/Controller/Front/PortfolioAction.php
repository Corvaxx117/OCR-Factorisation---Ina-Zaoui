<?php

namespace App\Controller\Front;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Affiche le portfolio d'Ina, filtrable par album.
 * Sans filtre, affiche les médias de l'admin.
 */
class PortfolioAction extends AbstractController
{
    #[Route(path: '/portfolio/{id?}', name: 'portfolio')]
    public function __invoke(?int $id = null, AlbumRepository $albumRepository, MediaRepository $mediaRepository, UserRepository $userRepository)
    {
        $albums = $albumRepository->findAll();
        $album = $id ? $albumRepository->find($id) : null;
        $user = $userRepository->findOneBy(['admin' => true]);

        $medias = $album
            ? $mediaRepository->findBy(['album' => $album])
            : $mediaRepository->findBy(['user' => $user]);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias
        ]);
    }
}
