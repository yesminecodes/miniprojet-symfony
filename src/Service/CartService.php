<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;

class CartService
{
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getCart(SessionInterface $session): array
    {
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if (!$product) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'quantity' => $quantity
            ];

            $total += $product->getPrice() * $quantity;
        }

        return [
            'items' => $items,
            'total' => $total
        ];
    }
}