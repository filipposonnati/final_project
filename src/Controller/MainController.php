<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\Page; 

class MainController extends AbstractController
{
    /**
     * @Route("/", name="")
     */
    public function main(): Response
    {
        return $this->render('main/main.html.twig');
    }

    /**
     * @Route("/user", name="user")
     * @IsGranted("ROLE_USER")
     */
    public function user(): Response
    {
        return $this->render('main/user.html.twig');
    }

    /**
     * @Route("/admin", name="admin")
     * @IsGranted("ROLE_ADMIN")
     */
    public function admin(): Response
    {
        return $this->render('main/admin.html.twig');
    }

    /**
     * @Route("/wiki/{title}", name="page")
     * @IsGranted("ROLE_USER")
     */
    public function page(string $title): Response
    {
        $page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['title' => $title]);

        return $this->render('main/page.html.twig', [
            'page' => $page
        ]);
    }
}
