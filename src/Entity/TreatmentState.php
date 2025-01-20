<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TreatmentState
 *
 * @ORM\Table(name="treatment_state", uniqueConstraints={@ORM\UniqueConstraint(name="treatment_state_id_uindex", columns={"id"})})
 * @ORM\Entity
 */
class TreatmentState
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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLibelle(): string
    {
        return $this->libelle;
    }

    /**
     * @param string $libelle
     */
    public function setLibelle(string $libelle)
    {
        $this->libelle = $libelle;
    }

    public function __toString()
    {
        return $this->getLibelle();
    }
}

