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

    /*public function findEspeceDataToDisplayInStation(Espece $espece): array
    {
        $periods = [];

        $evenementsEspeces = $this->getEntityManager()
            ->getRepository(EvenementEspece::class)
            ->findBy(['espece' => $espece], ['espece' => 'asc'])
        ;
        $loopStadeBbch = [];
        foreach ($evenementsEspeces as $evenementEspece) {
            $evenement = $evenementEspece->getEvenement();
            $evenementNom = Evenement::DISPLAY_LABELS[$evenement->getNom()];
            $StadeBbch = $evenement->getStadeBbch();

            if (!in_array($evenementNom, array_keys($periods))) {
                $loopStadeBbch[] = $StadeBbch;
                $periods[$evenementNom] = [
                    'begin' => $evenementEspece->getDateDebut(),
                    'end' => $evenementEspece->getDateFin(),
                ];
            }
            if (!in_array($StadeBbch, $loopStadeBbch)) {
                $loopStadeBbch[] = $StadeBbch;
                // the earliest begining date
                if ($evenementEspece->getDateDebut() < $periods[$evenementNom]['begin']) {
                    $periods[$evenementNom]['begin'] = $evenementEspece->getDateDebut();
                }
                // the latest ending date
                if ($evenementEspece->getDateFin() > $periods[$evenementNom]['end']) {
                    $periods[$evenementNom]['end'] = $evenementEspece->getDateFin();
                }
            }
        }

        return [
            'espece' => $espece,
            'periods' => $periods,
            'individuals' => [],
        ];
    }*/

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
