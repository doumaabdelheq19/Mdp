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

class SubcontractorStdRepository extends EntityRepository
{
    public function findForUser(User $user)
    {
        $qb = $this->createQueryBuilder('ss');

        if ($user->getParentUser()) {
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->isNotNull("ss.manager"),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull("ss.user"),
                        $qb->expr()->orX(
                            "ss.user = :user",
                            "ss.user = :parentUser"
                        )
                    )
                )
            )
            ->setParameter("parentUser", $user->getParentUser());
        } else {
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->isNotNull("ss.manager"),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull("ss.user"),
                        "ss.user = :user"
                    )
                )
            );
        }
        $qb->setParameter("user", $user);

        return $qb->getQuery()->getResult();
    }
}