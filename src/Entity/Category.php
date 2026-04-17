<?php

namespace App\Entity;

use App\Enum\TransactionType;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $name = '';

    #[Assert\NotNull]
    #[ORM\Column(enumType: TransactionType::class, length: 20)]
    private TransactionType $type = TransactionType::EXPENSE;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/')]
    #[ORM\Column(length: 7)]
    private string $color = '#64748b';

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Transaction::class)]
    private Collection $transactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->transactions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function setType(TransactionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCategory($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction) && $transaction->getCategory() === $this) {
            $transaction->setCategory(null);
        }

        return $this;
    }
}
