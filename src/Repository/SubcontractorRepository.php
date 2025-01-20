<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 03/01/2020
 * Time: 09:30
 */

namespace App\Repository;

use App\Entity\SubcontractorType;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class SubcontractorRepository extends EntityRepository
{
    public function findGroupForUser(User $user, SubcontractorType $subcontractorType = null)
    {
        $qb = $this->createQueryBuilder('s');

        if ($user->getParentUser()) {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    $qb->expr()->orX(
                        "s.user = :user",
                        "s.user = :parentUser"
                    )
                )
            )
            ->setParameter("parentUser", $user->getParentUser());
        } else {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    "s.user = :user"
                )
            );
        }
        $qb->andWhere("s.group = true");
        $qb->setParameter("user", $user)
        ->addOrderBy("s.name", "ASC");

        if ($subcontractorType) {
            $qb->andWhere("s.subcontractorType = :subcontractorType");
            $qb->setParameter("subcontractorType", $subcontractorType);
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithGroupForUser(User $user)
    {
        $qb = $this->createQueryBuilder('s');

        if ($user->getParentUser()) {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    $qb->expr()->orX(
                        "s.user = :user",
                        "s.user = :parentUser"
                    )
                )
            )
                ->setParameter("parentUser", $user->getParentUser());
        } else {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    "s.user = :user"
                )
            );
        }
        $qb->setParameter("user", $user)
            ->addOrderBy("s.name", "ASC");

        return $qb->getQuery()->getResult();
    }

    public function findExistingGroupForUserAndTerms(User $user, $terms)
    {
        $qb = $this->createQueryBuilder('s');

        if ($user->getParentUser()) {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    $qb->expr()->orX(
                        "s.user = :user",
                        "s.user = :parentUser"
                    )
                )
            )
                ->setParameter("parentUser", $user->getParentUser());
        } else {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    "s.user = :user"
                )
            );
        }
        $qb->andWhere("s.group = true");
        $qb->setParameter("user", $user)
        ->andWhere("s.name LIKE :terms")
        ->setParameter("terms", "%".$terms."%")
        ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllForUser(User $user)
    {
        $qb = $this->createQueryBuilder('s');

        if ($user->getParentUser()) {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    $qb->expr()->orX(
                        "s.user = :user",
                        $qb->expr()->andX(
                            "s.user = :parentUser",
                            "s.group = true"
                        )
                    )
                )
            )
                ->setParameter("parentUser", $user->getParentUser());
        } else {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    "s.user = :user"
                )
            );
        }

        $qb->setParameter("user", $user)
            ->addOrderBy("s.subcontractorType", "ASC")
            ->addOrderBy("s.name", "ASC");

        return $qb->getQuery()->getResult();
    }

    public function searchAllForUser(User $user, $terms)
    {
        $qb = $this->createQueryBuilder('s');

        if ($user->getParentUser()) {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    $qb->expr()->orX(
                        "s.user = :user",
                        $qb->expr()->andX(
                            "s.user = :parentUser",
                            "s.group = true"
                        )
                    )
                )
            )
                ->setParameter("parentUser", $user->getParentUser());
        } else {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull("s.user"),
                    "s.user = :user"
                )
            );
        }

        $qb->setParameter("user", $user)
            ->addOrderBy("s.subcontractorType", "ASC")
            ->addOrderBy("s.name", "ASC");

        $qb->andWhere("s.name LIKE :terms")
            ->setParameter("terms", "%".$terms."%");

        return $qb->getQuery()->getResult();
    }
}