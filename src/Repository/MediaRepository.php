<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Médias du portfolio public : ceux de l'album donné, ou ceux de l'admin par défaut.
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
