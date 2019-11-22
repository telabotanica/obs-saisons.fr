<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use App\Entity\User;

class UserVoter extends Voter
{
    const LOGGED = 'user:logged';
    const REGISTER = 'user:register';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::LOGGED, self::REGISTER]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        switch ($attribute) {
            case self::LOGGED:
                return ($user instanceof User);
                break;
            case self::REGISTER:
                return !($user instanceof User);
                break;
        }

        return false;
    }
}
