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

class TreatmentStdRepository extends EntityRepository
{
    public function findAllOrdered()
    {
        $qb = $this->createQueryBuilder('t');

        $qb->where($qb->expr()->isNull("t.user"));

        $qb->leftJoin("t.category", "c")
            ->addSelect('(CASE WHEN c.id = 17 THEN 1 ELSE 0 END) AS HIDDEN ordCol')
            ->addOrderBy('ordCol', 'ASC')
            ->addOrderBy('c.libelle', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAllForGroup(User $user, User $parentUser = null)
    {
        $qb = $this->createQueryBuilder('t');

        if ($parentUser) {
            $qb->where($qb->expr()->orX(
                "t.user = :user",
                "t.user = :parentUser"
            ));
        } else {
            $qb->where("t.user = :user");
        }

        $qb->addOrderBy('t.name', 'ASC');

        $qb->setParameter("user", $user);

        if ($parentUser) {
            $qb->setParameter("parentUser", $parentUser);
        }

        return $qb->getQuery()->getResult();
    }
}