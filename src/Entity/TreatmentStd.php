<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Treatment
 *
 * @ORM\Table(name="treatment_std", uniqueConstraints={@ORM\UniqueConstraint(name="treatment_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="treatment_std_manager_id_fk", columns={"manager_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\TreatmentStdRepository")
 */
class TreatmentStd
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
     * @Gedmo\Translatable
     */
    private $name;

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
     */
    private $editDate;

    /**
     * @var string
     *
     * @ORM\Column(name="main_purpose", type="string", length=510, nullable=true)
     * @Gedmo\Translatable
     */
    private $mainPurpose;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_1", type="string", length=510, nullable=true)
     * @Gedmo\Translatable
     */
    private $purpose1;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_2", type="string", length=510, nullable=true)
     * @Gedmo\Translatable
     */
    private $purpose2;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_3", type="string", length=510, nullable=true)
     * @Gedmo\Translatable
     */
    private $purpose3;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_4", type="string", length=510, nullable=true)
     * @Gedmo\Translatable
     */
    private $purpose4;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_5", type="string", length=510, nullable=true)
     * @Gedmo\Translatable
     */
    private $purpose5;

    /**
     * @var string
     *
     * @ORM\Column(name="others_purpose", type="string", length=510, nullable=true)
     * @Gedmo\Translatable
     */
    private $othersPurpose;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=65535, nullable=true)
     * @Gedmo\Translatable
     */
    private $description;

    /**
     * @var array
     *
     * @ORM\Column(name="personal_data", type="array", nullable=true)
     * @Gedmo\Translatable
     */
    private $personalData;

    /**
     * @var string
     *
     * @ORM\Column(name="people_data", type="string", length=65535, nullable=true)
     * @Gedmo\Translatable
     */
    private $peopleData;

    /**
     * @var string
     *
     * @ORM\Column(name="transfer_outside_ue_countries", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $transferOutsideUeCountries;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sensitive_data", type="boolean", nullable=true)
     */
    private $sensitiveData = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="consent_asked", type="boolean", nullable=true)
     */
    private $consentAsked = false;

    /**
     * @var string
     *
     * @ORM\Column(name="consent_how", type="string", length=65535, nullable=true)
     * @Gedmo\Translatable
     */
    private $consentHow;

    /**
     * @var array
     *
     * @ORM\Column(name="pia_criteria", type="array", nullable=true)
     */
    private $piaCriteria = [];

    /**
     * @var boolean
     *
     * @ORM\Column(name="pia_needed", type="boolean", nullable=true)
     */
    private $piaNeeded = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pia_exoneration", type="boolean", nullable=true)
     */
    private $piaExoneration = false;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_basis", type="string", length=65535, nullable=true)
     * @Gedmo\Translatable
     */
    private $legalBasis;

    /**
     * @var string
     *
     * @ORM\Column(name="data_source", type="string", length=65535, nullable=true)
     * @Gedmo\Translatable
     */
    private $dataSource;

    /**
     * @var boolean
     *
     * @ORM\Column(name="automated_decision", type="boolean", nullable=true)
     */
    private $automatedDecision = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="insufficient_criteria", type="boolean", nullable=true)
     */
    private $insufficientCriteria = false;

    /**
     * @var string
     *
     * @ORM\Column(name="data_retention_period", type="string", length=65535, nullable=true)
     * @Gedmo\Translatable
     */
    private $dataRetentionPeriod;

    /**
     * @var Manager
     *
     * @ORM\ManyToOne(targetEntity="Manager")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     * })
     */
    private $manager;

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
     * @var TreatmentStdCategory
     *
     * @ORM\ManyToOne(targetEntity="TreatmentStdCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     */
    private $category;

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
     * @return string
     */
    public function getMainPurpose()
    {
        return $this->mainPurpose;
    }

    /**
     * @param string $mainPurpose
     */
    public function setMainPurpose($mainPurpose)
    {
        $this->mainPurpose = $mainPurpose;
    }

    /**
     * @return string
     */
    public function getPurpose1()
    {
        return $this->purpose1;
    }

    /**
     * @param string $purpose1
     */
    public function setPurpose1($purpose1)
    {
        $this->purpose1 = $purpose1;
    }

    /**
     * @return string
     */
    public function getPurpose2()
    {
        return $this->purpose2;
    }

    /**
     * @param string $purpose2
     */
    public function setPurpose2($purpose2)
    {
        $this->purpose2 = $purpose2;
    }

    /**
     * @return string
     */
    public function getPurpose3()
    {
        return $this->purpose3;
    }

    /**
     * @param string $purpose3
     */
    public function setPurpose3($purpose3)
    {
        $this->purpose3 = $purpose3;
    }

    /**
     * @return string
     */
    public function getPurpose4()
    {
        return $this->purpose4;
    }

    /**
     * @param string $purpose4
     */
    public function setPurpose4($purpose4)
    {
        $this->purpose4 = $purpose4;
    }

    /**
     * @return string
     */
    public function getPurpose5()
    {
        return $this->purpose5;
    }

    /**
     * @param string $purpose5
     */
    public function setPurpose5($purpose5)
    {
        $this->purpose5 = $purpose5;
    }

    /**
     * @return string
     */
    public function getOthersPurpose()
    {
        return $this->othersPurpose;
    }

    /**
     * @param string $othersPurpose
     */
    public function setOthersPurpose($othersPurpose)
    {
        $this->othersPurpose = $othersPurpose;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getPersonalData()
    {
        return $this->personalData;
    }

    /**
     * @param array $personalData
     */
    public function setPersonalData($personalData)
    {
        $this->personalData = $personalData;
    }

    /**
     * @return string
     */
    public function getPeopleData()
    {
        return $this->peopleData;
    }

    /**
     * @param string $peopleData
     */
    public function setPeopleData($peopleData)
    {
        $this->peopleData = $peopleData;
    }

    /**
     * @return string
     */
    public function getTransferOutsideUeCountries()
    {
        return $this->transferOutsideUeCountries;
    }

    /**
     * @param string $transferOutsideUeCountries
     */
    public function setTransferOutsideUeCountries($transferOutsideUeCountries)
    {
        $this->transferOutsideUeCountries = $transferOutsideUeCountries;
    }

    /**
     * @return bool
     */
    public function isSensitiveData()
    {
        return $this->sensitiveData;
    }

    /**
     * @param bool $sensitiveData
     */
    public function setSensitiveData($sensitiveData)
    {
        $this->sensitiveData = $sensitiveData;
    }

    /**
     * @return bool
     */
    public function isConsentAsked()
    {
        return $this->consentAsked;
    }

    /**
     * @param bool $consentAsked
     */
    public function setConsentAsked($consentAsked)
    {
        $this->consentAsked = $consentAsked;
    }

    /**
     * @return string
     */
    public function getConsentHow()
    {
        return $this->consentHow;
    }

    /**
     * @param string $consentHow
     */
    public function setConsentHow($consentHow)
    {
        $this->consentHow = $consentHow;
    }

    /**
     * @return array
     */
    public function getPiaCriteria()
    {
        return $this->piaCriteria;
    }

    /**
     * @param array $piaCriteria
     */
    public function setPiaCriteria($piaCriteria)
    {
        $this->piaCriteria = $piaCriteria;
    }

    /**
     * @param $piaCriteria
     */
    public function addPiaCriteria($piaCriteria)
    {
        $this->piaCriteria[] = $piaCriteria;
    }

    /**
     * @return bool
     */
    public function isPiaNeeded()
    {
        return $this->piaNeeded;
    }

    /**
     * @param bool $piaNeeded
     */
    public function setPiaNeeded($piaNeeded)
    {
        $this->piaNeeded = $piaNeeded;
    }

    /**
     * @return bool
     */
    public function isPiaExoneration()
    {
        return $this->piaExoneration;
    }

    /**
     * @param bool $piaExoneration
     */
    public function setPiaExoneration($piaExoneration)
    {
        $this->piaExoneration = $piaExoneration;
    }

    /**
     * @return string
     */
    public function getLegalBasis()
    {
        return $this->legalBasis;
    }

    /**
     * @param string $legalBasis
     */
    public function setLegalBasis($legalBasis)
    {
        $this->legalBasis = $legalBasis;
    }

    /**
     * @return string
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * @param string $dataSource
     */
    public function setDataSource($dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * @return bool
     */
    public function isAutomatedDecision()
    {
        return $this->automatedDecision;
    }

    /**
     * @param bool $automatedDecision
     */
    public function setAutomatedDecision($automatedDecision)
    {
        $this->automatedDecision = $automatedDecision;
    }

    /**
     * @return bool
     */
    public function isInsufficientCriteria(): bool
    {
        return $this->insufficientCriteria;
    }

    /**
     * @param bool $insufficientCriteria
     */
    public function setInsufficientCriteria(bool $insufficientCriteria): void
    {
        $this->insufficientCriteria = $insufficientCriteria;
    }

    /**
     * @return string
     */
    public function getDataRetentionPeriod()
    {
        return $this->dataRetentionPeriod;
    }

    /**
     * @param string $dataRetentionPeriod
     */
    public function setDataRetentionPeriod($dataRetentionPeriod)
    {
        $this->dataRetentionPeriod = $dataRetentionPeriod;
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Manager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
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
     * @return TreatmentStdCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param TreatmentStdCategory $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

}

