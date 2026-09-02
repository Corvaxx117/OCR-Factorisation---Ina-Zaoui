<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Factorise les deux briques répétées dans tous les controllers d'administration :
 * la vérification CSRF avant une action destructive, et le flash + redirect après succès.
 * Suppose une classe hôte étendant AbstractController (isCsrfTokenValid, addFlash, redirectToRoute).
 */
trait AdminActionTrait
{
    protected function denyAccessUnlessValidCsrfToken(string $tokenId, Request $request): void
    {
        if (!$this->isCsrfTokenValid($tokenId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token invalide.');
        }
    }

    protected function redirectWithSuccess(string $message, string $route): RedirectResponse
    {
        $this->addFlash('success', $message);

        return $this->redirectToRoute($route);
    }
}
