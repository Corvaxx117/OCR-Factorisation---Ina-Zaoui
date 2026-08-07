<?php

namespace App\Controller\Admin\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class LogoutAction extends AbstractController
{
    #[Route(path: '/logout', name: 'admin_logout')]
    public function __invoke(): void
    {
        // Ce controller ne sera jamais exécuté.
        // Symfony intercepte la requête via le firewall.
    }
}
