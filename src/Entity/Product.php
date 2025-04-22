<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(["product:read"])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["product:read", "product:write"])]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(["product:read", "product:write"])]
    private ?\DateTimeImmutable $expiresAt = null;

    /**
     * @var Collection<int, DietaryPreference>
     */
    #[ORM\ManyToMany(targetEntity: DietaryPreference::class, inversedBy: 'products')]
    #[Groups(["product:read", "product:write"])]
    private Collection $dietaryPreferences;

    #[ORM\Column]
    #[Groups(["product:read", "product:write"])]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(["product:read", "product:write"])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["product:read"])]
    private ?User $user = null;

    public function __construct()
    {
        $this->dietaryPreferences = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return Collection<int, DietaryPreference>
     */
    public function getDietaryPreferences(): Collection
    {
        return $this->dietaryPreferences;
    }

    public function addDietaryPreference(DietaryPreference $dietaryPreference): static
    {
        if (!$this->dietaryPreferences->contains($dietaryPreference)) {
            $this->dietaryPreferences->add($dietaryPreference);
        }

        return $this;
    }

    public function removeDietaryPreference(DietaryPreference $dietaryPreference): static
    {
        $this->dietaryPreferences->removeElement($dietaryPreference);

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
