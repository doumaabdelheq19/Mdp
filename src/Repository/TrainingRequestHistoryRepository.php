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

class TrainingRequestHistoryRepository extends EntityRepository
{
    public function findForTraining(Training $training, User $user = null)
    {
        $qb = $this->createQueryBuilder('trh');

        $qb->leftJoin("trh.trainingRequest", "tr")
            ->leftJoin("tr.trainingCampain", "tc")
            ->where("tc.training = :training")
            ->setParameter("training", $training);

        if ($user) {
            $qb->andWhere("tr.user = :user")
                ->setParameter("user", $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function avgNoteForUser(User $user)
    {
        $qb = $this->createQueryBuilder('trh');

        $qb->select("AVG(trh.result) as avgNote")
            ->leftJoin("trh.trainingRequest", "tr")
            ->leftJoin("tr.trainingCampain", "tc")
            ->where("tc.user = :user")
            ->andWhere("tc.external = false")
            ->setParameter("user", $user);

        return $qb->getQuery()->getSingleScalarResult();
    }
}