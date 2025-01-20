<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 03/01/2020
 * Time: 09:30
 */

namespace App\Repository;

use App\Entity\Training;
use App\Entity\TrainingCampain;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class TrainingCampainRepository extends EntityRepository
{
    public function countTotalSensibilizedForUser(User $user)
    {
        $qb = $this->createQueryBuilder('tc');

        $qb->select("SUM(tc.emailsCount) as sumEmails")
            ->where("tc.user = :user")
            ->andWhere("tc.traineeship = true")
            ->andWhere("tc.external = false")
            ->setParameter("user", $user);

        return $qb->getQuery()->getSingleScalarResult();
    }
}