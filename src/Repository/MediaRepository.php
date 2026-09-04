<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Pagination\PaginatedResult;
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

    /**
     * @return PaginatedResult<Media>
     */
    public function paginateForUser(?User $user, int $page, int $perPage): PaginatedResult
    {
        $criteria = null === $user ? [] : ['user' => $user];

        return new PaginatedResult(
            $this->findBy($criteria, ['id' => 'ASC'], $perPage, $perPage * ($page - 1)),
            $page,
            $perPage,
            $this->count($criteria),
        );
    }
}
