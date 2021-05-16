<?php

namespace App\Controller;

use App\Entity\Block;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\TitlePageType;
use App\Form\TextPageType;
use App\Form\CodePageType;
use App\Form\ImagePageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("block", name="wiki_block_")
 * @IsGranted("ROLE_ADMIN")
 */
class BlockController extends AbstractController
{
    /**
     * @Route("/delete/{id_block}", name="delete", requirements={"id_block"="\d+"})
     */
    public function delete(int $id_block): Response
    {
        $block_to_delete = $this->getDoctrine()->getRepository(Block::class)->find($id_block);

        if (!is_null($block_to_delete)) {
            $blocks = $this->getDoctrine()->getRepository(Block::class)->getAllAfter($block_to_delete);

            foreach ($blocks as $block) {
                $block->setPosition($block->getPosition() - 1);
            }

            $id = $block_to_delete->getPage()->getId();

            if ($block_to_delete->getType() == 'image')
                unlink($this->getParameter('image_directory') . $block_to_delete->getContent());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($block_to_delete);
            $entityManager->flush();

            return $this->redirectToRoute('wiki_page', [
                'id' => $id
            ]);
        }
        throw $this->createNotFoundException('Block not found');
    }

    /**
     * @Route("/move_up/{id_block}", name="move_up", requirements={"id_block"="\d+"})
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
                    'id' => $block_down->getPage()->getId()
                ]);
            }
        }
        throw $this->createNotFoundException('Block not found');
    }

    /**
     * @Route("/move_down/{id_block}", name="move_down", requirements={"id_block"="\d+"})
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
                    'id' => $block_up->getPage()->getId()
                ]);
            }
        }
        throw $this->createNotFoundException('Block not found');
    }

    /**
     * @Route("/edit/{id_block}", name="edit", requirements={"id_block"="\d+"})
     */
    public function edit(int $id_block, Request $request, SluggerInterface $slugger): Response
    {
        $block_to_edit = $this->getDoctrine()->getRepository(Block::class)->find($id_block);

        if (!is_null($block_to_edit)) {
            $page = $block_to_edit->getPage();

            $content_to_reload = $block_to_edit->getContent();
            $type_to_reload = $block_to_edit->getType();

            /////////////////////////////
            // eliminazione del blocco //
            /////////////////////////////
            $blocks = $this->getDoctrine()->getRepository(Block::class)->getAllAfter($block_to_edit);

            foreach ($blocks as $block) {
                $block->setPosition($block->getPosition() - 1);
            }

            $id = $block_to_edit->getPage()->getId();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($block_to_edit);
            $entityManager->flush();

            $comments = $this->getDoctrine()
                ->getRepository(Comment::class)
                ->getSortedComments($page->getId());

            $blocks = $this->getDoctrine()
                ->getRepository(Block::class)
                ->getSortedBlocks($page->getId());

            $title_blocks = $this->getDoctrine()
                ->getRepository(Block::class)
                ->getTitles($page->getId());

            $lastBlock = $this->getDoctrine()
                ->getRepository(Block::class)
                ->findLast($page->getId());

            if (!array_key_exists(0, $lastBlock))
                $lastBlock_position = 0;
            else
                $lastBlock_position = $lastBlock[0]->getPosition();

            $positions = range(0, $lastBlock_position + 1);
            unset($positions[0]);

            //////////////////////
            // comment creation //
            //////////////////////
            $comment = new Comment();
            $comment_form = $this->createForm(CommentType::class, $comment, [
                'action' => $this->generateUrl('wiki_page', [
                    'id' => $id
                ])
            ]);
            $comment_form->handleRequest($request);

            if ($comment_form->isSubmitted() && $comment_form->isValid()) {
                $comment = $comment_form->getData();

                $comment->setUser($this->getUser());
                $comment->setPage($page);
                $comment->setInsertDateTime(date("Y/m/d H:i"));

                $entityManager->persist($comment);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'id' => $id
                ]);
            }

            $lastBlock = $this->getDoctrine()
                ->getRepository(Block::class)
                ->findLast($page->getId());

            //////////////////////////
            // title block creation //
            //////////////////////////
            $title_block = new Block();
            if ($type_to_reload == 'title')
                $title_block->setContent($content_to_reload);
            $title_form = $this->createForm(TitlePageType::class, $title_block, [
                'positions' => $positions,
                'action' => $this->generateUrl('wiki_page', [
                    'id' => $id
                ])
            ]);
            $title_form->handleRequest($request);

            if ($title_form->isSubmitted() && $title_form->isValid()) {
                $title_block = $title_form->getData();
                $title_block->setType('title');
                $title_block->setPage($page);

                $blocks_to_shift = $this->getDoctrine()->getRepository(Block::class)->getAllNotBefore($title_block);

                foreach ($blocks_to_shift as $block) {
                    $block->setPosition($block->getPosition() + 1);
                }

                $entityManager->persist($title_block);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'id' => $id
                ]);
            }

            /////////////////////////
            // text block creation //
            /////////////////////////
            $text_block = new Block();
            if ($type_to_reload == 'text')
                $text_block->setContent($content_to_reload);
            $text_form = $this->createForm(TextPageType::class, $text_block, [
                'positions' => $positions,
                'action' => $this->generateUrl('wiki_page', [
                    'id' => $id
                ])
            ]);
            $text_form->handleRequest($request);

            if ($text_form->isSubmitted() && $text_form->isValid()) {
                $text_block = $text_form->getData();
                $text_block->setType('text');
                $text_block->setPage($page);

                $blocks_to_shift = $this->getDoctrine()->getRepository(Block::class)->getAllNotBefore($text_block);

                foreach ($blocks_to_shift as $block) {
                    $block->setPosition($block->getPosition() + 1);
                }

                $entityManager->persist($text_block);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'id' => $id
                ]);
            }

            /////////////////////////
            // code block creation //
            /////////////////////////
            $code_block = new Block();
            if ($type_to_reload == 'code')
                $code_block->setContent($content_to_reload);
            $code_form = $this->createForm(CodePageType::class, $code_block, [
                'positions' => $positions,
                'action' => $this->generateUrl('wiki_page', [
                    'id' => $id
                ])
            ]);
            $code_form->handleRequest($request);

            if ($code_form->isSubmitted() && $code_form->isValid()) {
                $code_block = $code_form->getData();
                $code_block->setType('code');
                $code_block->setPage($page);

                $blocks_to_shift = $this->getDoctrine()->getRepository(Block::class)->getAllNotBefore($code_block);

                foreach ($blocks_to_shift as $block) {
                    $block->setPosition($block->getPosition() + 1);
                }

                $entityManager->persist($code_block);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'id' => $id
                ]);
            }

            //////////////////////////
            // image block creation //
            //////////////////////////
            $image_block = new Block();
            $image_form = $this->createForm(ImagePageType::class, null, [
                'positions' => $positions,
                'action' => $this->generateUrl('wiki_page', [
                    'id' => $id
                ])
            ]);
            $image_form->handleRequest($request);

            if ($image_form->isSubmitted() && $image_form->isValid()) {
                $imageFile = $image_form->get('image')->getData();

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image cannot be saved.');
                }

                $image_block->setContent($newFilename);
                $image_block->setType('image');
                $image_block->setPage($page);

                $blocks_to_shift = $this->getDoctrine()->getRepository(Block::class)->getAllNotBefore($image_block);

                foreach ($blocks_to_shift as $block) {
                    $block->setPosition($block->getPosition() + 1);
                }

                $entityManager->persist($image_block);
                $entityManager->flush();
                $this->addFlash('success', 'Blog was created!');

                return $this->redirectToRoute('wiki_page', [
                    'id' => $id
                ]);
            }

            return $this->render('main/page.html.twig', [
                'page' => $page,
                'title_blocks' => $title_blocks,
                'blocks' => $blocks,
                'comments' => $comments,
                'title_form' => $title_form->createView(),
                'text_form' => $text_form->createView(),
                'image_form' => $image_form->createView(),
                'code_form' => $code_form->createView(),
                'comment_form' => $comment_form->createView()
            ]);
        }
        throw $this->createNotFoundException('Block not found');
    }
}
