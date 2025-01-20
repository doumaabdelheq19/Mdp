<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * System
 *
 * @ORM\Table(name="system", uniqueConstraints={@ORM\UniqueConstraint(name="system_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="system_user_id_fk", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\SystemRepository")
 * @Gedmo\Loggable
 */
class System
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
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="array", nullable=true)
     * @Gedmo\Versioned
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="subtype", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $subtype;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`group`", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $group = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`auto_apply_to_treatments`", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $autoApplyToTreatments = false;

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
     * @ORM\ManyToMany(targetEntity="Treatment", mappedBy="systems")
     */
    private $treatments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->treatments = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param string $subtype
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
    }

    /**
     * @return bool
     */
    public function isGroup(): bool
    {
        return $this->group;
    }

    /**
     * @param bool $group
     */
    public function setGroup(bool $group): void
    {
        $this->group = $group;
    }

    /**
     * @return bool
     */
    public function isAutoApplyToTreatments()
    {
        return $this->autoApplyToTreatments;
    }

    /**
     * @param bool $autoApplyToTreatments
     */
    public function setAutoApplyToTreatments(bool $autoApplyToTreatments)
    {
        $this->autoApplyToTreatments = $autoApplyToTreatments;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTreatments()
    {
        return $this->treatments;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $treatments
     */
    public function setTreatments($treatments)
    {
        $this->treatments = $treatments;
    }


}

