<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("", name="wiki_")
 */
class MainController extends AbstractController
{
    /**
     * @Route("", name="home")
     */
    public function main(): Response
    {
        $pages = $this->getDoctrine()->getRepository(Page::class)->findAll();

        /////////////////////////////////
        // selezione pagine specifiche //
        /////////////////////////////////
        $math_category = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['name' => 'Matematica']);
        $math_pages = $math_category->getPages();

        $cryptography_category = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['name' => 'Crittografia']);
        $cryptography_pages = $cryptography_category->getPages();

        $turing_page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['title' => 'Alan Turing']);

        return $this->render('main/main.html.twig', [
            'pages' => $pages,
            'math_pages' => $math_pages,
            'cryptography_pages' => $cryptography_pages,
            'turing_page' => $turing_page
        ]);
    }
}
