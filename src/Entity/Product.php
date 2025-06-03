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

    #[ORM\Column(nullable: true)]
    #[Groups(["product:read", "product:write"])]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(["product:read", "product:write"])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["product:read"])]
    private ?User $user = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\ManyToMany(targetEntity: Order::class, mappedBy: 'products')]
    private Collection $orders;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?bool $isRecurring = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recurringFrequency = null;

    #[ORM\Column(length: 255)]
    private ?string $pickingAddress = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $availabilities = [];

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $images = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->dietaryPreferences = new ArrayCollection();
        $this->orders = new ArrayCollection();
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

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->addProduct($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            $order->removeProduct($this);
        }

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function isRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    public function setIsRecurring(bool $isRecurring): static
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }

    public function getRecurringFrequency(): ?string
    {
        return $this->recurringFrequency;
    }

    public function setRecurringFrequency(?string $recurringFrequency): static
    {
        $this->recurringFrequency = $recurringFrequency;

        return $this;
    }

    public function getPickingAddress(): ?string
    {
        return $this->pickingAddress;
    }

    public function setPickingAddress(string $pickingAddress): static
    {
        $this->pickingAddress = $pickingAddress;

        return $this;
    }

    public function getAvailabilities(): array
    {
        return $this->availabilities;
    }

    public function setAvailabilities(array $availabilities): static
    {
        $this->availabilities = $availabilities;

        return $this;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(string|array $images): static
    {
        if (is_string($images)) {
            $this->images = json_decode($images, true) ?? [];
        } else {
            $this->images = $images;
        }
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDaysUntilExpiration(): int
    {
        $today = new \DateTimeImmutable('today');
        return $today->diff($this->expiresAt)->days;
    }

    public function getFirstImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function hasAvailablePickupToday(): bool
    {
        $today = strtolower((new \DateTimeImmutable())->format('l'));
        $frenchDays = [
            'monday' => 'lundi',
            'tuesday' => 'mardi',
            'wednesday' => 'mercredi',
            'thursday' => 'jeudi',
            'friday' => 'vendredi',
            'saturday' => 'samedi',
            'sunday' => 'dimanche'
        ];

        $todayFrench = $frenchDays[$today] ?? '';

        foreach ($this->availabilities as $schedule) {
            if (isset($schedule['day']) &&
                strtolower($schedule['day']) === $todayFrench &&
                ($schedule['isEnabled'] ?? false)) {
                return true;
            }
        }

        return false;
    }
}
