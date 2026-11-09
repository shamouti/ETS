<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SessionDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public string $language = '';

    #[Assert\NotBlank]
    #[Assert\Date]
    public string $date = '';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/', message: 'Heure invalide, format attendu HH:MM.')]
    public string $time = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public string $location = '';

    #[Assert\Positive]
    public int $places = 0;

    #[Assert\PositiveOrZero]
    #[Assert\LessThanOrEqual(100000)]
    public float $price = 0.0;
}
