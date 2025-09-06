<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\ManyToOne(inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeFonds $fonds = null;

    #[ORM\Column]
    private ?int $dureePrevue = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeReelle = null;

    #[ORM\Column(length: 255)]
    private ?string $lieuPrevu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieuReel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $budgetPrevu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budgetReel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePrevueDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePrevueFin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReelleDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReelleFin = null;

    #[ORM\ManyToOne(inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StatutActivite $statutActivite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: DepenseFormation::class, orphanRemoval: true)]
    private Collection $depenseFormations;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: UserFormation::class, orphanRemoval: true)]
    private Collection $userFormations;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: DocumentFormation::class, orphanRemoval: true)]
    private Collection $documentFormations;

    public function __construct()
    {
        $this->depenseFormations = new ArrayCollection();
        $this->userFormations = new ArrayCollection();
        $this->documentFormations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getFonds(): ?TypeFonds
    {
        return $this->fonds;
    }

    public function setFonds(?TypeFonds $fonds): static
    {
        $this->fonds = $fonds;

        return $this;
    }

    public function getDureePrevue(): ?int
    {
        return $this->dureePrevue;
    }

    public function setDureePrevue(int $dureePrevue): static
    {
        $this->dureePrevue = $dureePrevue;

        return $this;
    }

    public function getDureeReelle(): ?int
    {
        return $this->dureeReelle;
    }

    public function setDureeReelle(?int $dureeReelle): static
    {
        $this->dureeReelle = $dureeReelle;

        return $this;
    }

    public function getLieuPrevu(): ?string
    {
        return $this->lieuPrevu;
    }

    public function setLieuPrevu(string $lieuPrevu): static
    {
        $this->lieuPrevu = $lieuPrevu;

        return $this;
    }

    public function getLieuReel(): ?string
    {
        return $this->lieuReel;
    }

    public function setLieuReel(?string $lieuReel): static
    {
        $this->lieuReel = $lieuReel;

        return $this;
    }

    public function getBudgetPrevu(): ?string
    {
        return $this->budgetPrevu;
    }

    public function setBudgetPrevu(string $budgetPrevu): static
    {
        $this->budgetPrevu = $budgetPrevu;

        return $this;
    }

    public function getBudgetReel(): ?string
    {
        return $this->budgetReel;
    }

    public function setBudgetReel(?string $budgetReel): static
    {
        $this->budgetReel = $budgetReel;

        return $this;
    }

    public function getDatePrevueDebut(): ?\DateTimeInterface
    {
        return $this->datePrevueDebut;
    }

    public function setDatePrevueDebut(\DateTimeInterface $datePrevueDebut): static
    {
        $this->datePrevueDebut = $datePrevueDebut;

        return $this;
    }

    public function getDatePrevueFin(): ?\DateTimeInterface
    {
        return $this->datePrevueFin;
    }

    public function setDatePrevueFin(\DateTimeInterface $datePrevueFin): static
    {
        $this->datePrevueFin = $datePrevueFin;

        return $this;
    }

    public function getDateReelleDebut(): ?\DateTimeInterface
    {
        return $this->dateReelleDebut;
    }

    public function setDateReelleDebut(?\DateTimeInterface $dateReelleDebut): static
    {
        $this->dateReelleDebut = $dateReelleDebut;

        return $this;
    }

    public function getDateReelleFin(): ?\DateTimeInterface
    {
        return $this->dateReelleFin;
    }

    public function setDateReelleFin(?\DateTimeInterface $dateReelleFin): static
    {
        $this->dateReelleFin = $dateReelleFin;

        return $this;
    }

    public function getStatutActivite(): ?StatutActivite
    {
        return $this->statutActivite;
    }

    public function setStatutActivite(?StatutActivite $statutActivite): static
    {
        $this->statutActivite = $statutActivite;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statutActivite?->getCode();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return Collection<int, DepenseFormation>
     */
    public function getDepenseFormations(): Collection
    {
        return $this->depenseFormations;
    }

    public function addDepenseFormation(DepenseFormation $depenseFormation): static
    {
        if (!$this->depenseFormations->contains($depenseFormation)) {
            $this->depenseFormations->add($depenseFormation);
            $depenseFormation->setFormation($this);
        }

        return $this;
    }

    public function removeDepenseFormation(DepenseFormation $depenseFormation): static
    {
        if ($this->depenseFormations->removeElement($depenseFormation)) {
            // set the owning side to null (unless already changed)
            if ($depenseFormation->getFormation() === $this) {
                $depenseFormation->setFormation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserFormation>
     */
    public function getUserFormations(): Collection
    {
        return $this->userFormations;
    }

    public function addUserFormation(UserFormation $userFormation): static
    {
        if (!$this->userFormations->contains($userFormation)) {
            $this->userFormations->add($userFormation);
            $userFormation->setFormation($this);
        }

        return $this;
    }

    public function removeUserFormation(UserFormation $userFormation): static
    {
        if ($this->userFormations->removeElement($userFormation)) {
            // set the owning side to null (unless already changed)
            if ($userFormation->getFormation() === $this) {
                $userFormation->setFormation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DocumentFormation>
     */
    public function getDocumentFormations(): Collection
    {
        return $this->documentFormations;
    }

    public function addDocumentFormation(DocumentFormation $documentFormation): static
    {
        if (!$this->documentFormations->contains($documentFormation)) {
            $this->documentFormations->add($documentFormation);
            $documentFormation->setFormation($this);
        }

        return $this;
    }

    public function removeDocumentFormation(DocumentFormation $documentFormation): static
    {
        if ($this->documentFormations->removeElement($documentFormation)) {
            // set the owning side to null (unless already changed)
            if ($documentFormation->getFormation() === $this) {
                $documentFormation->setFormation(null);
            }
        }

        return $this;
    }
}
