<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof User) {
            return;
        }

        if (User::STATUS_DISABLED === $user->getStatus()) {
            throw new CustomUserMessageAuthenticationException('Cet utilisateur est désactivé.');
        }

        if (User::STATUS_PENDING === $user->getStatus()) {
            throw new CustomUserMessageAuthenticationException('Cet utilisateur n’a pas encore été activé.');
        }

        if (User::STATUS_DELETED === $user->getStatus()) {
            throw new CustomUserMessageAuthenticationException('Cet utilisateur a été supprimé.');
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
    }
}
