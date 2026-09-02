<?php

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Upload d'un nouveau média avec validation du fichier.
 * Génère un nom de fichier aléatoire sécurisé.
 */
class AddAction extends AbstractController
{
    use AdminActionTrait;

    #[Route(path: '/admin/media/add', name: 'admin_media_add')]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        FileUploadService $fileUploadService,
        #[CurrentUser] User $currentUser
        )
    {
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($currentUser);
            }
            $media->setPath($fileUploadService->upload($media->getFile()));
            $em->persist($media);
            $em->flush();

            return $this->redirectWithSuccess('Média ajouté avec succès.', 'admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form->createView()]);
    }
}
