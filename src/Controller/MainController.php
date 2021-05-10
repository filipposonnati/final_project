<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Page;
use App\Entity\Block;
use App\Form\CommentType;
use App\Form\TitlePageType;
use App\Form\TextPageType;
use App\Form\CodePageType;
use App\Form\ImagePageType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

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
     * @Route("/{title}", name="page")
     */
    public function page(string $title, Request $request, SluggerInterface $slugger): Response
    {
        $page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['title' => $title]);

        if (!is_null($page)) {
            $comments = $this->getDoctrine()
                ->getRepository(Comment::class)
                ->getSortedComments($page->getId());

            $blocks = $this->getDoctrine()
                ->getRepository(Block::class)
                ->getSortedBlocks($page->getId());

            //comment creation
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

            //title block creation
            $title_block = new Block();
            $title_form = $this->createForm(TitlePageType::class, $title_block);
            $title_form->handleRequest($request);

            if ($title_form->isSubmitted() && $title_form->isValid()) {
                $title_block = $title_form->getData();
                $title_block->setType('title');
                $title_block->setPage($page);

                $lastBlock = $this->getDoctrine()
                    ->getRepository(Block::class)
                    ->findLast($page->getId());

                if ($lastBlock == null) $title_block->setPosition(1);
                else $title_block->setPosition($lastBlock[0]->getPosition() + 1);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($title_block);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'title' => $title
                ]);
            }

            //text block creation
            $text_block = new Block();
            $text_form = $this->createForm(TextPageType::class, $text_block);
            $text_form->handleRequest($request);

            if ($text_form->isSubmitted() && $text_form->isValid()) {
                $text_block = $text_form->getData();
                $text_block->setType('text');
                $text_block->setPage($page);

                $lastBlock = $this->getDoctrine()
                    ->getRepository(Block::class)
                    ->findLast($page->getId());

                if ($lastBlock == null) $text_block->setPosition(1);
                else $text_block->setPosition($lastBlock[0]->getPosition() + 1);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($text_block);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'title' => $title
                ]);
            }

            //image block creation
            $image_block = new Block();
            $image_form = $this->createForm(ImagePageType::class);
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

                $lastBlock = $this->getDoctrine()
                    ->getRepository(Block::class)
                    ->findLast($page->getId());

                if ($lastBlock == null) $image_block->setPosition(1);
                else $image_block->setPosition($lastBlock[0]->getPosition() + 1);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($image_block);
                $entityManager->flush();
                $this->addFlash('success', 'Blog was created!');

                return $this->redirectToRoute('wiki_page', [
                    'title' => $title
                ]);
            }

            //code block creation
            $code_block = new Block();
            $code_form = $this->createForm(CodePageType::class, $code_block);
            $code_form->handleRequest($request);

            if ($code_form->isSubmitted() && $code_form->isValid()) {
                $code_block = $code_form->getData();
                $code_block->setType('code');
                $code_block->setPage($page);

                $lastBlock = $this->getDoctrine()
                    ->getRepository(Block::class)
                    ->findLast($page->getId());

                if ($lastBlock == null) $code_block->setPosition(1);
                else $code_block->setPosition($lastBlock[0]->getPosition() + 1);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($code_block);
                $entityManager->flush();

                return $this->redirectToRoute('wiki_page', [
                    'title' => $title
                ]);
            }

            return $this->render('main/page.html.twig', [
                'page' => $page,
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
}
