<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\Page;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\Request;

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
    public function page(string $title, Request $request): Response
    {
        $page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['title' => $title]);

        if(!is_null($page)){
            $comments = $page->getComments();

            $comment = new Comment();

            $comment_form = $this->createForm(CommentType::class, $comment);

            $comment_form->handleRequest($request);

            if ($comment_form->isSubmitted() && $comment_form->isValid()) {
                $comment = $comment_form->getData();

                $comment->setUser($this->getUser());
                $comment->setPage($page);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($comment);
                $entityManager->flush();
    
                return $this->redirectToRoute('wiki_page', [
                    'title' => $title
                ]);
            }

            return $this->render('main/page.html.twig', [
                'page' => $page,
                'comments' => $comments,
                'comment_form' => $comment_form->createView()
            ]);
        }
        return $this->render('main/not_found.html.twig');
    }
}
