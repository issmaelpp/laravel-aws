<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case CreateArticle = 'article:create';
    case UpdateOwnArticle = 'article:update-own';
    case UpdateAnyArticle = 'article:update-any';
    case PublishArticle = 'article:publish';
    case DeleteOwnArticle = 'article:delete-own';
    case DeleteAnyArticle = 'article:delete-any';

    case ManageTopics = 'topic:manage';
}
