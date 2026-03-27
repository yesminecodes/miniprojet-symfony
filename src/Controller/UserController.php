<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin/user/{id}/to/editor', name: 'app_user_to_editor')]
public function changeRoleToEditor(EntityManagerInterface $entityManager, UserRepository $userRepository, $id): Response
{
    $user = $userRepository->find($id);

    $user->setRoles(['ROLE_EDITOR', 'ROLE_USER']);
    $entityManager->flush();

    $this->addFlash('success', 'User role updated successfully!');
    return $this->redirectToRoute('app_user');
}

    #[Route('/admin/user/{id}/remove/editor/role', name: 'app_user_remove_editor')]
    public function removeEditorRole(EntityManagerInterface $entityManager, UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);

        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('danger', 'User role updated successfully!');
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove', name: 'app_user_remove')]
    public function userRemove(EntityManagerInterface $entityManager, UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', 'User removed successfully!');
        return $this->redirectToRoute('app_user');
    }
}
