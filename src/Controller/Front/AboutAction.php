<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Affiche la page "Qui suis-je ?" présentant Ina Zaoui.
 */
class AboutAction extends AbstractController
{
    #[Route(path: '/about', name: 'about')]
    public function __invoke()
    {
        return $this->render('front/about.html.twig');
    }
}
