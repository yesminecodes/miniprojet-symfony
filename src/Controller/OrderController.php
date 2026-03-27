<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Form\OrderType;
use App\Service\CartService;
use App\Service\StripePayment;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

final class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        CartService $cartService,
        StripePayment $stripePayment
    ): Response {
        $cartData = $cartService->getCart($session);
        $totalProducts = $cartData['total'];
        $items = $cartData['items'];

        if (empty($items)) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('app_home');
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setTotalPrice($totalProducts);
            $order->setIsPaymentCompleted(false); 
            $order->setIsCompleted(false); 

            $entityManager->persist($order);
            $entityManager->flush();

            foreach ($items as $item) {
                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($item['product']);
                $orderProduct->setQuantity($item['quantity']);
                $entityManager->persist($orderProduct);
            }
            $entityManager->flush();

            if ($order->isPaidOnDelivery()) {
                $this->sendConfirmationEmail($order);
                $session->remove('cart');
                $this->addFlash('success', 'Order validated (Payment on delivery).');
                $shippingCost = $order->getCity()->getShippingCost();
                $stripeUrl = $stripePayment->startPayment($items, $shippingCost, $order);
            } else {
                $shippingCost = $order->getCity()->getShippingCost();
                $stripeUrl = $stripePayment->startPayment($items, $shippingCost, $order);
                return $this->redirect($stripeUrl);
            }
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $totalProducts
        ]);
    }

    #[Route('/editor/order/show/{type}', name: 'app_order_show', defaults: ['type' => 'all'])]
    public function getAllOrder(
        string $type, 
        Request $request, 
        OrderRepository $orderRepository, 
        PaginatorInterface $paginator
    ): Response {
        
        $criteria = [];
        if ($type === 'is-completed') {
            $criteria = ['isCompleted' => true];
        } elseif ($type === 'pay-online-not-delivered') {
            $criteria = [
                'isPaymentCompleted' => true,
                'paidOnDelivery' => false,
                'isCompleted' => false
            ];
        } elseif ($type === 'pay-online-completed') {
            $criteria = [
                'isPaymentCompleted' => true,
                'isCompleted' => true
            ];
        }

        $data = $orderRepository->findBy($criteria, ['id' => 'DESC']);

        $orders = $paginator->paginate(
            $data, 
            $request->query->getInt('page', 1), 
            10
        );

        return $this->render('order/order.html.twig', [
            'orders' => $orders,
            'currentType' => $type 
        ]);
    }

    #[Route('/editor/order/{id}/update/{type}', name: 'app_order_update')]
    public function updateOrder(
        int $id, 
        string $type, 
        OrderRepository $orderRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $order = $orderRepository->find($id);
        if ($order) {
            $order->setIsCompleted(true);
            $entityManager->flush();
            $this->addFlash('success', 'Order marked as delivered');
        }
        
        return $this->redirectToRoute('app_order_show', ['type' => $type]);
    }

    #[Route('/editor/order/{id}/remove', name: 'app_order_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Order deleted');
        
        return $this->redirectToRoute('app_order_show', ['type' => 'all']);
    }

    #[Route('/order/shipping-cost/{id}', name: 'app_shipping_cost', methods: ['GET'])]
    public function getShippingCost(City $city): Response
    {
        return $this->json([
            'status' => 200,
            'content' => $city->getShippingCost()
        ]);
    }

    private function sendConfirmationEmail(Order $order)
    {
        $html = $this->renderView('email/order_email.html.twig', [
            'order' => $order,
        ]);

        $email = (new Email())
            ->from('myShop@gmail.com')
            ->to($order->getEmail())
            ->subject('Confirmation of your order')
            ->html($html);

        $this->mailer->send($email);
    }
}