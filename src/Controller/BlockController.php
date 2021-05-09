<?php

namespace App\Controller;

use App\Entity\Block;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("wiki/block", name="wiki_block")
 * @IsGranted("ROLE_ADMIN")
 */
class BlockController extends AbstractController
{
    /**
     * @Route("/delete/{id_block}", name="delete")
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete_block(int $id_block): Response
    {
        $block = $this->getDoctrine()->getRepository(Block::class)->find($id_block);

        if (!is_null($block)) {
            return $this->redirectToRoute('wiki_page', [
                'title' => $block->getPage()->getTitle()
            ]);
        }
        throw $this->createNotFoundException('Comment not found');
    }
}
