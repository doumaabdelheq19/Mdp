<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrainingTeam
 *
 * @ORM\Table(name="training_team", uniqueConstraints={@ORM\UniqueConstraint(name="training_team_id_uindex", columns={"id"})})
 * @ORM\Entity
 */
class TrainingTeam
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
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email_addresses", type="text", length=65535, nullable=true)
     */
    private $emailAddresses;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="TrainingCampain", mappedBy="teams")
     */
    private $campains;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->campains = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getEmailAddresses()
    {
        return $this->emailAddresses;
    }

    /**
     * @param string|null $emailAddresses
     */
    public function setEmailAddresses(?string $emailAddresses)
    {
        $this->emailAddresses = $emailAddresses;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampains()
    {
        return $this->campains;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $campains
     */
    public function setCampains($campains): void
    {
        $this->campains = $campains;
    }

    public function __toString()
    {
        return $this->getName();
    }

}

