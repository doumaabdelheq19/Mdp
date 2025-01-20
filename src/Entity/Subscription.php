<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscription
 *
 * @ORM\Table(name="subscription", uniqueConstraints={@ORM\UniqueConstraint(name="subscription_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="subscription_user_id_fk", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionRepository")
 */
class Subscription
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
     * @ORM\Column(name="creation_date", type="datetime", nullable=true)
     */
    private $creationDate;

    /**
     * @var string
     *
     * @ORM\Column(name="offer", type="string", length=255, nullable=true)
     */
    private $offer;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="begin_date", type="datetime", nullable=true)
     */
    private $beginDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="payment_until", type="datetime", nullable=true)
     */
    private $paymentUntil;

    /**
     * @var string
     *
     * @ORM\Column(name="billing", type="string", length=255, nullable=true)
     */
    private $billing;

    /**
     * @var integer
     *
     * @ORM\Column(name="involvement_months", type="integer", nullable=true)
     */
    private $involvementMonths = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="billing_months", type="integer", nullable=true)
     */
    private $billingMonths = 1;

    /**
     * @var float
     *
     * @ORM\Column(name="unit_billing_price", type="float", precision=10, scale=0, nullable=true)
     */
    private $unitBillingPrice = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`active`", type="boolean", nullable=true)
     */
    private $active = false;

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
     * @var SubscriptionType
     *
     * @ORM\ManyToOne(targetEntity="SubscriptionType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * })
     */
    private $type;

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
     * @return string
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * @param string $offer
     */
    public function setOffer($offer)
    {
        $this->offer = $offer;
    }

    /**
     * @return \DateTime
     */
    public function getBeginDate()
    {
        return $this->beginDate;
    }

    /**
     * @param \DateTime $beginDate
     */
    public function setBeginDate($beginDate)
    {
        $this->beginDate = $beginDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return \DateTime
     */
    public function getPaymentUntil()
    {
        return $this->paymentUntil;
    }

    /**
     * @param \DateTime $paymentUntil
     */
    public function setPaymentUntil($paymentUntil)
    {
        $this->paymentUntil = $paymentUntil;
    }

    /**
     * @return string
     */
    public function getBilling()
    {
        return $this->billing;
    }

    /**
     * @param string $billing
     */
    public function setBilling($billing)
    {
        $this->billing = $billing;
    }

    /**
     * @return int
     */
    public function getInvolvementMonths()
    {
        return $this->involvementMonths;
    }

    /**
     * @param int $involvementMonths
     */
    public function setInvolvementMonths($involvementMonths)
    {
        $this->involvementMonths = $involvementMonths;
    }

    /**
     * @return int
     */
    public function getBillingMonths()
    {
        return $this->billingMonths;
    }

    /**
     * @param int $billingMonths
     */
    public function setBillingMonths($billingMonths)
    {
        $this->billingMonths = $billingMonths;
    }

    /**
     * @return float
     */
    public function getUnitBillingPrice()
    {
        return $this->unitBillingPrice;
    }

    /**
     * @param float $unitBillingPrice
     */
    public function setUnitBillingPrice($unitBillingPrice)
    {
        $this->unitBillingPrice = $unitBillingPrice;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
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
     * @return SubscriptionType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param SubscriptionType $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

}

