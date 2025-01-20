<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrainingRequestHistory
 *
 * @ORM\Table(name="training_request_history", uniqueConstraints={@ORM\UniqueConstraint(name="training_request_history_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="training_request_history_training_request_id_fk", columns={"training_request_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\TrainingRequestHistoryRepository")
 */
class TrainingRequestHistory
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
     * @var TrainingRequest
     *
     * @ORM\ManyToOne(targetEntity="TrainingRequest", inversedBy="histories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="training_request_id", referencedColumnName="id")
     * })
     */
    private $trainingRequest;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getAnswerDate(): \DateTime
    {
        return $this->answerDate;
    }

    /**
     * @param \DateTime $answerDate
     */
    public function setAnswerDate(\DateTime $answerDate): void
    {
        $this->answerDate = $answerDate;
    }

    /**
     * @return array
     */
    public function getUserAnswers(): array
    {
        return $this->userAnswers;
    }

    /**
     * @param array $userAnswers
     */
    public function setUserAnswers(array $userAnswers): void
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
    public function setResult($result): void
    {
        $this->result = $result;
    }

    /**
     * @return TrainingRequest
     */
    public function getTrainingRequest(): TrainingRequest
    {
        return $this->trainingRequest;
    }

    /**
     * @param TrainingRequest $trainingRequest
     */
    public function setTrainingRequest(TrainingRequest $trainingRequest): void
    {
        $this->trainingRequest = $trainingRequest;
    }

}

