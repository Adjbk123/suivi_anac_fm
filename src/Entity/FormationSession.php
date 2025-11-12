<?php

namespace App\Entity;

use App\Repository\FormationSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationSessionRepository::class)]
class FormationSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\ManyToOne(inversedBy: 'formationSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Direction $direction = null;

    #[ORM\ManyToOne(inversedBy: 'formationSessions')]
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

    #[ORM\ManyToOne(inversedBy: 'formationSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StatutActivite $statutActivite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'formationSession', targetEntity: DepenseFormation::class, orphanRemoval: true)]
    private Collection $depenseFormations;

    #[ORM\OneToMany(mappedBy: 'formationSession', targetEntity: UserFormation::class, orphanRemoval: true)]
    private Collection $userFormations;

    #[ORM\OneToMany(mappedBy: 'formationSession', targetEntity: DocumentFormation::class, orphanRemoval: true)]
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

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;

        return $this;
    }

    public function getDirection(): ?Direction
    {
        return $this->direction;
    }

    public function setDirection(?Direction $direction): static
    {
        $this->direction = $direction;

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
            $depenseFormation->setFormationSession($this);
        }

        return $this;
    }

    public function removeDepenseFormation(DepenseFormation $depenseFormation): static
    {
        if ($this->depenseFormations->removeElement($depenseFormation)) {
            // set the owning side to null (unless already changed)
            if ($depenseFormation->getFormationSession() === $this) {
                $depenseFormation->setFormationSession(null);
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
            $userFormation->setFormationSession($this);
        }

        return $this;
    }

    public function removeUserFormation(UserFormation $userFormation): static
    {
        if ($this->userFormations->removeElement($userFormation)) {
            // set the owning side to null (unless already changed)
            if ($userFormation->getFormationSession() === $this) {
                $userFormation->setFormationSession(null);
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
            $documentFormation->setFormationSession($this);
        }

        return $this;
    }

    public function removeDocumentFormation(DocumentFormation $documentFormation): static
    {
        if ($this->documentFormations->removeElement($documentFormation)) {
            // set the owning side to null (unless already changed)
            if ($documentFormation->getFormationSession() === $this) {
                $documentFormation->setFormationSession(null);
            }
        }

        return $this;
    }
}

