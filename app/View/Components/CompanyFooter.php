<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CompanyFooter extends Component
{
    public array $sections = [
        [
            'title' => 'footer.company',
            'links' => [
                ['label' => 'footer.about', 'url' => '/about'],
                ['label' => 'footer.contact', 'url' => '/contact'],
                ['label' => 'footer.investors', 'url' => '/investors'],
                ['label' => 'footer.blog', 'url' => '/blog'],
                ['label' => 'footer.press', 'url' => '/press'],
            ],
        ],
        [
            'title' => 'footer.customer_service',
            'links' => [
                ['label' => 'footer.help', 'url' => '/help'],
                ['label' => 'footer.privacy', 'url' => '/privacy'],
                ['label' => 'footer.community', 'url' => '/community'],
                ['label' => 'footer.recalls', 'url' => '/recalls'],
            ],
        ],
        [
            'title' => 'footer.career',
            'links' => [
                ['label' => 'footer.accomplice', 'url' => '/careers'],
                ['label' => 'footer.sell', 'url' => '/sell'],
                ['label' => 'footer.advertise', 'url' => '/advertise'],
                ['label' => 'footer.insurance', 'url' => '/insurance'],
            ],
        ],
    ];

    public array $legalLinks = [
        ['label' => 'footer.terms', 'url' => '/terms'],
        ['label' => 'footer.legal', 'url' => '/legal'],
        ['label' => 'footer.privacy', 'url' => '/privacy'],
        ['label' => 'footer.sitemap', 'url' => '/sitemap'],
    ];

    public function render(): View
    {
        return view('components.company-footer');
    }
}
