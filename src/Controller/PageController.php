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
class PageController extends AbstractController
{
    /**
     * @Route("/{title}", name="page")
     * @IsGranted("ROLE_USER")
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
