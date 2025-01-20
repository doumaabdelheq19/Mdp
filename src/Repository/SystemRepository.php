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
use function Doctrine\ORM\QueryBuilder;

class SystemRepository extends EntityRepository
{
    public function searchAllForUser(User $user, $terms)
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where("s.user = :user");

        $qb->setParameter("user", $user)
            ->addOrderBy("s.name", "ASC");

        $qb->andWhere("s.name LIKE :terms")
            ->setParameter("terms", "%".$terms."%");

        return $qb->getQuery()->getResult();
    }

    public function searchAllForUserWithGroup(User $user, $terms, User $parentUser = null)
    {
        $qb = $this->createQueryBuilder('s');

        if ($parentUser) {
            $qb->where($qb->expr()->orX(
                "s.user = :user"
                ,
                $qb->expr()->andX(
                    "s.user = :parentUser",
                    "s.group = true"
                )
            ));
        } else {
            $qb->where("s.user = :user");
        }

        $qb->setParameter("user", $user);

        if ($parentUser) {
            $qb->setParameter("parentUser", $parentUser);
        }

        $qb->addOrderBy("s.name", "ASC");

        $qb->andWhere("s.name LIKE :terms")
            ->setParameter("terms", "%".$terms."%");

        return $qb->getQuery()->getResult();
    }

    public function findForUserWithGroup(User $user, User $parentUser = null)
    {
        $qb = $this->createQueryBuilder('s');

        if ($parentUser) {
            $qb->where($qb->expr()->orX(
                "s.user = :user"
                ,
                $qb->expr()->andX(
                    "s.user = :parentUser",
                    "s.group = true"
                )
            ));
        } else {
            $qb->where("s.user = :user");
        }

        $qb->setParameter("user", $user);

        if ($parentUser) {
            $qb->setParameter("parentUser", $parentUser);
        }

        $qb->addOrderBy("s.name", "ASC");

        return $qb->getQuery()->getResult();
    }
}