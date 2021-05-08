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
     * @Route("/delete/comment/{id_comment}", name="delete_comment")
     * @IsGranted("ROLE_USER")
     */
    public function delete_comment(int $id_comment): Response
    {
        $comment = $this->getDoctrine()->getRepository(Comment::class)->find($id_comment);

        if (!is_null($comment)) {
            if (($this->getUser() == $comment->getUser()) || ($this->getUser()->getRoles() == array('ROLE_ADMIN'))) {
                $page = $comment->getPage();

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($comment);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'title' => $page->getTitle()
                ]);
            }
            throw $this->createAccessDeniedException('You can\'t delete this comment');
        }
        throw $this->createNotFoundException('Comment not found');
    }

    /**
     * @Route("/{title}", name="page")
     */
    public function page(string $title, Request $request): Response
    {
        $page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['title' => $title]);

        if (!is_null($page)) {
            $comments = $this->getDoctrine()
                ->getRepository(Comment::class)
                ->findSortedComments($page->getId());

            $comment = new Comment();

            $comment_form = $this->createForm(CommentType::class, $comment);

            $comment_form->handleRequest($request);

            if ($comment_form->isSubmitted() && $comment_form->isValid()) {
                $comment = $comment_form->getData();

                $comment->setUser($this->getUser());
                $comment->setPage($page);
                $comment->setInsertDateTime(date("Y/m/d H:i"));

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
        throw $this->createNotFoundException('Page not found');
    }
}
