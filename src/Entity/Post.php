<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 */
class Post
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $body;

    /**
     * @ORM\Column(type="boolean")
     */
    private $published;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     */
    private $publication_date;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tag", inversedBy="posts")
     */
    private $tag;

    public function __construct()
    {
        $this->tag = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publication_date;
    }

    public function setPublicationDate(\DateTimeInterface $publication_date): self
    {
        $this->publication_date = $publication_date;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTag(): Collection
    {
        return $this->tag;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tag->contains($tag)) {
            $this->tag[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tag->contains($tag)) {
            $this->tag->removeElement($tag);
        }

        return $this;
    }

    /**
     * Standalone function for updating post, placed in Entity as a piece of business logic
     *
     * @param string $requestJson
     * @return Post
     */
    public function updatePost(string $requestJson) : Post
    {
        $postData = json_decode($requestJson);

        if (!empty($postData->title)) {
            $this->setTitle($postData->title);
        }

        if (!empty($postData->body)) {
            $this->setBody($postData->body);
        }

        if (!empty($postData->published)) {
            $this->setPublished($postData->published);
        }

        if (!empty($postData->publication_date)) {
            $this->setPublicationDate(new \DateTime($postData->publication_date));
        }

        return $this;
    }

    /**
     * Standalone function for creating post, placed in Entity as a piece of business logic
     *
     * @param string $requestJson
     * @return Post
     */
    public function createPost(string $requestJson) : Post
    {
        $postData = $this->getPostDataFromRequest($requestJson);

        $this->setTitle($postData['title']);
        $this->setBody($postData['body']);
        $this->setPublished($postData['published']);
        $this->setPublicationDate($postData['publication_date']);

        return $this;
    }

    /**
     * Retrieves data structure which is ready to use for post creation
     *
     * @param string $requestJson
     * @return array
     */
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
