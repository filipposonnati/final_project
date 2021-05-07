<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\Page;

/**
 * @Route("/wiki", name="wiki_")
 */
class MainController extends AbstractController
{
    /**
     * @Route("", name="home")
     */
    public function main(): Response
    {
        $pages = $this->getDoctrine()->getRepository(Page::class)->findAll();

        return $this->render('main/main.html.twig', [
            'pages' => $pages
        ]);
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
     * @Route("/{title}", name="page")
     */
    public function page(string $title): Response
    {
        $page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['title' => $title]);

        if(!is_null($page)){
            $comments = $page->getComments();

            return $this->render('main/page.html.twig', [
                'page' => $page,
                'comments' => $comments
            ]);
        }
        return $this->render('main/not_found.html.twig');
    }
}
