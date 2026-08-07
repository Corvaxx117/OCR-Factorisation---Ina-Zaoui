<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class HomeAction extends AbstractController
{
    #[Route(path: '/', name: 'home')]
    public function __invoke()
    {
        return $this->render('front/home.html.twig');
    }
}
