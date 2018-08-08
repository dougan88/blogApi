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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PostController extends Controller
{
    /**
     * @Route("/posts", name="post")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PostController.php',
        ]);
    }

    /**
     * @Route("/posts", name="post", methods={"POST"})
     */
    public function create(EntityManagerInterface $entityManager, Request $request, Post $post, ValidatorInterface $validator)
    {
        $postData = $this->getPostDataFromRequest($request->getContent());
        $post->setTitle($postData['title']);
        $post->setBody($postData['body']);
        $post->setPublished($postData['published']);
        $post->setPublicationDate($postData['publication_date']);
//        $post->addTag($postData['tag']);

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
            ], Response::HTTP_OK,
        ]);
    }

    private function getPostDataFromRequest(string $requestJson)
    {
        $postData = json_decode($requestJson);

        return [
            'title' => $postData->title ?? '',
            'body' => $postData->body ?? '',
            'published' => $postData->published ?? false,
            'publication_date' => $postData->publication_date ?? new \DateTime(),
            'tag' => $postData->tag ?? ''
        ];
    }
}