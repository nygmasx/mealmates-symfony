<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(["profile:read"])]
    private ?Uuid $id = null;

    #[ORM\OneToOne(inversedBy: 'profile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["profile:read"])]
    private ?User $user = null;

    /**
     * @var Collection<int, DietaryPreference>
     */
    #[ORM\ManyToMany(targetEntity: DietaryPreference::class, inversedBy: 'profiles')]
    #[Groups(["profile:read", "profile:write"])]
    private Collection $dietaryPreferences;

    #[ORM\Column(length: 255)]
    #[Groups(["profile:read", "profile:write"])]
    private ?string $addressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["profile:read", "profile:write"])]
    private ?string $addressLine2 = null;

    #[ORM\Column(length: 255)]
    #[Groups(["profile:read", "profile:write"])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(["profile:read", "profile:write"])]
    private ?string $zipCode = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["profile:read"])]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["profile:read"])]
    private ?float $longitude = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'reviewedUserProfile')]
    #[Groups(["profile:read", "profile:write"])]
    private Collection $reviews;

    /**
     * @var Collection<int, Availability>
     */
    #[ORM\ManyToMany(targetEntity: Availability::class, inversedBy: 'profiles')]
    #[Groups(["profile:read", "profile:write"])]
    private Collection $availabilities;

    public function __construct()
    {
        $this->dietaryPreferences = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->availabilities = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

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

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): static
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): static
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setReviewedUserProfile($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getReviewedUserProfile() === $this) {
                $review->setReviewedUserProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Availability>
     */
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    public function addAvailability(Availability $availability): static
    {
        if (!$this->availabilities->contains($availability)) {
            $this->availabilities->add($availability);
        }

        return $this;
    }

    public function removeAvailability(Availability $availability): static
    {
        $this->availabilities->removeElement($availability);

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }
}
