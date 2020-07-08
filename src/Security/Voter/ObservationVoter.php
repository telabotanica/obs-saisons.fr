<?php

namespace App\Security\Voter;

use App\Entity\Observation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ObservationVoter extends Voter
{
    // these strings are just invented: you can use anything
    const EDIT = 'observation:edit';
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (self::EDIT !== $attribute) {
            return false;
        }

        // only vote on `Observation` objects
        if (!$subject instanceof Observation) {
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

        // $subject is a Observation object
        /** @var Observation $observation */
        $observation = $subject;

        if (
            $this->security->isGranted(
                StationVoter::EDIT,
                $observation->getIndividual()->getStation()
            )
        ) {
            return true;
        }

        if (self::EDIT === $attribute) {
            return $this->canEdit($observation, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(Observation $observation, User $user)
    {
        return $user === $observation->getUser();
    }
}
