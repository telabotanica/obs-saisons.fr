<?php

namespace App\Security\Voter;

use App\Entity\Station;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class StationVoter extends Voter
{
    // these strings are just invented: you can use anything
    const EDIT = 'station:edit';
    const CONTRIBUTE = 'station:contribute';
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::CONTRIBUTE, self::EDIT])) {
            return false;
        }

        // only vote on `Station` objects
        if (!$subject instanceof Station) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // ROLE_ADMIN can do anything!
        if ($this->security->isGranted(User::ROLE_ADMIN)) {
            return true;
        }

        // $subject is a Station object
        /** @var Station $station */
        $station = $subject;

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($station, $user);
            case self::CONTRIBUTE:
                return $this->canContribute($station, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(Station $station, User $user)
    {
        return $user === $station->getUser();
    }

    private function canContribute(Station $station, User $user)
    {
        if ($this->canEdit($station, $user)) {
            return true;
        }

        return !$station->getIsPrivate();
    }
}
