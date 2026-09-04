<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Médias du portfolio public : ceux de l'album donné, ou ceux de l'admin par défaut.
     *
     * @return list<Media>
     */
    public function findPortfolioMedias(?Album $album): array
    {
        if ($album) {
            return $this->findBy(['album' => $album]);
        }

        return $this->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->where('u.admin = true')
            ->getQuery()
            ->getResult();
    }
}
