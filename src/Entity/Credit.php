<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Credit
 *
 * @ORM\Table(name="credit", uniqueConstraints={@ORM\UniqueConstraint(name="credit_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="credit_manager_id_fk", columns={"manager_id"}), @ORM\Index(name="credit_user_id_fk", columns={"user_id"})})
 * @ORM\Entity
 */
class Credit
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
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var float
     *
     * @ORM\Column(name="stock", type="float", precision=10, scale=0, nullable=true)
     */
    private $stock = 1;

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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return float
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * @param float $stock
     */
    public function setStock($stock): void
    {
        $this->stock = $stock;
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
    public function setManager(Manager $manager)
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
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function convertToDecimal($setValue = false) {
        $value = $this->getStock();
        if ($this->getStock() > 0) {
            $intStock = intval($this->getStock());
            if ($this->getStock() != $intStock) {
                $decimalMinutePart = $this->getStock() - $intStock;
                $decimalPart = ($decimalMinutePart / 60) * 100;
                $value = $intStock + $decimalPart;
                if ($setValue) {
                    $this->setStock($value);
                }
            }
        }
        return $value;
    }

    public function convertToMinutes($setValue = false) {
        $value = $this->getStock();
        if ($this->getStock() > 0) {
            $intStock = intval($this->getStock());
            if ($this->getStock() != $intStock) {
                $decimalMinutePart = $this->getStock() - $intStock;
                $decimalPart = ($decimalMinutePart / 100) * 60;
                $value = $intStock + $decimalPart;
                if ($setValue) {
                    $this->setStock($value);
                }
            }
        }
        return $value;
    }
}

