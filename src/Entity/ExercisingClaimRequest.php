<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ExercisingClaimRequest
 *
 * @ORM\Table(name="exercising_claim_request", uniqueConstraints={@ORM\UniqueConstraint(name="exercising_claim_request_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="exercising_claim_request_user_id_fk", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\ExercisingClaimRequestRepository")
 * @Gedmo\Loggable
 */
class ExercisingClaimRequest
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
     * @var \DateTime
     *
     * @ORM\Column(name="request_date", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $requestDate;

    /**
     * @var string
     *
     * @ORM\Column(name="rights", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $rights;

    /**
     * @var string
     *
     * @ORM\Column(name="customer", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $customer;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="answer_date", type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $answerDate;

    /**
     * @var string
     *
     * @ORM\Column(name="accountant_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $accountantName;

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
     * @ORM\Column(name="precisions", type="string", length=65535, nullable=true)
     * @Gedmo\Versioned
     */
    private $precisions;

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
     * @return \DateTime
     */
    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * @param \DateTime $requestDate
     */
    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;
    }

    /**
     * @return string
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * @param string $rights
     */
    public function setRights($rights)
    {
        $this->rights = $rights;
    }

    /**
     * @return string
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param string $customer
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return \DateTime
     */
    public function getAnswerDate()
    {
        return $this->answerDate;
    }

    /**
     * @param \DateTime $answerDate
     */
    public function setAnswerDate($answerDate)
    {
        $this->answerDate = $answerDate;
    }

    /**
     * @return string
     */
    public function getAccountantName()
    {
        return $this->accountantName;
    }

    /**
     * @param string $accountantName
     */
    public function setAccountantName($accountantName)
    {
        $this->accountantName = $accountantName;
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
    public function getPrecisions()
    {
        return $this->precisions;
    }

    /**
     * @param string $precisions
     */
    public function setPrecisions($precisions)
    {
        $this->precisions = $precisions;
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
    public function setFile($file)
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

