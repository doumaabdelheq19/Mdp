<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Incident
 *
 * @ORM\Table(name="incident", uniqueConstraints={@ORM\UniqueConstraint(name="incident_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="incident_user_id_fk", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\IncidentRepository")
 * @Gedmo\Loggable
 */
class Incident
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
     * @var boolean
     *
     * @ORM\Column(name="cnil_informed", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $cnilInformed = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="notice_72_h", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $notice72H = true;

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
     * @ORM\Column(name="people_number", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $peopleNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="file_type", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $fileType;

    /**
     * @var string
     *
     * @ORM\Column(name="consequences", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $consequences;

    /**
     * @var string
     *
     * @ORM\Column(name="taken_measures", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $takenMeasures;

    /**
     * @var boolean
     *
     * @ORM\Column(name="people_informed", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $peopleInformed = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=true)
     */
    private $creationDate;

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
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $file;

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
     * @return bool
     */
    public function isCnilInformed()
    {
        return $this->cnilInformed;
    }

    /**
     * @param bool $cnilInformed
     */
    public function setCnilInformed($cnilInformed)
    {
        $this->cnilInformed = $cnilInformed;
    }

    /**
     * @return bool
     */
    public function isNotice72H()
    {
        return $this->notice72H;
    }

    /**
     * @param bool $notice72H
     */
    public function setNotice72H($notice72H)
    {
        $this->notice72H = $notice72H;
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
    public function getPeopleNumber()
    {
        return $this->peopleNumber;
    }

    /**
     * @param string $peopleNumber
     */
    public function setPeopleNumber($peopleNumber)
    {
        $this->peopleNumber = $peopleNumber;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     * @return string
     */
    public function getConsequences()
    {
        return $this->consequences;
    }

    /**
     * @param string $consequences
     */
    public function setConsequences($consequences)
    {
        $this->consequences = $consequences;
    }

    /**
     * @return string
     */
    public function getTakenMeasures()
    {
        return $this->takenMeasures;
    }

    /**
     * @param string $takenMeasures
     */
    public function setTakenMeasures($takenMeasures)
    {
        $this->takenMeasures = $takenMeasures;
    }

    /**
     * @return bool
     */
    public function isPeopleInformed()
    {
        return $this->peopleInformed;
    }

    /**
     * @param bool $peopleInformed
     */
    public function setPeopleInformed($peopleInformed)
    {
        $this->peopleInformed = $peopleInformed;
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
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
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
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile(string $file): void
    {
        $this->file = $file;
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


}

