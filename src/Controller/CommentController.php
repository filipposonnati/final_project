<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("comment", name="wiki_comment_")
 */
class CommentController extends AbstractController
{
    /**
     * @Route("/delete/{id_comment}", name="delete", requirements={"id_comment"="\d+"})
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
                    'id' => $page->getId()
                ]);
            }
            throw $this->createAccessDeniedException('You can\'t delete this comment');
        }
        throw $this->createNotFoundException('Comment not found');
    }
}
