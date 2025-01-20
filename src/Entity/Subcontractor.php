<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Subcontractor
 *
 * @ORM\Table(name="subcontractor", uniqueConstraints={@ORM\UniqueConstraint(name="subcontractor_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="subcontractor_user_id_fk", columns={"user_id"}), @ORM\Index(name="subcontractor_conformity_id_fk", columns={"conformity_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\SubcontractorRepository")
 * @Gedmo\Loggable
 */
class Subcontractor
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
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_first_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $contactFirstName;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_last_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $contactLastName;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_phone", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $contactPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_email", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $contactEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="privacy_policy_link", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $privacyPolicyLink;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="edit_date", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $editDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`group`", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $group = false;

    /**
     * @var Conformity
     *
     * @ORM\ManyToOne(targetEntity="Conformity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="conformity_id", referencedColumnName="id")
     * })
     * @Gedmo\Versioned
     */
    private $conformity;

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
     * @var SubcontractorType
     *
     * @ORM\ManyToOne(targetEntity="SubcontractorType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * })
     */
    private $subcontractorType;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="UserDocument", mappedBy="subcontractor")
     */
    private $documents;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Treatment", mappedBy="subcontractors")
     */
    private $treatments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->documents = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function getContactFirstName()
    {
        return $this->contactFirstName;
    }

    /**
     * @param string $contactFirstName
     */
    public function setContactFirstName($contactFirstName)
    {
        $this->contactFirstName = $contactFirstName;
    }

    /**
     * @return string
     */
    public function getContactLastName()
    {
        return $this->contactLastName;
    }

    /**
     * @param string $contactLastName
     */
    public function setContactLastName($contactLastName)
    {
        $this->contactLastName = $contactLastName;
    }

    /**
     * @return string
     */
    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    /**
     * @param string $contactPhone
     */
    public function setContactPhone($contactPhone)
    {
        $this->contactPhone = $contactPhone;
    }

    /**
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * @param string $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * @return string
     */
    public function getPrivacyPolicyLink()
    {
        return $this->privacyPolicyLink;
    }

    /**
     * @param string $privacyPolicyLink
     */
    public function setPrivacyPolicyLink($privacyPolicyLink)
    {
        $this->privacyPolicyLink = $privacyPolicyLink;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return \DateTime
     */
    public function getEditDate()
    {
        return $this->editDate;
    }

    /**
     * @param \DateTime $editDate
     */
    public function setEditDate($editDate)
    {
        $this->editDate = $editDate;
    }

    /**
     * @return bool
     */
    public function isGroup()
    {
        return $this->group;
    }

    /**
     * @param bool $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return Conformity
     */
    public function getConformity()
    {
        return $this->conformity;
    }

    /**
     * @param Conformity $conformity
     */
    public function setConformity($conformity)
    {
        $this->conformity = $conformity;
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
     * @return SubcontractorType
     */
    public function getSubcontractorType()
    {
        return $this->subcontractorType;
    }

    /**
     * @param SubcontractorType $subcontractorType
     */
    public function setSubcontractorType(SubcontractorType $subcontractorType)
    {
        $this->subcontractorType = $subcontractorType;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $documents
     */
    public function setDocuments($documents)
    {
        $this->documents = $documents;
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

