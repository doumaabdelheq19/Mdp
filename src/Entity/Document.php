<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Document
 *
 * @ORM\Table(name="document", uniqueConstraints={@ORM\UniqueConstraint(name="document_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="document_manager_id_fk", columns={"manager_id"}), @ORM\Index(name="document_document_type_id_fk", columns={"type_id"})})
 * @ORM\Entity
 */
class Document
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
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $filename;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`translated_en`", type="boolean", nullable=true)
     */
    private $translatedEn = false;

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
     * @var DocumentType
     *
     * @ORM\ManyToOne(targetEntity="DocumentType", inversedBy="documents")
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
     * Get the document name, fallback to French if English is missing.
     * @return string
     */
    public function getName()
    {
        return !empty($this->name) ? $this->name : ($this->getFrenchName() ?: 'Unnamed Document');
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
     * @return bool
     */
    public function isTranslatedEn()
    {
        return $this->translatedEn;
    }

    /**
     * @param bool $translatedEn
     */
    public function setTranslatedEn(bool $translatedEn)
    {
        $this->translatedEn = $translatedEn;
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
     * @return DocumentType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param DocumentType $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Fetch the French name if available.
     * @return string|null
     */
    private function getFrenchName()
    {
        // Use Gedmo translations if available
        return $this->name ?: null;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
