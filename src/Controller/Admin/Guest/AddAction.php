<?php

namespace App\Controller\Admin\Guest;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\User;
use App\Form\GuestType;
use App\Service\GuestRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Ajout d'un invité : crée un User avec admin=false, hash le mot de passe.
 */
#[IsGranted('ROLE_ADMIN')]
class AddAction extends AbstractController
{
    use AdminActionTrait;

    #[Route(path: '/admin/guest/add', name: 'admin_guest_add')]
    public function __invoke(Request $request, GuestRegistrationService $guestRegistrationService): Response
    {
        $guest = new User();
        $form = $this->createForm(GuestType::class, $guest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if (!is_string($plainPassword)) {
                throw new \LogicException('Le mot de passe est invalide.');
            }

            $guestRegistrationService->register($guest, $plainPassword);

            return $this->redirectWithSuccess('Invité ajouté avec succès.', 'admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
