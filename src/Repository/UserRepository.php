<?php

namespace App\Repository;

use App\Entity\User;
use App\Pagination\PaginatedResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Met à jour (rehash) automatiquement le mot de passe de l'utilisateur.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère les invités actifs avec leurs médias en une seule requête (évite le N+1).
     *
     * @return list<User>
     */
    public function findActiveGuestsWithMedias(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->where('u.admin = false')
            ->andWhere('u.active = true')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PaginatedResult<User>
     */
    public function paginateGuests(int $page, int $perPage): PaginatedResult
    {
        $criteria = ['admin' => false];

        return new PaginatedResult(
            $this->findBy($criteria, ['id' => 'ASC'], $perPage, $perPage * ($page - 1)),
            $page,
            $perPage,
            $this->count($criteria),
        );
    }
}
