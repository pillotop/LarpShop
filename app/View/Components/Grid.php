<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Grid extends Component
{
    public array $columns;

    public string $gap;

    public function __construct(
        array $columns = ['default' => 2],
        string $gap = '3',
    ) {
        $this->columns = $columns;
        $this->gap = $gap;
    }

    public function render(): View
    {
        return view('components.grid');
    }

    public function gridClasses(): string
    {
        $classes = [];

        foreach ($this->columns as $breakpoint => $count) {
            $breakpoint = $breakpoint === 'xxl' ? '2xl' : $breakpoint;
            $prefix = $breakpoint === 'default'
                ? 'row-cols'
                : "{$breakpoint}:row-cols";

            $classes[] = "{$prefix}-{$count}";
        }

        $classes[] = "g-{$this->gap}";

        return implode(' ', $classes);
    }
}
