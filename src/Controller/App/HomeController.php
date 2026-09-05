<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(OAUTH_ISSUER)%')] private readonly string $oauthIssuer,
    ) {
    }

    #[Route('/{path<.*>}', name: 'home')]
    public function index(): Response
    {
        return $this->render('home.html.twig', ['oauthIssuer' => $this->oauthIssuer]);
    }
}
