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

class TrainingRequestRepository extends EntityRepository
{
    public function countTotalForUser(User $user)
    {
        $qb = $this->createQueryBuilder('tr');

        $qb->select("COUNT(tr.id) as total")
            ->leftJoin("tr.trainingCampain", "tc")
            ->where("tc.user = :user")
            ->andWhere("tc.external = false")
            ->setParameter("user", $user);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function avgNoteForUser(User $user)
    {
        $qb = $this->createQueryBuilder('tr');

        $qb->select("AVG(tr.result) as avgNote")
            ->leftJoin("tr.trainingCampain", "tc")
            ->where("tc.user = :user")
            ->setParameter("user", $user);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countAnsweredForUser(User $user)
    {
        $qb = $this->createQueryBuilder('tr');

        $qb->select("COUNT(tr.id) as total")
            ->leftJoin("tr.trainingCampain", "tc")
            ->where("tc.user = :user")
            ->andWhere($qb->expr()->isNotNull("tr.answerDate"))
            ->andWhere("tc.external = false")
            ->setParameter("user", $user);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findForTraining(Training $training)
    {
        $qb = $this->createQueryBuilder('tr');

        $qb->leftJoin("tr.trainingCampain", "tc")
            ->where("tc.training = :training")
            ->setParameter("training", $training);

        return $qb->getQuery()->getResult();
    }
}