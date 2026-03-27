<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class StripeController extends AbstractController
{
    #[Route('/stripe/success', name: 'app_stripe_success')]
    public function success(Request $request): Response
    {
        $request->getSession()->remove('cart');
        return $this->render('stripe/success.html.twig');
    }

    #[Route('/stripe/reject', name: 'app_stripe_reject')]
    public function reject(): Response
    {
        return $this->render('stripe/reject.html.twig');
    }

    #[Route('/stripe/notify', name: 'app_stripe_notify', methods: ['POST'])]
    public function notify(
        Request $request, 
        OrderRepository $orderRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        Stripe::setApiKey($_ENV['STRIPE_SECRET']);
        $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        $payLoad = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payLoad, $sigHeader, $endpoint_secret);
        } catch (\Exception $e) {
            return new Response('Signature fail', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;

            if ($orderId) {
                $order = $orderRepository->find($orderId);

                if ($order) {
                    $order->setIsPaymentCompleted(true);
                    
                    $order->setPaidOnDelivery(false); 
                    
                    $entityManager->flush();

                    file_put_contents('stripe_success_log.txt', "Order {$orderId} paid at " . date('H:i:s') . "\n", FILE_APPEND);
                }
            }
        }

        return new Response('Webhook received and processed', 200);
    }
}