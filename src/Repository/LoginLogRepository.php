<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class LoginLogRepository extends EntityRepository
{
    public function findLast3Months(User $user)
    {
        $now = new \DateTime("now");
        $now->sub(new \DateInterval("P3M"));

        $qb = $this->createQueryBuilder('ll');

        $qb->leftJoin("ll.account", "acc")
            ->leftJoin("acc.subuser", "su")
            ->where(
                $qb->expr()->orX(
                    "acc.user = :user",
                    "su.user = :user"
                )
            )
            ->andWhere("ll.date >= :beginDate")
            ->setParameter("user", $user)
            ->setParameter("beginDate", $now)
            ->addOrderBy("ll.date", "DESC");

        return $qb->getQuery()->getResult();
    }

}