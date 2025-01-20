<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 03/01/2020
 * Time: 09:30
 */

namespace App\Repository;

use App\Entity\Action;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class ActionRepository extends EntityRepository
{
    public function findToTreat(User $user)
    {
        $now = new \DateTime("now");
        $date = clone $now;
        $date->add(new \DateInterval("P30D"));

        $qb = $this->createQueryBuilder('a');

        $qb->where($qb->expr()->orX(
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = false"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser != :user"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser = :user",
                "a.groupUserConcerned = true"
            )
        ))
            ->andWhere("a.setUpDate <= :date");

        $qb->setParameter("user", $user)
            ->setParameter("date", $date)
            ->addOrderBy("a.setUpDate", "ASC");


        return $qb->getQuery()->getResult();
    }

    public function findForUserWithGroup(User $user)
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where($qb->expr()->orX(
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = false"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser != :user"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser = :user",
                "a.groupUserConcerned = true"
            )
        ));

        $qb->setParameter("user", $user)
            ->addOrderBy("a.name", "ASC");


        return $qb->getQuery()->getResult();
    }

    public function findForUserWithGroupNotTerminated(User $user)
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where($qb->expr()->orX(
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = false"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser != :user"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser = :user",
                "a.groupUserConcerned = true"
            )
        ))
            ->andWhere("a.terminated = false");

        $qb->setParameter("user", $user)
            ->addOrderBy("a.priority", "ASC")
            ->addOrderBy("a.date", "ASC");


        return $qb->getQuery()->getResult();
    }

    public function findForUserWithGroupTerminated(User $user)
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where($qb->expr()->orX(
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = false"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser != :user"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser = :user",
                "a.groupUserConcerned = true"
            )
        ))
        ->andWhere("a.terminated = true");

        $qb->setParameter("user", $user)
            ->addOrderBy("a.editDate", "DESC");


        return $qb->getQuery()->getResult();
    }

    public function findGroupsForUser(User $user)
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where($qb->expr()->andX(
            "a.user = :user",
            "a.byGroup = true",
            "a.groupUser = :user"
        ));

        $qb->setParameter("user", $user)
            ->addOrderBy("a.date", "DESC");


        return $qb->getQuery()->getResult();
    }

    public function findGroupsForAction(Action $action)
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where($qb->expr()->orX(
            $qb->expr()->andX(
                "a.user != :user",
                "a.byGroup = true",
                "a.groupUser = :user"
            ),
            $qb->expr()->andX(
                "a.user = :user",
                "a.byGroup = true",
                "a.groupUser = :user",
                "a.groupUserConcerned = true"
            )
        ))
        ->andWhere($qb->expr()->orX(
            "a.groupAction = :action",
            "a.id = :actionId"
        ));

        $qb->setParameter("user", $action->getGroupUser());
        $qb->setParameter("action", $action);
        $qb->setParameter("actionId", $action->getId());

        return $qb->getQuery()->getResult();
    }
}