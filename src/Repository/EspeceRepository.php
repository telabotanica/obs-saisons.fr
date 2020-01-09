<?php

namespace App\Repository;

use App\Entity\Espece;
use App\Entity\Evenement;
use App\Entity\EvenementEspece;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Espece|null find($id, $lockMode = null, $lockVersion = null)
 * @method Espece|null findOneBy(array $criteria, array $orderBy = null)
 * @method Espece[]    findAll()
 * @method Espece[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EspeceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Espece::class);
    }

    public function findDataToDisplayInStation(int $id): array
    {
        $manager = $this->getEntityManager();

        $evenementsEspeces = $manager->getRepository(EvenementEspece::class)
            ->findBy(['espece' => $id], ['espece' => 'asc'])
        ;
        $loopEvenementNom = [];
        $loopStadeBbch = [];
        $stades = [];
        foreach ($evenementsEspeces as $evenementEspece) {
            $evenement = $manager->getRepository(Evenement::class)
                ->find($evenementEspece->getEvenement())
            ;
            $evenementNom = ucfirst($evenement->getNom());
            $StadeBbch = $evenement->getStadeBbch();

            if (!in_array($evenementNom, $loopEvenementNom)) {
                $loopEvenementNom[] = $evenementNom;
                $loopStadeBbch[] = $StadeBbch;
                $stades[$evenementNom] = [date_format($evenementEspece->getDateDebut(), 'Y-m-d')];
            }
            if (in_array($evenementNom, ['1ere apparition', 'Fructification']) || !in_array($StadeBbch, $loopStadeBbch)) {
                array_push($stades[$evenementNom], date_format($evenementEspece->getDateFin(), 'Y-m-d'));
            }
        }
        $espece = $this->find($id);

        return [
            'name' => $espece->getNomVernaculaire(),
            'scientific_name' => $espece->getNomScientifique(),
            'image' => $espece->getPhoto(),
            'periods' => $stades,
            'individuals' => [],
        ];
    }

    // /**
    //  * @return Espece[] Returns an array of Espece objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Espece
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
