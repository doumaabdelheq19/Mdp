<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrainingCampain
 *
 * @ORM\Table(name="training_campain", uniqueConstraints={@ORM\UniqueConstraint(name="training_campain_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="training_campain_training_id_fk", columns={"training_id"}), @ORM\Index(name="training_campain_user_id_fk", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\TrainingCampainRepository")
 */
class TrainingCampain
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
     * @var array
     *
     * @ORM\Column(name="questions", type="array", nullable=true)
     */
    private $questions;

    /**
     * @var array
     *
     * @ORM\Column(name="answers", type="array", nullable=true)
     */
    private $answers;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var array
     *
     * @ORM\Column(name="emails", type="array", nullable=true)
     */
    private $emails = [];

    /**
     * @var integer
     *
     * @ORM\Column(name="emails_count", type="integer", nullable=false)
     */
    private $emailsCount = 0;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="traineeship", type="boolean", nullable=true)
     */
    private $traineeship = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="traineeship_date", type="datetime", nullable=true)
     */
    private $traineeshipDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="former", type="string", length=255, nullable=true)
     */
    private $former;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="external", type="boolean", nullable=true)
     */
    private $external = false;

    /**
     * @var Training
     *
     * @ORM\ManyToOne(targetEntity="Training")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="training_id", referencedColumnName="id")
     * })
     */
    private $training;

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
     * @ORM\ManyToMany(targetEntity="TrainingTeam", inversedBy="campains")
     * @ORM\JoinTable(name="training_campain_has_team",
     *   joinColumns={
     *     @ORM\JoinColumn(name="training_campain_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="training_team_id", referencedColumnName="id")
     *   }
     * )
     */
    private $teams;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->teams = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return \DateTime|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime|null $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return array
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * @param array $questions
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;
    }

    /**
     * @return array
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param array $answers
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param array $emails
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;
    }

    /**
     * @return int
     */
    public function getEmailsCount()
    {
        return $this->emailsCount;
    }

    /**
     * @param int $emailsCount
     */
    public function setEmailsCount($emailsCount)
    {
        $this->emailsCount = $emailsCount;
    }

    /**
     * @return bool|null
     */
    public function getTraineeship()
    {
        return $this->traineeship;
    }

    /**
     * @param bool|null $traineeship
     */
    public function setTraineeship($traineeship)
    {
        $this->traineeship = $traineeship;
    }

    /**
     * @return \DateTime|null
     */
    public function getTraineeshipDate()
    {
        return $this->traineeshipDate;
    }

    /**
     * @param \DateTime|null $traineeshipDate
     */
    public function setTraineeshipDate($traineeshipDate)
    {
        $this->traineeshipDate = $traineeshipDate;
    }

    /**
     * @return string|null
     */
    public function getFormer()
    {
        return $this->former;
    }

    /**
     * @param string|null $former
     */
    public function setFormer($former)
    {
        $this->former = $former;
    }

    /**
     * @return bool|null
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * @param bool|null $external
     */
    public function setExternal(?bool $external)
    {
        $this->external = $external;
    }

    /**
     * @return Training
     */
    public function getTraining()
    {
        return $this->training;
    }

    /**
     * @param Training $training
     */
    public function setTraining($training)
    {
        $this->training = $training;
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
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $teams
     */
    public function setTeams($teams): void
    {
        $this->teams = $teams;
    }

}

