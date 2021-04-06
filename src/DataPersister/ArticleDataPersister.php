<?php


namespace App\DataPersister;


use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Article;
use App\Entity\Tag;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class ArticleDataPersister implements ContextAwareDataPersisterInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Slugify
     */
    private $slugger;

    /**
     * @var Request
     */
    private $request;
    /**
     * @var Security
     */
    private $security;


    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, Security $security){
        $this->em = $em;
        $this->slugger = new Slugify();
        $this->request = $requestStack->getCurrentRequest();
        $this->security = $security;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Article;
    }

    /**
     * @param Article $data
     * @param array $context
     * @return object|void
     */
    public function persist($data, array $context = [])
    {
        $httpMethod = $this->request->getMethod();

        // Update the slug only if the article isn't published
        if(!$data->getIsPublished()){
            $data->setSlug($this->slugger->slugify($data->getTitle()));
        }

        // Set the updatedAt value if it's not a POST request
        if($httpMethod !== 'POST'){
            $data->setUpdatedAt(new \DateTime());
        }

        //Set author
        if($httpMethod === 'POST'){
            $data->setAuthor($this->security->getUser());
        }

        //Check if a tag exist
        $tagRepository = $this->em->getRepository(Tag::class);
        foreach ($data->getTags() as $tagData){
            $slug = $this->slugger->slugify($tagData->getLabel());
            $tag = $tagRepository->findOneBySlug($slug);

            if($tag){
               $data->removeTag($tagData);
               $data->addTag($tag);
            }else{
                $tagData->setSlug($slug);
                $this->em->persist($tagData);
            }
        }

        $this->em->persist($data);
        $this->em->flush();
    }

    public function remove($data, array $context = [])
    {
        $this->em->remove($data);
        $this->em->flush();
    }
}
