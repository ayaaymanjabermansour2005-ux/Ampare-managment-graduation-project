<?php

namespace App\Policies;

use App\Models\User;

class ArticleCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->can('article-comments.view');
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin() && $user->can('article-comments.moderate');
    }
}
