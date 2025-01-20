<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDocument
 *
 * @ORM\Table(name="user_document", uniqueConstraints={@ORM\UniqueConstraint(name="user_document_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="user_document_subcontractor_id_fk", columns={"subcontractor_id"}), @ORM\Index(name="user_document_user_id_fk", columns={"user_id"})})
 * @ORM\Entity
 */
class UserDocument
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
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="user_filename", type="string", length=255, nullable=true)
     */
    private $userFilename;

    /**
     * @var Subcontractor
     *
     * @ORM\ManyToOne(targetEntity="Subcontractor", inversedBy="documents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="subcontractor_id", referencedColumnName="id")
     * })
     */
    private $subcontractor;

    /**
     * @var Action
     *
     * @ORM\ManyToOne(targetEntity="Action", inversedBy="documents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="action_id", referencedColumnName="id")
     * })
     */
    private $action;

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
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getUserFilename()
    {
        return $this->userFilename;
    }

    /**
     * @param string $userFilename
     */
    public function setUserFilename($userFilename)
    {
        $this->userFilename = $userFilename;
    }

    /**
     * @return Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param Action $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return Subcontractor
     */
    public function getSubcontractor()
    {
        return $this->subcontractor;
    }

    /**
     * @param Subcontractor $subcontractor
     */
    public function setSubcontractor($subcontractor)
    {
        $this->subcontractor = $subcontractor;
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

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->getName()??$this->getFilename();
    }


}

