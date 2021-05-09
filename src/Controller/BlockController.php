<?php

namespace App\Controller;

use App\Entity\Block;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("wiki/block", name="wiki_block_")
 */
class BlockController extends AbstractController
{
    /**
     * @Route("/delete/{id_block}", name="delete")
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete_block(int $id_block): Response
    {
        $block_to_delete = $this->getDoctrine()->getRepository(Block::class)->find($id_block);

        if (!is_null($block_to_delete)) {
            $blocks = $this->getDoctrine()->getRepository(Block::class)->getAllAfter($block_to_delete);

            foreach ($blocks as $block) {
                $block->setPosition($block->getPosition() - 1);
            }

            $title = $block_to_delete->getPage()->getTitle();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($block_to_delete);
            $entityManager->flush();

            return $this->redirectToRoute('wiki_page', [
                'title' => $title
            ]);
        }
        throw $this->createNotFoundException('Comment not found');
    }
}
