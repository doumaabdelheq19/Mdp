<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentSubtype
 *
 * @ORM\Table(name="document_subtype", uniqueConstraints={@ORM\UniqueConstraint(name="document_subtype_id_uindex", columns={"id"})})
 * @ORM\Entity
 */
class DocumentSubtype
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
     */
    private $libelle;

    /**
     * @var DocumentType
     *
     * @ORM\ManyToOne(targetEntity="DocumentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * })
     */
    private $type;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Document", mappedBy="type")
     */
    private $documents;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->documents = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function getType(): DocumentType
    {
        return $this->type;
    }

    /**
     * @param DocumentType $type
     */
    public function setType(DocumentType $type): void
    {
        $this->type = $type;
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

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->getLibelle();
    }

}

