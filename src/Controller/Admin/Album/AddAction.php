<?php

namespace App\Controller\Admin\Album;

use App\Entity\Album;
use App\Form\AlbumType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Formulaire de création d'un nouvel album.
 */
class AddAction extends AbstractController
{
    #[Route(path: '/admin/album/add', name: 'admin_album_add')]
    public function __invoke(Request $request, EntityManagerInterface $em)
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($album);
            $em->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', ['form' => $form->createView()]);
    }
}
