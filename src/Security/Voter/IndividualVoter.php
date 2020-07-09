<?php

namespace App\Security\Voter;

use App\Entity\Individual;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class IndividualVoter extends Voter
{
    // these strings are just invented: you can use anything
    const EDIT = 'individual:edit';
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

        // only vote on `Individual` objects
        if (!$subject instanceof Individual) {
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

        // $subject is a Individual object
        /** @var Individual $individual */
        $individual = $subject;

        if (
           $this->security->isGranted(
               StationVoter::EDIT,
               $individual->getStation()
           )
        ) {
            return true;
        }

        if (self::EDIT === $attribute) {
            return $this->canEdit($individual, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(Individual $individual, User $user)
    {
        return $user === $individual->getUser();
    }
}
