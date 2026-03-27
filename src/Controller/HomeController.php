<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response {
        
        $searchQuery = $request->query->get('q');

        if ($searchQuery) {
            $data = $productRepository->searchEngine($searchQuery);
        } else {
            $data = $productRepository->findBy([], ['id' => 'DESC']);
        }

        $products = $paginator->paginate(
            $data, 
            $request->query->getInt('page', 1),
            12 
        );

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'searchQuery' => $searchQuery 
        ]);
    }
}