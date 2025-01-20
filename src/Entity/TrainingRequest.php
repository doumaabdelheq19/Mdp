<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrainingRequest
 *
 * @ORM\Table(name="training_request", uniqueConstraints={@ORM\UniqueConstraint(name="training_request_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="training_request_training_campain_id_fk", columns={"training_campain_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\TrainingRequestRepository")
 */
class TrainingRequest
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
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="answer_date", type="datetime", nullable=true)
     */
    private $answerDate;

    /**
     * @var array
     *
     * @ORM\Column(name="user_answers", type="array", nullable=true)
     */
    private $userAnswers;

    /**
     * @var float
     *
     * @ORM\Column(name="result", type="float", precision=10, scale=0, nullable=true)
     */
    private $result = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="position", type="string", length=255, nullable=true)
     */
    private $position;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="resend_date", type="datetime", nullable=true)
     */
    private $resendDate;

    /**
     * @var TrainingCampain
     *
     * @ORM\ManyToOne(targetEntity="TrainingCampain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="training_campain_id", referencedColumnName="id")
     * })
     */
    private $trainingCampain;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="TrainingRequestHistory", mappedBy="trainingRequest")
     */
    private $histories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->histories = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string|null $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return \DateTime|null
     */
    public function getAnswerDate()
    {
        return $this->answerDate;
    }

    /**
     * @param \DateTime|null $answerDate
     */
    public function setAnswerDate($answerDate)
    {
        $this->answerDate = $answerDate;
    }

    /**
     * @return array
     */
    public function getUserAnswers()
    {
        return $this->userAnswers;
    }

    /**
     * @param array $userAnswers
     */
    public function setUserAnswers($userAnswers)
    {
        $this->userAnswers = $userAnswers;
    }

    /**
     * @return float
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param float $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string|null
     */
    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * @param string|null $position
     */
    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }

    /**
     * @return \DateTime
     */
    public function getResendDate()
    {
        return $this->resendDate;
    }

    /**
     * @param \DateTime $resendDate
     */
    public function setResendDate(\DateTime $resendDate)
    {
        $this->resendDate = $resendDate;
    }

    /**
     * @return TrainingCampain
     */
    public function getTrainingCampain()
    {
        return $this->trainingCampain;
    }

    /**
     * @param TrainingCampain $trainingCampain
     */
    public function setTrainingCampain($trainingCampain)
    {
        $this->trainingCampain = $trainingCampain;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHistories()
    {
        return $this->histories;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $histories
     */
    public function setHistories($histories): void
    {
        $this->histories = $histories;
    }

}

