<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Training
 *
 * @ORM\Table(name="training", uniqueConstraints={@ORM\UniqueConstraint(name="training_id_uindex", columns={"id"})})
 * @ORM\Entity
 */
class Training
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
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $title;

    /**
     * @var array|null
     *
     * @ORM\Column(name="questions", type="array", nullable=true)
     * @Gedmo\Translatable
     */
    private $questions;

    /**
     * @var array|null
     *
     * @ORM\Column(name="answers", type="array", nullable=true)
     */
    private $answers;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="answered", type="boolean", nullable=true)
     */
    private $answered = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="available_for_all", type="boolean", nullable=true)
     */
    private $availableForAll = true;

    /**
     * @var string
     *
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    private $picture;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="training_has_user",
     *   joinColumns={
     *     @ORM\JoinColumn(name="training_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *   }
     * )
     */
    private $users;

    /**
     * @param int $id
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

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
     * @return array|null
     */
    public function getQuestions(): ?array
    {
        return $this->questions;
    }

    /**
     * @param array|null $questions
     */
    public function setQuestions(?array $questions): void
    {
        $this->questions = $questions;
    }

    /**
     * @return array|null
     */
    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    /**
     * @param array|null $answers
     */
    public function setAnswers(?array $answers): void
    {
        $this->answers = $answers;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function isAnswered(): bool
    {
        return $this->answered;
    }

    /**
     * @param bool $answered
     */
    public function setAnswered(bool $answered): void
    {
        $this->answered = $answered;
    }

    /**
     * @return bool
     */
    public function isAvailableForAll(): bool
    {
        return $this->availableForAll;
    }

    /**
     * @param bool $availableForAll
     */
    public function setAvailableForAll(bool $availableForAll): void
    {
        $this->availableForAll = $availableForAll;
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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $users
     */
    public function setUsers($users): void
    {
        $this->users = $users;
    }

}

