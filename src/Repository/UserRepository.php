<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        $user->setPassword($newEncodedPassword);
        try {
            $this->getEntityManager()->flush($user);
        } catch (\Throwable $e) {
            // ignore failures, we don't want to interrupt login and stuff
        }
    }

    public function findByRole(string $role, int $maxResults = 1)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->select('u')
            ->andWhere($qb->expr()->like('u.roles', ':role'))
            ->setParameter(':role', '%'.$role.'%')
            ->orderBy('u.createdAt', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
        ;
    }
	
	public function findNewMembersPerYear(int $year)
	{
		$qb = $this->createQueryBuilder('u')
			->where('YEAR(u.createdAt) = :year')
			->andWhere('u.roles NOT LIKE :role')
			->andWhere('u.status = 1')
			->setParameter('year', $year)
			->setParameter('role','%ROLE_ADMIN')
			->getQuery()
			->getResult()
			;
		return count($qb);
	}

    public function findTotalMembersPerStatus()
    {
        $qb = $this->createQueryBuilder('u');

        $result = $qb
            ->select('
                CASE 
                    WHEN u.status = 0 THEN \'désactivé\'
                    WHEN u.status = 1 THEN \'actif\'
                    WHEN u.status = 2 THEN \'pas encore activé\'
                    ELSE \'Autre\'
                END as status_du_compte,
                COUNT(u) as NbreUtilisateurs
            ')
            ->where('u.deletedAt IS NULL')
            ->andWhere('u.roles NOT LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->groupBy('status_du_compte')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findAllActiveMembers(){
        $usersQuery ='';
        try{
            //Requete afin de récupérer les différente utilisateurs pour le filtrage
            $usersQuery = $this->createQueryBuilder('u')
                ->select('partial u.{id, name}')
                ->where('u.status = :status')
                ->orderBy('u.id', 'ASC')
                ->setParameter('status', 1);
        }catch (\Exception $exception){
            echo 'An error occurd --> ' . $exception;
        }


        return $usersQuery->getQuery()->getResult();
    }


}
