<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="user_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="user_manager_id_fk", columns={"manager_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
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
     * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
     */
    private $companyName;

    /**
     * @var string
     *
     * @ORM\Column(name="siret", type="string", length=255, nullable=true)
     */
    private $siret;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="address_2", type="string", length=255, nullable=true)
     */
    private $address2;

    /**
     * @var string
     *
     * @ORM\Column(name="zip_code", type="string", length=16, nullable=true)
     */
    private $zipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="company_phone", type="string", length=255, nullable=true)
     */
    private $companyPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_first_name", type="string", length=255, nullable=true)
     */
    private $contactFirstName;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_last_name", type="string", length=255, nullable=true)
     */
    private $contactLastName;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_email", type="string", length=255, nullable=true)
     */
    private $contactEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_phone", type="string", length=255, nullable=true)
     */
    private $contactPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_job", type="string", length=255, nullable=true)
     */
    private $contactJob;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_first_name", type="string", length=255, nullable=true)
     */
    private $accountantFirstName;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_last_name", type="string", length=255, nullable=true)
     */
    private $accountantLastName;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_email", type="string", length=255, nullable=true)
     */
    private $accountantEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_phone", type="string", length=255, nullable=true)
     */
    private $accountantPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_job", type="string", length=255, nullable=true)
     */
    private $accountantJob;

    /**
     * @var string
     *
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    private $picture;

    /**
     * @var boolean
     *
     * @ORM\Column(name="manager_dpo", type="boolean", nullable=true)
     */
    private $managerDpo = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="demo", type="boolean", nullable=true)
     */
    private $demo = false;

    /**
     * @var float
     *
     * @ORM\Column(name="credit", type="float", precision=10, scale=0, nullable=true)
     */
    private $credit = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="employees_number", type="integer", nullable=false)
     */
    private $employeesNumber = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=5, nullable=true)
     */
    private $language = "fr";

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
     * @var Manager
     *
     * @ORM\ManyToOne(targetEntity="Manager")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="lawyer_id", referencedColumnName="id")
     * })
     */
    private $lawyer;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="childrenUsers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $parentUser;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="parentUser")
     */
    private $childrenUsers;

    /**
     * @ORM\OneToOne(targetEntity="Account", mappedBy="user")
     */
    protected $account;

    /**
     * @var Subscription
     *
     * @ORM\ManyToOne(targetEntity="Subscription")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="current_subscription_id", referencedColumnName="id")
     * })
     */
    private $currentSubscription;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Treatment", inversedBy="groupUsers")
     * @ORM\JoinTable(name="user_has_treatment_group",
     *   joinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="treatment_id", referencedColumnName="id")
     *   }
     * )
     */
    private $groupTreatments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->childrenUsers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groupTreatments = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string
     */
    public function getSiret()
    {
        return $this->siret;
    }

    /**
     * @param string $siret
     */
    public function setSiret($siret)
    {
        $this->siret = $siret;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param string $address2
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCompanyPhone()
    {
        return $this->companyPhone;
    }

    /**
     * @param string $companyPhone
     */
    public function setCompanyPhone($companyPhone)
    {
        $this->companyPhone = $companyPhone;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
    public function getContactJob()
    {
        return $this->contactJob;
    }

    /**
     * @param string $contactJob
     */
    public function setContactJob($contactJob)
    {
        $this->contactJob = $contactJob;
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
    public function getAccountantJob()
    {
        return $this->accountantJob;
    }

    /**
     * @param string $accountantJob
     */
    public function setAccountantJob($accountantJob)
    {
        $this->accountantJob = $accountantJob;
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;
    }

    /**
     * @return bool
     */
    public function isManagerDpo()
    {
        return $this->managerDpo;
    }

    /**
     * @param bool $managerDpo
     */
    public function setManagerDpo($managerDpo)
    {
        $this->managerDpo = $managerDpo;
    }

    /**
     * @return bool
     */
    public function isDemo()
    {
        return $this->demo;
    }

    /**
     * @param bool $demo
     */
    public function setDemo($demo)
    {
        $this->demo = $demo;
    }

    /**
     * @return float
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param float $credit
     */
    public function setCredit($credit): void
    {
        $this->credit = $credit;
    }

    /**
     * @return int
     */
    public function getEmployeesNumber()
    {
        return $this->employeesNumber;
    }

    /**
     * @param int $employeesNumber
     */
    public function setEmployeesNumber($employeesNumber)
    {
        $this->employeesNumber = $employeesNumber;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
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
     * @return Manager
     */
    public function getLawyer()
    {
        return $this->lawyer;
    }

    /**
     * @param Manager $lawyer
     */
    public function setLawyer($lawyer)
    {
        $this->lawyer = $lawyer;
    }

    /**
     * @return User
     */
    public function getParentUser()
    {
        return $this->parentUser;
    }

    /**
     * @param User $parentUser
     */
    public function setParentUser($parentUser)
    {
        $this->parentUser = $parentUser;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildrenUsers()
    {
        return $this->childrenUsers;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $childrenUsers
     */
    public function setChildrenUsers($childrenUsers)
    {
        $this->childrenUsers = $childrenUsers;
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param mixed $account
     */
    public function setAccount($account)
    {
        $this->account = $account;
    }

    /**
     * @return Subscription
     */
    public function getCurrentSubscription()
    {
        return $this->currentSubscription;
    }

    /**
     * @param Subscription $currentSubscription
     */
    public function setCurrentSubscription($currentSubscription)
    {
        $this->currentSubscription = $currentSubscription;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupTreatments(): \Doctrine\Common\Collections\Collection
    {
        return $this->groupTreatments;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $groupTreatments
     */
    public function setGroupTreatments(\Doctrine\Common\Collections\Collection $groupTreatments): void
    {
        $this->groupTreatments = $groupTreatments;
    }

    public function isMainGroupAgency() {
        return count($this->getChildrenUsers()) > 0;
    }

}

