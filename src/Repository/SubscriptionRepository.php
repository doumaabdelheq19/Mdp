<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 03/01/2020
 * Time: 09:30
 */

namespace App\Repository;

use App\Entity\Manager;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class SubscriptionRepository extends EntityRepository
{
    public function findExpired(\DateTime $now)
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where("s.active = true")
            ->andWhere("s.endDate < :now")
            ->setParameter("now", $now);

        return $qb->getQuery()->getResult();
    }

    public function findNearlyExpired(\DateTime $now)
    {
        $expirationDate = clone $now;
        $expirationDate->add(new \DateInterval("P30D"));

        $qb = $this->createQueryBuilder('s');

        $qb->where("s.active = true")
            ->andWhere("s.endDate LIKE :expirationDate")
            ->setParameter("expirationDate", $expirationDate->format("Y-m-d")."%");

        return $qb->getQuery()->getResult();
    }
}