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
     *
     * Retrieves all posts, filtered by published, publication date and tag.
     */
    public function getAll(?int $publishedOnly = 0, ?int $dateOrder = 0, ?string $tag = '') : JsonResponse
    {
        $dateOrder = $dateOrder ? 'ASC' : 'DESC';
        $published = $publishedOnly ? true : false;

        //Use repository method, specially written for required filters
        $posts = $this->getDoctrine()
            ->getRepository(Post::class)
            ->findManyByConditions($tag, $published, $dateOrder);

        $response = [];

        //Formatting response before rendering
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
     *
     * Retrieves one post by id
     */
    public function getOne($id) : JsonResponse
    {
        $post = $this->getDoctrine()
            ->getRepository(Post::class)
            ->find($id);

        //Formatting response before rendering
        $response = $this->formatResponse($post);

        return new JsonResponse([
            'success' => true,
            'error' => null,
            'result' => [
                'posts' => $response,
            ]], Response::HTTP_OK);
    }

    /**
     * @Route("/posts/{id}", name="update_one", methods={"PUT"})
     *
     * Updates specified post with a new data
     */
    public function updateOne($id, EntityManagerInterface $entityManager, Request $request, ValidatorInterface $validator) : JsonResponse
    {
        $post = $this->getDoctrine()
            ->getRepository(Post::class)
            ->find($id);

        //If no post with specified id return not found error
        if (!$post) {
            return new JsonResponse([
                'success' => null,
                'error' => true,
                'result' => 'Post not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $post = $post->updatePost($request->getContent());
        $entityManager->flush();

        //Formatting response before rendering
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
     *
     * Deletes specified post
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
     *
     * Creates post with specifie data
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

        //For saved post we add tags if there are any
        $post = $this->setTags($request->getContent(), $post);

        $adminEmail = $container->getParameter('admin.email');

        //Sending an email that post was created
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

    /**
     * Commonly used formatting helper
     *
     * @param Post $post
     * @return array
     */
    private function formatResponse(Post $post) : array
    {
        return [
            'title' => $post->getTitle(),
            'body' => $post->getBody(),
            'publication_date' => $post->getPublicationDate(),
            'published' => $post->getPublished(),
        ];
    }

    /**
     * Persisting or creating new tags if there's no tag with specified name
     *
     * @param string $requestJson
     * @param Post $post
     * @return Post
     */
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

    /**
     * Wrap email sending
     *
     * @param string $adminEmail
     * @param \Swift_Mailer $mailer
     * @return int
     */
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
