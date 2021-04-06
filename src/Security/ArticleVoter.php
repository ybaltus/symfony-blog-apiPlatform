<?php


namespace App\Security;


use App\Entity\Article;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ArticleVoter extends Voter
{
    const ARTICLE_EDIT = 'ARTICLE_EDIT';
    const ARTICLE_DELETE = 'ARTICLE_DELETE';

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {

        $supportAttribute = in_array($attribute, [self::ARTICLE_EDIT, self::ARTICLE_DELETE]);
        $supportSubject = $subject instanceof Article;

        return $supportAttribute && $supportSubject;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        switch ($attribute){
            case 'ARTICLE_EDIT':
            case 'ARTICLE_DELETE':
                if($this->security->isGranted('ROLE_ADMIN') || $token->getUser() === $subject->getAuthor()){
                    return true;
                }
                break;
            default:
                return false;
        }
    }
}
