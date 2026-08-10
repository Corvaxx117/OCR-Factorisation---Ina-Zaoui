<?php

namespace App\Controller\Admin\Media;

use App\Entity\Media;
use App\Form\MediaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Upload d'un nouveau média avec validation du fichier.
 * Génère un nom de fichier aléatoire sécurisé.
 */
class AddAction extends AbstractController
{
    #[Route(path: '/admin/media/add', name: 'admin_media_add')]
    public function __invoke(Request $request, EntityManagerInterface $em)
    {
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($this->getUser());
            }
            $media->setPath('uploads/' . bin2hex(random_bytes(16)) . '.' . $media->getFile()->guessExtension());
            $media->getFile()->move('uploads/', $media->getPath());
            $em->persist($media);
            $em->flush();

            $this->addFlash('success', 'Média ajouté avec succès.');

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form->createView()]);
    }
}
