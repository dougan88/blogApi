<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Tag;
use Psr\Container\ContainerInterface;
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
     * @Route("/posts/all/{publishedOnly}/{dateOrder}/{tag}", name="get_all", methods={"GET"})
     */
    public function getAll(?int $publishedOnly = 0, ?int $dateOrder = 0, ?string $tag = '') : JsonResponse
    {
        $dateOrder = $dateOrder ? 'ASC' : 'DESC';
        $published = $publishedOnly ? true : false;

        $posts = $this->getDoctrine()
            ->getRepository(Post::class)
            ->findManyByTagName($tag, $published, $dateOrder);

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
    public function getOne($id) : JsonResponse
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
    public function updateOne($id, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator) : JsonResponse
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
    public function deleteOne($id, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator) : JsonResponse
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
    public function createOne(EntityManagerInterface $entityManager, Request $request, Post $post, ValidatorInterface $validator, ContainerInterface $container,\Swift_Mailer $mailer) : JsonResponse
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

        $post = $this->setTags($request->getContent(), $post);

        $adminEmail = $container->getParameter('admin.email');

        if ($adminEmail) {
            $this->sendCreatedEmail($adminEmail, $mailer);
        }

        return new JsonResponse([
            'success' => true,
            'error' => null,
            'result' => [
                'id' => $post->getId(),
            ]], Response::HTTP_OK);
    }

    private function formatResponse(Post $post) : array
    {
        return [
            'title' => $post->getTitle(),
            'body' => $post->getBody(),
            'publication_date' => $post->getPublicationDate(),
            'published' => $post->getPublished(),
        ];
    }

    private function setTags(string $requestJson, Post $post) : Post
    {
        $postData = json_decode($requestJson);

        $tagsList = $postData->tag ?? '';

        if (!empty($tagsList) && is_array($tagsList)) {

            $entityManager = $this->getDoctrine()->getManager();

            foreach ($tagsList as $tagName) {

                $tag = $this->getDoctrine()
                    ->getRepository(Tag::class)
                    ->findOneBy(['name'=>$tagName]);

                if (!$tag) {
                    $tag = new Tag();
                    $tag->setName($tagName);
                }
                $entityManager->persist($tag);
                $entityManager->flush();

                $post->addTag($tag);
                $entityManager->persist($post);
                $entityManager->flush();
            }
        }

        return $post;
    }

    private function sendCreatedEmail(string $adminEmail, \Swift_Mailer $mailer)
    {
        $message = (new \Swift_Message('Post Created'))
            ->setFrom('no-reply@example.com')
            ->setTo($adminEmail)
            ->setBody(
                'Post created',
                'text/html'
            );

        return $mailer->send($message);
    }
}
