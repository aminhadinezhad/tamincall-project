<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * A 1–5 rating as five stars, with the level's name beside them («خیلی ناراضی» … «خیلی راضی»),
 * so the answer the customer gives in words maps straight onto a star without counting.
 */
class StarRating extends Field
{
    protected string $view = 'filament.forms.components.star-rating';

    /** @var array<int, string> */
    protected array $levels = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules(['nullable', 'integer', 'between:1,5']);
    }

    /**
     * @param  array<int, string>  $levels  the name of each star, keyed 1 to 5
     */
    public function levels(array $levels): static
    {
        $this->levels = $levels;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getLevels(): array
    {
        return $this->levels;
    }
}
