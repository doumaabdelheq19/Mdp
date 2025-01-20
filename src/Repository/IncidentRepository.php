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

class IncidentRepository extends EntityRepository
{
    public function findForUserWithGroup(User $user, User $parentUser = null)
    {
        $qb = $this->createQueryBuilder('i');

        if ($parentUser) {
            $qb->where($qb->expr()->orX(
                "i.user = :user"
                ,
                $qb->expr()->andX(
                    "i.user = :parentUser",
                    "i.group = true"
                )
            ));
        } else {
            $qb->where("i.user = :user");
        }

        $qb->setParameter("user", $user);

        if ($parentUser) {
            $qb->setParameter("parentUser", $parentUser);
        }

        return $qb->getQuery()->getResult();
    }
}