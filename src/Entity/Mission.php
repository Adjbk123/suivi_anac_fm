<?php

namespace App\Entity;

use App\Repository\MissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionRepository::class)]
class Mission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'missions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Direction $direction = null;

    #[ORM\ManyToOne(inversedBy: 'missions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeFonds $fonds = null;

    #[ORM\Column(length: 255)]
    private ?string $lieuPrevu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieuReel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePrevueDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePrevueFin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReelleDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReelleFin = null;

    #[ORM\ManyToOne(inversedBy: 'missions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StatutActivite $statutActivite = null;

    #[ORM\Column]
    private ?int $dureePrevue = null;

    #[ORM\Column]
    private ?float $budgetPrevu = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: DepenseMission::class, orphanRemoval: true)]
    private Collection $depenseMissions;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: UserMission::class, orphanRemoval: true)]
    private Collection $userMissions;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: DocumentMission::class, orphanRemoval: true)]
    private Collection $documentMissions;

    public function __construct()
    {
        $this->depenseMissions = new ArrayCollection();
        $this->userMissions = new ArrayCollection();
        $this->documentMissions = new ArrayCollection();
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

    /**
     * @return Collection<int, DepenseMission>
     */
    public function getDepenseMissions(): Collection
    {
        return $this->depenseMissions;
    }

    public function addDepenseMission(DepenseMission $depenseMission): static
    {
        if (!$this->depenseMissions->contains($depenseMission)) {
            $this->depenseMissions->add($depenseMission);
            $depenseMission->setMission($this);
        }

        return $this;
    }

    public function removeDepenseMission(DepenseMission $depenseMission): static
    {
        if ($this->depenseMissions->removeElement($depenseMission)) {
            // set the owning side to null (unless already changed)
            if ($depenseMission->getMission() === $this) {
                $depenseMission->setMission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserMission>
     */
    public function getUserMissions(): Collection
    {
        return $this->userMissions;
    }

    public function addUserMission(UserMission $userMission): static
    {
        if (!$this->userMissions->contains($userMission)) {
            $this->userMissions->add($userMission);
            $userMission->setMission($this);
        }

        return $this;
    }

    public function removeUserMission(UserMission $userMission): static
    {
        if ($this->userMissions->removeElement($userMission)) {
            // set the owning side to null (unless already changed)
            if ($userMission->getMission() === $this) {
                $userMission->setMission(null);
            }
        }

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

    public function getBudgetPrevu(): ?float
    {
        return $this->budgetPrevu;
    }

    public function setBudgetPrevu(float $budgetPrevu): static
    {
        $this->budgetPrevu = $budgetPrevu;

        return $this;
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
     * @return Collection<int, DocumentMission>
     */
    public function getDocumentMissions(): Collection
    {
        return $this->documentMissions;
    }

    public function addDocumentMission(DocumentMission $documentMission): static
    {
        if (!$this->documentMissions->contains($documentMission)) {
            $this->documentMissions->add($documentMission);
            $documentMission->setMission($this);
        }

        return $this;
    }

    public function removeDocumentMission(DocumentMission $documentMission): static
    {
        if ($this->documentMissions->removeElement($documentMission)) {
            // set the owning side to null (unless already changed)
            if ($documentMission->getMission() === $this) {
                $documentMission->setMission(null);
            }
        }

        return $this;
    }
}
