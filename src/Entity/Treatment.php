<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Treatment
 *
 * @ORM\Table(name="treatment", uniqueConstraints={@ORM\UniqueConstraint(name="treatment_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="treatment_user_id_fk", columns={"user_id"})})
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class Treatment
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
     * @var integer
     *
     * @ORM\Column(name="number", type="integer", nullable=true)
     */
    private $number = 1;

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
     * @var string
     *
     * @ORM\Column(name="main_purpose", type="string", length=510, nullable=true)
     * @Gedmo\Versioned
     */
    private $mainPurpose;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_1", type="string", length=510, nullable=true)
     * @Gedmo\Versioned
     */
    private $purpose1;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_2", type="string", length=510, nullable=true)
     * @Gedmo\Versioned
     */
    private $purpose2;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_3", type="string", length=510, nullable=true)
     * @Gedmo\Versioned
     */
    private $purpose3;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_4", type="string", length=510, nullable=true)
     * @Gedmo\Versioned
     */
    private $purpose4;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose_5", type="string", length=510, nullable=true)
     * @Gedmo\Versioned
     */
    private $purpose5;

    /**
     * @var string
     *
     * @ORM\Column(name="others_purpose", type="string", length=510, nullable=true)
     * @Gedmo\Versioned
     */
    private $othersPurpose;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var array
     *
     * @ORM\Column(name="personal_data", type="array", nullable=true)
     * @Gedmo\Versioned
     */
    private $personalData;

    /**
     * @var string
     *
     * @ORM\Column(name="people_data", type="string", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $peopleData;

    /**
     * @var string
     *
     * @ORM\Column(name="transfer_outside_ue_countries", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $transferOutsideUeCountries;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sensitive_data", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $sensitiveData = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="consent_asked", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $consentAsked = false;

    /**
     * @var string
     *
     * @ORM\Column(name="consent_how", type="string", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $consentHow;

    /**
     * @var array
     *
     * @ORM\Column(name="pia_criteria", type="array", nullable=true)
     * @Gedmo\Versioned
     */
    private $piaCriteria = [];

    /**
     * @var boolean
     *
     * @ORM\Column(name="pia_needed", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $piaNeeded = false;

    /**
     * @var string
     *
     * @ORM\Column(name="pia_file", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $piaFile;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pia_exoneration", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $piaExoneration = false;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_basis", type="string", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $legalBasis;

    /**
     * @var string
     *
     * @ORM\Column(name="data_source", type="string", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $dataSource;

    /**
     * @var boolean
     *
     * @ORM\Column(name="automated_decision", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $automatedDecision = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="insufficient_criteria", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $insufficientCriteria = false;

    /**
     * @var string
     *
     * @ORM\Column(name="data_retention_period", type="string", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $dataRetentionPeriod;

    /**
     * @var string
     *
     * @ORM\Column(name="treatment_accountant", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $treatmentAccountant;

    /**
     * @var string
     *
     * @ORM\Column(name="dpo", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $dpo;

    /**
     * @var string
     *
     * @ORM\Column(name="service_accountant", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $serviceAccountant;

    /**
     * @var string
     *
     * @ORM\Column(name="editor", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $editor;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`group`", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $group = false;

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
     * @ORM\ManyToMany(targetEntity="Subcontractor", inversedBy="treatments")
     * @ORM\JoinTable(name="treatment_has_subcontractor",
     *   joinColumns={
     *     @ORM\JoinColumn(name="treatment_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="subcontractor_id", referencedColumnName="id")
     *   }
     * )
     */
    private $subcontractors;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="System", inversedBy="treatments")
     * @ORM\JoinTable(name="treatment_has_system",
     *   joinColumns={
     *     @ORM\JoinColumn(name="treatment_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="system_id", referencedColumnName="id")
     *   }
     * )
     */
    private $systems;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Action", mappedBy="treatments")
     */
    private $actions;

    /**
     * @var TreatmentState
     *
     * @ORM\ManyToOne(targetEntity="TreatmentState")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_id", referencedColumnName="id")
     * })
     * @Gedmo\Versioned
     */
    private $state;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groupTreatments")
     */
    private $groupUsers;

    /**
     * @var SubcontractorType
     *
     * @ORM\ManyToOne(targetEntity="SubcontractorType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_subcontractor_type_id", referencedColumnName="id")
     * })
     */
    private $companySubcontractorType;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->subcontractors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->systems = new \Doctrine\Common\Collections\ArrayCollection();
        $this->actions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groupUsers = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
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
     * @return string
     */
    public function getPiaFile()
    {
        return $this->piaFile;
    }

    /**
     * @param string $piaFile
     */
    public function setPiaFile($piaFile)
    {
        $this->piaFile = $piaFile;
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
     * @return string
     */
    public function getTreatmentAccountant()
    {
        return $this->treatmentAccountant;
    }

    /**
     * @param string $treatmentAccountant
     */
    public function setTreatmentAccountant($treatmentAccountant)
    {
        $this->treatmentAccountant = $treatmentAccountant;
    }

    /**
     * @return string
     */
    public function getDpo()
    {
        return $this->dpo;
    }

    /**
     * @param string $dpo
     */
    public function setDpo($dpo)
    {
        $this->dpo = $dpo;
    }

    /**
     * @return string
     */
    public function getServiceAccountant()
    {
        return $this->serviceAccountant;
    }

    /**
     * @param string $serviceAccountant
     */
    public function setServiceAccountant($serviceAccountant)
    {
        $this->serviceAccountant = $serviceAccountant;
    }

    /**
     * @return string
     */
    public function getEditor()
    {
        return $this->editor;
    }

    /**
     * @param string $editor
     */
    public function setEditor($editor)
    {
        $this->editor = $editor;
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
    public function getSubcontractors()
    {
        return $this->subcontractors;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $subcontractors
     */
    public function setSubcontractors($subcontractors)
    {
        $this->subcontractors = $subcontractors;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSystems()
    {
        return $this->systems;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $systems
     */
    public function setSystems($systems)
    {
        $this->systems = $systems;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $actions
     */
    public function setActions($actions)
    {
        $this->actions = $actions;
    }

    /**
     * @return TreatmentState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param TreatmentState $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupUsers()
    {
        return $this->groupUsers;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $groupUsers
     */
    public function setGroupUsers($groupUsers): void
    {
        $this->groupUsers = $groupUsers;
    }

    /**
     * @return SubcontractorType
     */
    public function getCompanySubcontractorType()
    {
        return $this->companySubcontractorType;
    }

    /**
     * @param SubcontractorType $companySubcontractorType
     */
    public function setCompanySubcontractorType($companySubcontractorType)
    {
        $this->companySubcontractorType = $companySubcontractorType;
    }

}

