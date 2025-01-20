<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Action
 *
 * @ORM\Table(name="action", uniqueConstraints={@ORM\UniqueConstraint(name="action_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="action_user_id_fk", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\ActionRepository")
 * @Gedmo\Loggable
 */
class Action
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
     * @ORM\Column(name="budget", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $budget;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_last_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $accountantLastName;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_first_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $accountantFirstName;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_email", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $accountantEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_phone", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $accountantPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="goal", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $goal;

    /**
     * @var string
     *
     * @ORM\Column(name="information", type="text", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $information;

    /**
     * @var string
     *
     * @ORM\Column(name="useful_link", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $usefulLink;

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
     */
    private $editDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="set_up_date", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $setUpDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`terminated`", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $terminated = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="by_manager", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $byManager = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="for_dpo", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $forDpo = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="estimation_time", type="integer", nullable=true)
     */
    private $estimationTime = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="real_time", type="integer", nullable=true)
     */
    private $realTime = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="`priority`", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    private $priority = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="by_group", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $byGroup = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="group_user_concerned", type="boolean", nullable=true)
     * @Gedmo\Versioned
     */
    private $groupUserConcerned = false;

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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_user_id", referencedColumnName="id")
     * })
     */
    private $groupUser;


    /**
     * @var Action
     *
     * @ORM\ManyToOne(targetEntity="Action")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_action_id", referencedColumnName="id")
     * })
     */
    private $groupAction;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Treatment", inversedBy="actions")
     * @ORM\JoinTable(name="action_has_treatment",
     *   joinColumns={
     *     @ORM\JoinColumn(name="action_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="treatment_id", referencedColumnName="id")
     *   }
     * )
     */
    private $treatments;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="UserDocument", mappedBy="action")
     */
    private $documents;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Document")
     * @ORM\JoinTable(name="action_has_sheet",
     *   joinColumns={
     *     @ORM\JoinColumn(name="action_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="document_id", referencedColumnName="id")
     *   }
     * )
     */
    private $sheets;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->treatments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->documents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sheets = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @param string $budget
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;
    }

    /**
     * @return string
     */
    public function getAccountantLastName()
    {
        return $this->accountantLastName;
    }

    /**
     * @param string $accountantLastName
     */
    public function setAccountantLastName($accountantLastName)
    {
        $this->accountantLastName = $accountantLastName;
    }

    /**
     * @return string
     */
    public function getAccountantFirstName()
    {
        return $this->accountantFirstName;
    }

    /**
     * @param string $accountantFirstName
     */
    public function setAccountantFirstName($accountantFirstName)
    {
        $this->accountantFirstName = $accountantFirstName;
    }

    /**
     * @return string
     */
    public function getAccountantEmail()
    {
        return $this->accountantEmail;
    }

    /**
     * @param string $accountantEmail
     */
    public function setAccountantEmail($accountantEmail)
    {
        $this->accountantEmail = $accountantEmail;
    }

    /**
     * @return string
     */
    public function getAccountantPhone()
    {
        return $this->accountantPhone;
    }

    /**
     * @param string $accountantPhone
     */
    public function setAccountantPhone($accountantPhone)
    {
        $this->accountantPhone = $accountantPhone;
    }

    /**
     * @return string
     */
    public function getGoal()
    {
        return $this->goal;
    }

    /**
     * @param string $goal
     */
    public function setGoal($goal)
    {
        $this->goal = $goal;
    }

    /**
     * @return string
     */
    public function getInformation()
    {
        return $this->information;
    }

    /**
     * @param string $information
     */
    public function setInformation($information)
    {
        $this->information = $information;
    }

    /**
     * @return string
     */
    public function getUsefulLink()
    {
        return $this->usefulLink;
    }

    /**
     * @param string $usefulLink
     */
    public function setUsefulLink($usefulLink)
    {
        $this->usefulLink = $usefulLink;
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
     * @return \DateTime
     */
    public function getSetUpDate()
    {
        return $this->setUpDate;
    }

    /**
     * @param \DateTime $setUpDate
     */
    public function setSetUpDate($setUpDate)
    {
        $this->setUpDate = $setUpDate;
    }

    /**
     * @return bool
     */
    public function isTerminated()
    {
        return $this->terminated;
    }

    /**
     * @param bool $terminated
     */
    public function setTerminated($terminated)
    {
        $this->terminated = $terminated;
    }

    /**
     * @return bool
     */
    public function isByManager()
    {
        return $this->byManager;
    }

    /**
     * @param bool $byManager
     */
    public function setByManager($byManager)
    {
        $this->byManager = $byManager;
    }

    /**
     * @return bool
     */
    public function isForDpo()
    {
        return $this->forDpo;
    }

    /**
     * @param bool $forDpo
     */
    public function setForDpo($forDpo)
    {
        $this->forDpo = $forDpo;
    }

    /**
     * @return int
     */
    public function getEstimationTime()
    {
        return $this->estimationTime;
    }

    /**
     * @param int $estimationTime
     */
    public function setEstimationTime($estimationTime)
    {
        $this->estimationTime = $estimationTime;
    }

    /**
     * @return int
     */
    public function getRealTime()
    {
        return $this->realTime;
    }

    /**
     * @param int $realTime
     */
    public function setRealTime($realTime)
    {
        $this->realTime = $realTime;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return bool
     */
    public function isByGroup(): bool
    {
        return $this->byGroup;
    }

    /**
     * @param bool $byGroup
     */
    public function setByGroup(bool $byGroup): void
    {
        $this->byGroup = $byGroup;
    }

    /**
     * @return bool
     */
    public function isGroupUserConcerned(): bool
    {
        return $this->groupUserConcerned;
    }

    /**
     * @param bool $groupUserConcerned
     */
    public function setGroupUserConcerned(bool $groupUserConcerned): void
    {
        $this->groupUserConcerned = $groupUserConcerned;
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
     * @return User
     */
    public function getGroupUser()
    {
        return $this->groupUser;
    }

    /**
     * @param User $groupUser
     */
    public function setGroupUser(User $groupUser)
    {
        $this->groupUser = $groupUser;
    }

    /**
     * @return Action
     */
    public function getGroupAction()
    {
        return $this->groupAction;
    }

    /**
     * @param Action $groupAction
     */
    public function setGroupAction(Action $groupAction)
    {
        $this->groupAction = $groupAction;
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
    public function getSheets()
    {
        return $this->sheets;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $sheets
     */
    public function setSheets($sheets)
    {
        $this->sheets = $sheets;
    }


}

