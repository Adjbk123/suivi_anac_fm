<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/security-test')]
class SecurityTestController extends AbstractController
{
    #[Route('/', name: 'app_security_test')]
    public function index(): Response
    {
        return $this->render('security/test_roles.html.twig');
    }
}
