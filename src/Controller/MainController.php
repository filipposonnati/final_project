<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Block;
use App\Entity\Comment;
use App\Entity\Category;
use App\Form\CommentType;
use App\Form\TitlePageType;
use App\Form\TextPageType;
use App\Form\CodePageType;
use App\Form\ImagePageType;
use App\Form\PageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("", name="wiki_")
 */
class MainController extends AbstractController
{
    /**
     * @Route("", name="home")
     */
    public function main(Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $pages = $this->getDoctrine()->getRepository(Page::class)->findAll();
        $math_category = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['name' => 'Matematica']);
        $math_pages = $math_category->getPages();

        ///////////////////
        // page creation //
        ///////////////////
        $page = new Page();
        $page_form = $this->createForm(PageType::class, $page);
        $page_form->handleRequest($request);

        if ($page_form->isSubmitted() && $page_form->isValid()) {
            $page->setIntroduction('');
            $entityManager->persist($page);
            $entityManager->flush();

            return $this->redirectToRoute('wiki_page', [
                'id' => $page->getId()
            ]);
        }

        return $this->render('main/main.html.twig', [
            'pages' => $pages,
            'math_pages' => $math_pages,
            'page_form' => $page_form->createView()
        ]);
    }

    /**
     * @Route("/{id}", name="page", requirements={"id"="\d+"})
     */
    public function page(int $id, Request $request, SluggerInterface $slugger): Response
    {
        $page = $this->getDoctrine()->getRepository(Page::class)->find($id);

        if (!is_null($page)) {
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

            $entityManager = $this->getDoctrine()->getManager();

            //////////////////////
            // comment creation //
            //////////////////////
            $comment = new Comment();
            $comment_form = $this->createForm(CommentType::class, $comment);
            $comment_form->handleRequest($request);

            if ($comment_form->isSubmitted() && $comment_form->isValid()) {
                $comment = $comment_form->getData();

                $comment->setUser($this->getUser());
                $comment->setPage($page);
                $comment->setInsertDateTime(date("Y/m/d H:i"));

                $entityManager->persist($comment);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'id' => $page->getId()
                ]);
            }

            //////////////////////////
            // title block creation //
            //////////////////////////
            $title_block = new Block();
            $title_form = $this->createForm(TitlePageType::class, $title_block, [
                'positions' => $positions,
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
                    'id' => $page->getId()
                ]);
            }

            /////////////////////////
            // text block creation //
            /////////////////////////
            $text_block = new Block();
            $text_form = $this->createForm(TextPageType::class, $text_block, [
                'positions' => $positions,
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
                    'id' => $page->getId()
                ]);
            }

            /////////////////////////
            // code block creation //
            /////////////////////////
            $code_block = new Block();
            $code_form = $this->createForm(CodePageType::class, $code_block, [
                'positions' => $positions,
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
                    'id' => $page->getId()
                ]);
            }

            //////////////////////////
            // image block creation //
            //////////////////////////
            $image_block = new Block();
            $image_form = $this->createForm(ImagePageType::class, null, [
                'positions' => $positions,
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
                $image_block->setPosition($image_form->get('position')->getData());

                $blocks_to_shift = $this->getDoctrine()->getRepository(Block::class)->getAllNotBefore($image_block);

                foreach ($blocks_to_shift as $block) {
                    $block->setPosition($block->getPosition() + 1);
                }

                $entityManager->persist($image_block);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'id' => $page->getId()
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
        throw $this->createNotFoundException('Page not found');
    }

    /**
     * @Route("/delete/{id}", name="page_delete", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(int $id): Response
    {
        $page_to_delete = $this->getDoctrine()->getRepository(Page::class)->find($id);

        if (!is_null($page_to_delete)) {
            $blocks = $page_to_delete->getBlocks();

            foreach ($blocks as $block) {
                if ($block->getType() == 'image') {
                    unlink($this->getParameter('image_directory') . $block->getContent());
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($page_to_delete);
            $entityManager->flush();

            return $this->redirectToRoute('wiki_home');
        }
        throw $this->createNotFoundException('Page not found');
    }
}
