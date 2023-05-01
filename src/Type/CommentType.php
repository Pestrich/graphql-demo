<?php

declare(strict_types=1);

namespace App\Type;

use App\Data\Comment;
use App\Data\DataSource;
use App\Data\User;
use App\Type\Field\HtmlField;
use App\Types;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;

class CommentType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Comment',
            'fields' => static fn (): array => [
                'id' => Types::id(),
                'author' => [
                    'type' => Types::user(),
                    'resolve' => static fn (Comment $comment): ?User => $comment->isAnonymous
                        ? null
                        : DataSource::findUser($comment->authorId),
                ],
                'parent' => [
                    'type' => Types::comment(),
                    'resolve' => static fn (Comment $comment): ?Comment => $comment->parentId !== null
                        ? DataSource::findComment($comment->parentId)
                        : null,
                ],
                'isAnonymous' => Types::boolean(),
                'replies' => [
                    'type' => new ListOfType(Types::comment()),
                    'args' => [
                        'after' => Types::int(),
                        'limit' => [
                            'type' => Types::int(),
                            'defaultValue' => 5,
                        ],
                    ],
                    'resolve' => fn (Comment $comment, array $args): array => DataSource::findReplies($comment->id, $args['limit'], $args['after'] ?? null),
                ],
                'totalReplyCount' => [
                    'type' => Types::int(),
                    'resolve' => static fn (Comment $comment): int => DataSource::countReplies($comment->id),
                ],
                'body' => HtmlField::build([
                    'resolve' => static fn (Comment $comment): string => $comment->body,
                ]),
            ],
        ]);
    }
}
