<?php

namespace App\Filament\Widgets\Concerns;

/**
 * One look for every report chart: Kalameh, right-to-left legend and tooltips, recessive grid,
 * thin marks. Colours are the validated two-series pair (blue, orange) and a red–gray–blue
 * diverging scale for satisfaction.
 */
class ChartStyle
{
    public const BLUE = '#2a78d6';

    public const ORANGE = '#eb6834';

    /** 1 (very unhappy) … 5 (very happy): red arm, gray midpoint, blue arm. */
    public const SATISFACTION = ['#c93434', '#eb8f8e', '#b0afaa', '#86b6ef', '#2a78d6'];

    public const GRID = 'rgba(128, 128, 128, 0.15)';

    /**
     * Categorical slots in fixed order (validated: adjacent pairs pass colour-blind and normal-vision
     * separation). A category always keeps its slot, whatever its rank. Slots 3–5 sit under 3:1
     * against white, so every donut names each slice with its percentage in the legend.
     */
    public const CATEGORICAL = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300'];

    /** Donut: no axes, a white gap between slices, legend underneath with names and percentages. */
    public static function doughnutOptions(): array
    {
        $font = ['family' => 'Kalameh, Tahoma, sans-serif', 'size' => 12];

        return [
            'maintainAspectRatio' => false,
            'cutout' => '62%',
            'layout' => ['padding' => 8],
            'plugins' => [
                'legend' => [
                    'rtl' => true,
                    'position' => 'bottom',
                    'labels' => ['font' => $font, 'usePointStyle' => true, 'pointStyle' => 'circle', 'boxWidth' => 8, 'padding' => 14],
                ],
                'tooltip' => [
                    'rtl' => true,
                    'textDirection' => 'rtl',
                    'titleFont' => $font,
                    'bodyFont' => $font,
                    'padding' => 10,
                ],
            ],
        ];
    }

    /** Dataset look for a donut: slices separated by the surface colour. */
    public static function doughnutDataset(array $data, array $colors): array
    {
        return [
            'data' => $data,
            'backgroundColor' => $colors,
            'borderColor' => '#ffffff',
            'borderWidth' => 2,
            'hoverOffset' => 6,
        ];
    }

    public static function options(array $overrides = []): array
    {
        $font = ['family' => 'Kalameh, Tahoma, sans-serif', 'size' => 12];

        return array_replace_recursive([
            'maintainAspectRatio' => false,
            'locale' => 'fa-IR',
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => [
                    'rtl' => true,
                    'position' => 'bottom',
                    'labels' => ['font' => $font, 'usePointStyle' => true, 'boxWidth' => 8, 'padding' => 16],
                ],
                'tooltip' => [
                    'rtl' => true,
                    'textDirection' => 'rtl',
                    'titleFont' => $font,
                    'bodyFont' => $font,
                    'padding' => 10,
                    'boxPadding' => 4,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['font' => $font],
                    'border' => ['display' => false],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => self::GRID],
                    'ticks' => ['font' => $font, 'precision' => 0],
                    'border' => ['display' => false],
                ],
            ],
        ], $overrides);
    }
}
