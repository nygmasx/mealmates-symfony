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
    #[Groups(["product:read", "product:summary"])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["product:read", "product:write", "product:summary"])]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(["product:read", "product:write", "product:summary"])]
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
    #[Groups(["product:read", "product:summary"])]
    private ?User $user = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\ManyToMany(targetEntity: Order::class, mappedBy: 'products')]
    private Collection $orders;

    #[ORM\Column]
    #[Groups(["product:read", "product:write"])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(["product:read", "product:write"])]
    private ?bool $isRecurring = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["product:read", "product:write"])]
    private ?string $recurringFrequency = null;

    #[ORM\Column(length: 255)]
    #[Groups(["product:read", "product:write"])]
    private ?string $pickingAddress = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(["product:read", "product:write"])]
    private array $availabilities = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(["product:read", "product:write"])]
    private array $images = [];

    #[ORM\Column]
    #[Groups(["product:read", "product:write"])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Chat>
     */
    #[ORM\OneToMany(targetEntity: Chat::class, mappedBy: 'relatedProduct')]
    private Collection $chats;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isAlertEnabled = false;

    #[ORM\Column(nullable: true)]
    private ?int $alertDaysBefore = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastAlertSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $alertCount = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'product')]
    private Collection $bookings;

    public function __construct()
    {
        $this->dietaryPreferences = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->chats = new ArrayCollection();
        $this->bookings = new ArrayCollection();
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

    public function setPrice(?float $price): static
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

    /**
     * @return Collection<int, Chat>
     */
    public function getChats(): Collection
    {
        return $this->chats;
    }

    public function addChat(Chat $chat): static
    {
        if (!$this->chats->contains($chat)) {
            $this->chats->add($chat);
            $chat->setRelatedProduct($this);
        }

        return $this;
    }

    public function removeChat(Chat $chat): static
    {
        if ($this->chats->removeElement($chat)) {
            // set the owning side to null (unless already changed)
            if ($chat->getRelatedProduct() === $this) {
                $chat->setRelatedProduct(null);
            }
        }

        return $this;
    }

    public function isAlertEnabled(): ?bool
    {
        return $this->isAlertEnabled;
    }

    public function setIsAlertEnabled(bool $isAlertEnabled): static
    {
        $this->isAlertEnabled = $isAlertEnabled;

        return $this;
    }

    public function getAlertDaysBefore(): ?int
    {
        return $this->alertDaysBefore;
    }

    public function setAlertDaysBefore(?int $alertDaysBefore): static
    {
        $this->alertDaysBefore = $alertDaysBefore;

        return $this;
    }

    public function getLastAlertSentAt(): ?\DateTimeImmutable
    {
        return $this->lastAlertSentAt;
    }

    public function setLastAlertSentAt(?\DateTimeImmutable $lastAlertSentAt): static
    {
        $this->lastAlertSentAt = $lastAlertSentAt;

        return $this;
    }

    public function getAlertCount(): ?int
    {
        return $this->alertCount;
    }

    public function setAlertCount(?int $alertCount): static
    {
        $this->alertCount = $alertCount;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setProduct($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getProduct() === $this) {
                $booking->setProduct(null);
            }
        }

        return $this;
    }
}
