<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubcontractorStdDocument
 *
 * @ORM\Table(name="subcontractor_std_document", uniqueConstraints={@ORM\UniqueConstraint(name="subcontractor_std_document_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="subcontractor_std_document_manager_id_fk", columns={"manager_id"}), @ORM\Index(name="subcontractor_std_document_subcontractor_std_id_fk", columns={"subcontractor_std_id"})})
 * @ORM\Entity
 */
class SubcontractorStdDocument
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
     * @var Manager
     *
     * @ORM\ManyToOne(targetEntity="Manager")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     * })
     */
    private $manager;

    /**
     * @var SubcontractorStd
     *
     * @ORM\ManyToOne(targetEntity="SubcontractorStd", inversedBy="documents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="subcontractor_std_id", referencedColumnName="id")
     * })
     */
    private $subcontractorStd;

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
     * @return SubcontractorStd
     */
    public function getSubcontractorStd()
    {
        return $this->subcontractorStd;
    }

    /**
     * @param SubcontractorStd $subcontractorStd
     */
    public function setSubcontractorStd($subcontractorStd)
    {
        $this->subcontractorStd = $subcontractorStd;
    }

}

