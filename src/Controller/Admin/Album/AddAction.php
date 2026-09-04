<?php

namespace App\Controller\Admin\Album;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\Album;
use App\Form\AlbumType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Formulaire de création d'un nouvel album (admin uniquement).
 */
#[IsGranted('ROLE_ADMIN')]
class AddAction extends AbstractController
{
    use AdminActionTrait;

    #[Route(path: '/admin/album/add', name: 'admin_album_add')]
    public function __invoke(Request $request, EntityManagerInterface $em): Response
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($album);
            $em->flush();

            return $this->redirectWithSuccess('Album ajouté avec succès.', 'admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', ['form' => $form->createView()]);
    }
}
