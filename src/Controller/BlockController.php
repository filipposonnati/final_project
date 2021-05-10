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

            if ($block_to_delete->getType() == 'image')
                unlink($this->getParameter('image_directory') . $block_to_delete->getContent());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($block_to_delete);
            $entityManager->flush();

            return $this->redirectToRoute('wiki_page', [
                'title' => $title
            ]);
        }
        throw $this->createNotFoundException('Block not found');
    }

    /**
     * @Route("/move_up/{id_block}", name="move_up")
     * @IsGranted("ROLE_ADMIN")
     */
    public function move_up(int $id_block): Response
    {
        $block_down = $this->getDoctrine()->getRepository(Block::class)->find($id_block);

        if (!is_null($block_down)) {
            $position_down = $block_down->getPosition();

            if ($position_down > 1) {
                $position_up = $position_down - 1;
                $block_up = $this->getDoctrine()->getRepository(Block::class)->findOneBy([
                    'position' => $position_up,
                    'page' => $block_down->getPage()
                ]);

                $block_down->setPosition($position_up);
                $block_up->setPosition($position_down);

                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('wiki_page', [
                    'title' => $block_down->getPage()->getTitle()
                ]);
            }
        }
        throw $this->createNotFoundException('Block not found');
    }

    /**
     * @Route("/move_down/{id_block}", name="move_down")
     * @IsGranted("ROLE_ADMIN")
     */
    public function move_down(int $id_block): Response
    {
        $block_up = $this->getDoctrine()->getRepository(Block::class)->find($id_block);

        if (!is_null($block_up)) {
            $position_up = $block_up->getPosition();

            $last_block = $this->getDoctrine()->getRepository(Block::class)->findLast($block_up->getPage());

            if ($position_up < $last_block[0]->getPosition()) {
                $position_down = $position_up + 1;
                $block_down = $this->getDoctrine()->getRepository(Block::class)->findOneBy([
                    'position' => $position_down,
                    'page' => $block_up->getPage()
                ]);

                $block_down->setPosition($position_up);
                $block_up->setPosition($position_down);

                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('wiki_page', [
                    'title' => $block_up->getPage()->getTitle()
                ]);
            }
        }
        throw $this->createNotFoundException('Block not found');
    }
}
