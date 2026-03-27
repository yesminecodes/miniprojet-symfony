<?php
namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Entity\Order; 

class StripePayment
{
    private $stripeSecret;

    public function __construct()
    {
        $this->stripeSecret = $_ENV['STRIPE_SECRET'] ?? $_SERVER['STRIPE_SECRET'];
        Stripe::setApiKey($this->stripeSecret);
    }

    /**
     * @param array $cart 
     * @param float $shippingCost 
     * @param Order $order 
     */
    public function startPayment($cart, $shippingCost, Order $order)
    {
        $products = [];

        foreach ($cart as $item) {
            $products[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['product']->getName(),
                    ],
                    'unit_amount' => (int)($item['product']->getPrice()*100), 
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $products[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Frais de livraison',
                ],
                'unit_amount' => (int)($shippingCost*100),
            ],
            'quantity' => 1,
        ];

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $products,
            'mode' => 'payment',
            'success_url' => 'http://localhost:8000/stripe/success',
            'cancel_url' => 'http://localhost:8000/stripe/reject',
            'metadata' => [
                'order_id' => $order->getId(),
            ],
        ]);

        return $session->url;
    }
}