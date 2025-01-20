<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TreatmentStdCategory
 *
 * @ORM\Table(name="treatment_std_category", uniqueConstraints={@ORM\UniqueConstraint(name="treatment_std_category_id_uindex", columns={"id"})})
 * @ORM\Entity
 */
class TreatmentStdCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @param string $libelle
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
    }

    public function __toString()
    {
        return $this->getLibelle();
    }

}

