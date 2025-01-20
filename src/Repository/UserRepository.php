<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 03/01/2020
 * Time: 09:30
 */

namespace App\Repository;

use App\Entity\Manager;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findForManager(Manager $manager)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->where($qb->expr()->orX(
            "u.manager = :manager",
            "u.lawyer = :manager"
        ))
            ->setParameter("manager", $manager);

        return $qb->getQuery()->getResult();
    }
}