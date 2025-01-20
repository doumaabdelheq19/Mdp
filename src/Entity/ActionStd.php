<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * ActionStd
 *
 * @ORM\Table(name="action_std", uniqueConstraints={@ORM\UniqueConstraint(name="action_std_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="action_std_manager_id_fk", columns={"manager_id"})})
 * @ORM\Entity
 */
class ActionStd
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
     * @var string
     *
     * @ORM\Column(name="budget", type="string", length=255, nullable=true)
     */
    private $budget;

    /**
     * @var string
     *
     * @ORM\Column(name="goal", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $goal;

    /**
     * @var string
     *
     * @ORM\Column(name="information", type="text", length=65535, nullable=true)
     * @Gedmo\Translatable
     */
    private $information;

    /**
     * @var string
     *
     * @ORM\Column(name="useful_link", type="text", length=65535, nullable=true)
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
     * @var Manager
     *
     * @ORM\ManyToOne(targetEntity="Manager")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     * })
     */
    private $manager;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="ActionStdDocument", mappedBy="actionStd")
     */
    private $documents;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Document")
     * @ORM\JoinTable(name="action_std_has_sheet",
     *   joinColumns={
     *     @ORM\JoinColumn(name="action_std_id", referencedColumnName="id")
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

