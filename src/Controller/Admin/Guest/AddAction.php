<?php

namespace App\Controller\Admin\Guest;

use App\Entity\User;
use App\Form\GuestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AddAction extends AbstractController
{
    #[Route(path: '/admin/guest/add', name: 'admin_guest_add')]
    public function __invoke(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $guest = new User();
        $form = $this->createForm(GuestType::class, $guest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            $guest->setPassword($hasher->hashPassword($guest, $plainPassword));
            $guest->setAdmin(false);
            $guest->setActive(true);

            $em->persist($guest);
            $em->flush();

            $this->addFlash('success', 'Invité ajouté avec succès.');

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
