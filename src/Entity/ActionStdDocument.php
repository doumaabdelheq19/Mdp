<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActionStdDocument
 *
 * @ORM\Table(name="action_std_document", uniqueConstraints={@ORM\UniqueConstraint(name="action_std_document_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="action_std_document_action_std_id_fk", columns={"action_std_id"}), @ORM\Index(name="action_std_document_manager_id_fk", columns={"manager_id"})})
 * @ORM\Entity
 */
class ActionStdDocument
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
     * @var ActionStd
     *
     * @ORM\ManyToOne(targetEntity="ActionStd", inversedBy="documents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="action_std_id", referencedColumnName="id")
     * })
     */
    private $actionStd;

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
     * @return ActionStd
     */
    public function getActionStd()
    {
        return $this->actionStd;
    }

    /**
     * @param ActionStd $actionStd
     */
    public function setActionStd($actionStd)
    {
        $this->actionStd = $actionStd;
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


}

