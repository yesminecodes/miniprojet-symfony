<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductHistory;
use App\Entity\SubCategory;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Form\ProductHistoryType;
use App\Repository\ProductRepository;
use App\Repository\ProductHistoryRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();
                try{
                    $image->move($this->getParameter('image_dir'), $newFilename);
                }catch(FileException $e){}
                
                $product->setImage($newFilename);
            }
            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new ProductHistory();
            $stockHistory->setProduct($product);
            $stockHistory->setQuantity($product->getStock());
            $stockHistory->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();
            $this->addFlash('success', 'Product has been added.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Product has been updated.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        $image = $form->get('image')->getData();
        if ($image) {
            $imageName = uniqid().'.'.$image->guessExtension();
            $image->move($this->getParameter('product_images_directory'), $imageName);
            $product->setImage($imageName);
        }


        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('danger', 'Product has been deleted.');
        }



        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/add/product/{id}/stock', name: 'app_product_add_stock',methods: ['POST','GET'])]
    public function addStock(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $history = new ProductHistory();
        $form = $this->createForm(ProductHistoryType::class, $history);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $history->getQuantity();

            if ($quantity > 0) {
                
                $product->setStock($product->getStock() + $quantity);

                
                $history->setCreatedAt(new \DateTimeImmutable());
                $history->setProduct($product);

                $em->persist($history);
                $em->flush();

                $this->addFlash('success', 'Stock updated!');
                return $this->redirectToRoute('app_product_index');
            } else {
                $this->addFlash('danger', 'The quantity must be greater than zero.');
            }
        }
        return $this->render('product/add_stock.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }
    #[Route('/add/product/{id}/stock/history', name: 'app_product_add_stock_history',methods: ['POST','GET'])]
    public function stockHistory($id,ProductRepository $productRepository,ProductHistoryRepository $productHistoryRepository): Response
    {
        $product = $productRepository->find($id);
        $histories = $productHistoryRepository->findBy(['product' => $product], ['createdAt' => 'DESC']);
    
        return $this->render('product/addedStockHistoryShow.html.twig', [
            'histories' => $histories,
            'product' => $product
        ]);
}
#[Route('/product/detail/{id}', name: 'app_product_show_public', methods: ['GET'])]
public function showProd(Product $product, ProductRepository $productRepository): Response
{
    $latestProducts = $productRepository->findBy([], ['id' => 'DESC'], 4);

    return $this->render('home/product_show.html.twig', [
        'product' => $product,
        'latestProducts' => $latestProducts
    ]);
}
#[Route('/filter/subcategory/{id}', name: 'app_product_filtrer')]
public function filtrer(SubCategory $subCategory, CategoryRepository $categoryRepository): Response
{
    return $this->render('home/filtrer.html.twig', [
        'subCategory' => $subCategory,
        'products' => $subCategory->getProducts(), 
        'categories' => $categoryRepository->findAll(),
    ]);
}
}
