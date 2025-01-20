<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * DocumentType
 *
 * @ORM\Table(name="document_type", uniqueConstraints={@ORM\UniqueConstraint(name="document_type_id_uindex", columns={"id"})})
 * @ORM\Entity(repositoryClass="App\Repository\DocumentTypeRepository")
 */
class DocumentType
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
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $libelle;

    /**
     * @var DocumentType
     *
     * @ORM\ManyToOne(targetEntity="DocumentType", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="DocumentType", mappedBy="parent")
     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Document", mappedBy="type")
     */
    private $documents;

    /**
     * @Gedmo\Locale
     */
    private $locale;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->documents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @param string $libelle
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
    }

    /**
     * @return DocumentType
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param DocumentType $parent
     */
    public function setParent(DocumentType $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $children
     */
    public function setChildren($children): void
    {
        $this->children = $children;
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
     * @param string $locale
     *
     * @return static
     */
    public function setTranslatableLocale($locale) {
        $this->locale = $locale;
        return $this;
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->getLibelle();
    }

}

