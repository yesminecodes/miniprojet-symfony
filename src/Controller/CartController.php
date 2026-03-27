<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
#[Route('/cart', name: 'app_cart')]
public function index(SessionInterface $session, ProductRepository $productRepository, CartService $cartService): Response
{
    $cartData = $cartService->getCart($session);

    return $this->render('cart/index.html.twig', [
        'items' => $cartData['items'],
        'total' => $cartData['total']
    ]);
}
#[Route('/cart/add/{id}', name: 'app_cart_add')]
public function add($id, SessionInterface $session): Response
{
    $cart = $session->get('cart', []);

    if (!empty($cart[$id])) {
        $cart[$id]++;
    } else {
        $cart[$id] = 1;
    }

    $session->set('cart', $cart);

    return $this->redirectToRoute('app_cart');
}
#[Route('/cart/remove/{id}', name: 'app_cart_remove_product')]
public function removeProduct($id, SessionInterface $session): Response
{
    $cart = $session->get('cart', []);

    if (!empty($cart[$id])) {
        unset($cart[$id]);
    }

    $session->set('cart', $cart); 
    return $this->redirectToRoute('app_cart');
}
#[Route('/cart/clear', name: 'app_cart_clear')]
public function clear(SessionInterface $session): Response
{
    $session->set('cart', []); 
    return $this->redirectToRoute('app_cart');
}
}
