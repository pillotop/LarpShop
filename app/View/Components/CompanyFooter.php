<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class CompanyFooter extends Component
{
    public array $sections = [
        [
            'title' => 'Company Info',
            'links' => [
                ['label' => 'About LarpShop', 'url' => '/about'],
                ['label' => 'Contact Us', 'url' => '/contact'],
                ['label' => 'Investors', 'url' => '/investors'],
                ['label' => 'Blog', 'url' => '/blog'],
                ['label' => 'Press', 'url' => '/press'],
            ],
        ],
        [
            'title' => 'Customer Service',
            'links' => [
                ['label' => 'Help Centre', 'url' => '/help'],
                ['label' => 'Privacy Policy', 'url' => '/privacy'],
                ['label' => 'Community Forum', 'url' => '/community'],
                ['label' => 'Product Recalls', 'url' => '/recalls'],
            ],
        ],
        [
            'title' => 'Career',
            'links' => [
                ['label' => 'Become an Accomplice', 'url' => '/careers'],
                ['label' => 'Sell on LarpShop', 'url' => '/sell'],
                ['label' => 'Advertise Your Products', 'url' => '/advertise'],
                ['label' => 'Larp Insurance', 'url' => '/insurance'],
            ],
        ],
    ];

    public array $legalLinks = [
        ['label' => 'Terms of Service', 'url' => '/terms'],
        ['label' => 'Legal', 'url' => '/legal'],
        ['label' => 'Privacy Policy', 'url' => '/privacy'],
        ['label' => 'Sitemap', 'url' => '/sitemap'],
    ];

    public function render(): View
    {
        return view('components.company-footer');
    }
}
