<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 03/01/2020
 * Time: 09:30
 */

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class ExercisingClaimRequestRepository extends EntityRepository
{
    public function findForReviving(\DateTime $date)
    {
        $firstRevive = clone $date;
        $secondRevive = clone $date;

        $firstRevive->sub(new \DateInterval("P15D"));
        $secondRevive->sub(new \DateInterval("P27D"));

        $qb = $this->createQueryBuilder('ecr');

        $qb->where($qb->expr()->isNotNull("ecr.requestDate"))
            ->andWhere($qb->expr()->isNull("ecr.answerDate"))
            ->andWhere(
                $qb->expr()->orX(
                    "ecr.requestDate LIKE :firstRevive",
                    "ecr.requestDate LIKE :secondRevive"
                )
            )
            ->setParameter("firstRevive", $firstRevive->format("Y-m-d")."%")
            ->setParameter("secondRevive", $secondRevive->format("Y-m-d")."%");

        return $qb->getQuery()->getResult();
    }
}