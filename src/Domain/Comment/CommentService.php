<?php

namespace App\Domain\Comment;

use App\Domain\Application\Entity\Content;
use App\Domain\Auth\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class CommentService
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public function create(CommentData $data): Comment
    {
        // On crée un nouveau commentaire
        /** @var Content $target */
        $target = $this->em->getRepository(Content::class)->find($data->target);
        /** @var Comment|null $parent */
        $parent = $data->parent ? $this->em->getReference(Comment::class, $data->parent) : null;
        $comment = (new Comment())
            ->setAuthor($this->auth->getUserOrNull())
            ->setUsername($data->username)
            ->setCreatedAt(new \DateTime())
            ->setContent($data->content)
            ->setParent($parent)
            ->setTarget($target);
        $this->em->persist($comment);
        $this->em->flush();
        $this->dispatcher->dispatch(new CommentCreatedEvent($comment));

        return $comment;
    }

    public function update(Comment $comment, string $content): Comment
    {
        $comment->setContent($content);
        $this->em->flush();

        return $comment;
    }

    public function delete(int $commentId): void
    {
        /** @var Comment $comment */
        $comment = $this->em->getReference(Comment::class, $commentId);
        $this->em->remove($comment);
        $this->em->flush();
    }
}
