<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'sessions')]
class TestSession
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $language = '';

    /** Format YYYY-MM-DD */
    #[ODM\Field(type: 'string')]
    private string $date = '';

    /** Format HH:MM */
    #[ODM\Field(type: 'string')]
    private string $time = '';

    #[ODM\Field(type: 'string')]
    private string $location = '';

    /** Prix en dirhams marocains, MAD (0 = gratuit) */
    #[ODM\Field(type: 'float')]
    private float $price = 0.0;

    /** Nombre total de places */
    #[ODM\Field(type: 'int')]
    private int $places = 0;

    /** Places restantes (décrémentée atomiquement à la réservation) */
    #[ODM\Field(type: 'int')]
    private int $remaining = 0;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function setTime(string $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = round($price, 2);

        return $this;
    }

    public function getPlaces(): int
    {
        return $this->places;
    }

    public function setPlaces(int $places): static
    {
        $this->places = $places;

        return $this;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function setRemaining(int $remaining): static
    {
        $this->remaining = $remaining;

        return $this;
    }
}
