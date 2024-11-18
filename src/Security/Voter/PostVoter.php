<?php

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class PostVoter extends Voter
{
    public const EDIT = 'POST_EDIT';
    public const VIEW = 'POST_VIEW';
	private $security;
	
	public function __construct(Security $security)
	{
		$this->security = $security;
	}

    protected function supports(string $attribute, $subject): bool
    {
		// if the attribute isn't one we support, return false
		if (!in_array($attribute, [self::VIEW, self::EDIT])) {
			return false;
		}
	
		// only vote on `Station` objects
		if (!$subject instanceof Post) {
			return false;
		}
	
		return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }
	
		// ROLE_ADMIN can do anything!
		if ($this->security->isGranted(User::ROLE_ADMIN)) {
			return true;
		}

		/** @var Post $post */
		$post = $subject;
		
        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
				return $this->canEdit($post, $user);
            case self::VIEW:
				return $this->canView($post, $user);
        }
		
		throw new \LogicException('This code should not be reached!');

    }
	private function canEdit(Post $post, User $user)
	{
		return $user === $post->getAuthor();
	}
	
	private function canView(Post $post, User $user)
	{
		if ($this->canEdit($post, $user)){
			return true;
		}
		return true;
	}
}
