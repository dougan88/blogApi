<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostController extends AbstractController
{
    /**
     * @Route("/posts/all/{publishedOnly}/{dateOrder}", name="get_all", methods={"GET"})
     */
    public function getAll(?bool $publishedOnly = false, ?int $dateOrder = 0)
    {
        $dateOrder = $dateOrder ? 'ASC' : 'DESC';
        if ($publishedOnly) {
            $posts = $this->getDoctrine()
                ->getRepository(Post::class)
                ->findBy(['published' => true],
                    ['publication_date' => $dateOrder]);

        } else {
            $posts = $this->getDoctrine()
                ->getRepository(Post::class)
                ->findBy([],
                    ['publication_date' => $dateOrder]);
        }

        $response = [];

        foreach ($posts as $post) {
            $response[] = $this->formatResponse($post);
        }

        return new JsonResponse([
            'success' => true,
            'error' => null,
            'result' => [
                'posts' => $response,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/posts/one/{id}", name="get_one", methods={"GET"})
     */
    public function getOne($id)
    {
        $post = $this->getDoctrine()
            ->getRepository(Post::class)
            ->find($id);

        $response = $this->formatResponse($post);

        return new JsonResponse([
            'success' => true,
            'error' => null,
            'result' => [
                'posts' => $response,
            ]], Response::HTTP_OK);
    }

    /**
     * @Route("/posts/{id}", name="update_one", methods={"PUT"})s
     */
    public function updateOne($id, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator)
    {
        $post = $this->getDoctrine()
            ->getRepository(Post::class)
            ->find($id);

        if (!$post) {
            return new JsonResponse([
                'success' => null,
                'error' => true,
                'result' => 'Post not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $post = $post->updatePost($request->getContent());
        $entityManager->flush();

        $response = $this->formatResponse($post);

        return new JsonResponse([
            'success' => true,
            'error' => null,
            'result' => [
                'posts' => $response,
            ]], Response::HTTP_OK);
    }

    /**
     * @Route("/posts/{id}", name="delete_one", methods={"DELETE"})
     */
    public function deleteOne($id, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator)
    {
        $post = $this->getDoctrine()
            ->getRepository(Post::class)
            ->find($id);

        if (!$post) {
            return new JsonResponse([
                'success' => null,
                'error' => true,
                'result' => 'Post not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'error' => null,
            'result' => 'Post was deleted'
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/posts", name="post", methods={"POST"})
     */
    public function createOne(EntityManagerInterface $entityManager, Request $request, Post $post, ValidatorInterface $validator)
    {
        $post = $post->createPost($request->getContent());

        $errors = $validator->validate($post);

        if (count($errors) > 0) {

            return new JsonResponse([
                'success' => null,
                'error' => true,
                'result' => (string) $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($post);

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'error' => null,
            'result' => [
                'id' => $post->getId(),
            ]], Response::HTTP_OK);
    }

    private function formatResponse(Post $post)
    {
        return [
            'title' => $post->getTitle(),
            'body' => $post->getBody(),
            'publication_date' => $post->getPublicationDate(),
            'published' => $post->getPublished(),
        ];
    }
}
