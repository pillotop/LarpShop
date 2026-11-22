<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\Vendors\DeliveryMode;
use App\Enums\Vendors\OnboardingStep;
use App\Enums\Vendors\SellerType;
use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use Database\Seeders\Support\DemoSeedContext;
use Illuminate\Database\Seeder;

/**
 * Seeds representative customers, admins, multi-store vendor owners, and onboarding applications.
 */
class DemoUserAndVendorSeeder extends Seeder
{
    public function __construct(private DemoSeedContext $context) {}

    public function run(): void
    {
        $password = $this->context->demoPasswordHash();
        $anchor = $this->context->anchor();

        $admin = $this->upsertUser(
            email: 'admin@admin.com',
            username: 'admin',
            role: 'admin',
            phone: null,
            gender: Gender::PreferNotToSay,
            dob: '1992-05-14',
            verifiedAt: $anchor->subDays(190),
            passwordHash: $password,
        );

        $this->upsertUser(
            email: 'demo-admin@example.test',
            username: 'demo_admin',
            role: 'admin',
            phone: '+15550100001',
            gender: Gender::PreferNotToSay,
            dob: '1994-03-20',
            verifiedAt: $anchor->subDays(185),
            passwordHash: $password,
        );

        $this->seedCustomers($password);
        $owners = $this->seedVendors($password, $admin);
        $this->seedExtraOnboardingApplications($password, $admin, $owners);
    }

    private function seedCustomers(string $password): void
    {
        $anchor = $this->context->anchor();
        $named = [
            ['johndoe', 'john@example.com', '+15550111001', Gender::Male, '1995-02-11', 180],
            ['janestewart', 'jane@example.com', '+15550111002', Gender::Female, '1996-07-19', 175],
            ['alexrivera', 'alex@example.com', '+15550111003', Gender::NonBinary, '1993-11-04', 170],
            ['mariagarcia', 'maria@example.com', '+15550111004', Gender::Female, '1991-09-23', 165],
            ['demo_customer', 'customer@example.test', '+15550111005', Gender::Male, '1997-04-12', 160],
        ];

        foreach ($named as [$username, $email, $phone, $gender, $dob, $daysAgo]) {
            $this->upsertUser($email, $username, 'customer', $phone, $gender, $dob, $anchor->subDays($daysAgo), $password);
        }

        $firstNames = ['Liam', 'Noah', 'Oliver', 'Emma', 'Ava', 'Sophia', 'Lucas', 'Mason', 'Mia', 'Ethan', 'Amelia', 'Harper'];
        $lastNames = ['Nguyen', 'Tran', 'Chen', 'Patel', 'Kim', 'Garcia', 'Miller', 'Davis', 'Wilson', 'Taylor', 'Clark', 'Lewis'];
        $genders = Gender::cases();

        for ($i = 1; $i <= 46; $i++) {
            $first = $firstNames[($i - 1) % count($firstNames)];
            $last = $lastNames[((int) (($i - 1) / count($firstNames)) + ($i * 3)) % count($lastNames)];
            $num = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $username = sprintf('demo_%s_%s_%s', strtolower($first), strtolower($last), $num);
            $email = sprintf('demo.customer.%s@example.test', $num);
            $verifiedAt = $i <= 44 ? $anchor->subDays(180 - ($i % 150)) : null;

            $this->upsertUser(
                email: $email,
                username: $username,
                role: 'customer',
                phone: sprintf('+1555020%04d', $i),
                gender: $genders[$i % count($genders)],
                dob: sprintf('%d-%02d-%02d', 1986 + ($i % 16), ($i % 12) + 1, ($i % 27) + 1),
                verifiedAt: $verifiedAt,
                passwordHash: $password,
            );
        }
    }

    /**
     * @return array<string, User>
     */
    private function seedVendors(string $password, User $admin): array
    {
        $anchor = $this->context->anchor();
        $stores = [
            [
                'username' => 'volttech',
                'email' => 'contact@volttech.example.test',
                'store_name' => 'VoltTech Gadgets',
                'description' => 'Smart televisions, mirrorless cameras, and everyday electronics tested for reliability.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::Worldwide,
                'destinations' => [],
                'submitted_days_ago' => 170,
                'review_hours' => 18,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'volttech',
                'email' => 'contact@volttech.example.test',
                'store_name' => 'VoltTech Outlet',
                'description' => 'Secondary warehouse outlet for certified clearance gadgets and camera kits by VoltTech.',
                'status' => 'approved',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::SelectedCountries,
                'destinations' => ['US', 'CA', 'GB'],
                'submitted_days_ago' => 95,
                'review_hours' => 24,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'volttech',
                'email' => 'contact@volttech.example.test',
                'store_name' => 'VoltTech Workshop',
                'description' => 'Legacy workshop storefront queued for manual onboarding verification.',
                'status' => 'pending',
                'app_status' => null,
            ],
            [
                'username' => 'audiopulse',
                'email' => 'support@audiopulse.example.test',
                'store_name' => 'AudioPulse Hi-Fi',
                'description' => 'Studio-tuned headphones, bookshelf speakers, and wireless sound systems.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::Domestic,
                'destinations' => [],
                'submitted_days_ago' => 160,
                'review_hours' => 12,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'cybergear',
                'email' => 'sales@cybergear.example.test',
                'store_name' => 'CyberGear Gaming',
                'description' => 'Mechanical keyboards, ultra-light gaming mice, and console controllers.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::Worldwide,
                'destinations' => [],
                'submitted_days_ago' => 150,
                'review_hours' => 16,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'nextgen',
                'email' => 'info@nextgen.example.test',
                'store_name' => 'NextGen Components',
                'description' => 'Ergonomic monitors, workspace docks, and productivity hardware.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::SelectedCountries,
                'destinations' => ['US', 'VN', 'SG', 'JP'],
                'submitted_days_ago' => 140,
                'review_hours' => 28,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'smarthome',
                'email' => 'hello@smarthome.example.test',
                'store_name' => 'SmartHome Innovations',
                'description' => 'Connected hubs, smart lighting fixtures, and home automation kits.',
                'status' => 'active',
                'seller_type' => SellerType::Individual,
                'delivery_mode' => DeliveryMode::Domestic,
                'destinations' => [],
                'submitted_days_ago' => 125,
                'review_hours' => 20,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'peakperformance',
                'email' => 'store@peakfit.example.test',
                'store_name' => 'Peak Performance Gear',
                'description' => 'Home gym dumbbells, training apparel, and indoor/outdoor team sports gear.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::Worldwide,
                'destinations' => [],
                'submitted_days_ago' => 115,
                'review_hours' => 15,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'trailblazer',
                'email' => 'sales@trailblazer.example.test',
                'store_name' => 'Trailblazer Outdoors',
                'description' => 'All-terrain bicycles, weatherproof tents, hiking footwear, and expedition packs.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::SelectedCountries,
                'destinations' => ['US', 'CA', 'AU'],
                'submitted_days_ago' => 105,
                'review_hours' => 22,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'hearthhaven',
                'email' => 'care@hearthhaven.example.test',
                'store_name' => 'Hearth & Haven Home',
                'description' => 'Cast-iron cookware, ceramic decor, natural linens, and handcrafted seating.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::Domestic,
                'destinations' => [],
                'submitted_days_ago' => 85,
                'review_hours' => 19,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'luxeloom',
                'email' => 'studio@luxeloom.example.test',
                'store_name' => 'Luxe & Loom Studio',
                'description' => 'Everyday leather accessories, skincare essentials, and artisanal textile craft supplies.',
                'status' => 'active',
                'seller_type' => SellerType::Individual,
                'delivery_mode' => DeliveryMode::Worldwide,
                'destinations' => [],
                'submitted_days_ago' => 65,
                'review_hours' => 14,
                'app_status' => VendorApplicationStatus::Approved,
            ],
            [
                'username' => 'demo_vendor',
                'email' => 'vendor@example.test',
                'store_name' => 'Demo Vendor Store',
                'description' => 'Curated showcase storefront operated by the primary demo vendor account.',
                'status' => 'active',
                'seller_type' => SellerType::Business,
                'delivery_mode' => DeliveryMode::Worldwide,
                'destinations' => [],
                'submitted_days_ago' => 50,
                'review_hours' => 10,
                'app_status' => VendorApplicationStatus::Approved,
            ],
        ];

        $owners = [];

        foreach ($stores as $index => $storeData) {
            $owner = $this->upsertUser(
                email: $storeData['email'],
                username: $storeData['username'],
                role: 'customer',
                phone: sprintf('+1555030%04d', $index + 1),
                gender: Gender::PreferNotToSay,
                dob: '1990-06-15',
                verifiedAt: $anchor->subDays(180),
                passwordHash: $password,
            );
            $owners[$storeData['username']] = $owner;

            $vendor = Vendor::updateOrCreate(
                ['store_name' => $storeData['store_name']],
                [
                    'user_id' => $owner->user_id,
                    'description' => $storeData['description'],
                    'status' => $storeData['status'],
                ]
            );

            if ($storeData['app_status'] === null) {
                continue;
            }

            $submittedAt = $anchor->subDays($storeData['submitted_days_ago']);
            $reviewedAt = $submittedAt->addHours($storeData['review_hours']);

            $application = VendorApplication::updateOrCreate(
                ['vendor_id' => $vendor->vendor_id],
                [
                    'user_id' => $owner->user_id,
                    'status' => $storeData['app_status'],
                    'current_step' => OnboardingStep::Review,
                    'seller_type' => $storeData['seller_type'],
                    'seller_country_code' => 'US',
                    'business_name' => $storeData['store_name'].' LLC',
                    'contact_name' => ucfirst($storeData['username']).' Manager',
                    'contact_email' => $storeData['email'],
                    'contact_phone' => sprintf('+1555030%04d', $index + 1),
                    'store_name' => $storeData['store_name'],
                    'description' => $storeData['description'],
                    'pickup_recipient_name' => $storeData['store_name'].' Dispatch',
                    'pickup_phone_number' => sprintf('+1555031%04d', $index + 1),
                    'pickup_country_code' => 'US',
                    'pickup_address_line_1' => sprintf('%d Commerce Way', 100 + $index * 15),
                    'pickup_city' => 'Austin',
                    'pickup_region' => 'TX',
                    'pickup_postal_code' => '78701',
                    'delivery_mode' => $storeData['delivery_mode'],
                    'submitted_at' => $submittedAt,
                    'reviewed_at' => $reviewedAt,
                    'reviewed_by' => $admin->user_id,
                ]
            );

            $this->syncDestinations($application, $storeData['destinations']);
        }

        return $owners;
    }

    /**
     * @param  array<string, User>  $owners
     */
    private function seedExtraOnboardingApplications(string $password, User $admin, array $owners): void
    {
        $anchor = $this->context->anchor();

        $applicants = [
            [
                'username' => 'nordic_craft_applicant',
                'email' => 'nordic.applicant@example.test',
                'store_name' => 'Nordic Craft Collective',
                'status' => VendorApplicationStatus::Submitted,
                'submitted_at' => $anchor->subDays(6),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
                'step' => OnboardingStep::Review,
            ],
            [
                'username' => 'urban_cycle_applicant',
                'email' => 'urbancycle.applicant@example.test',
                'store_name' => 'Urban Cycle Works',
                'status' => VendorApplicationStatus::Submitted,
                'submitted_at' => $anchor->subDays(2),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
                'step' => OnboardingStep::Review,
            ],
            [
                'username' => 'artisan_glass_applicant',
                'email' => 'artisanglass.applicant@example.test',
                'store_name' => 'Artisan Glass Imports',
                'status' => VendorApplicationStatus::Rejected,
                'submitted_at' => $anchor->subDays(18),
                'reviewed_at' => $anchor->subDays(17)->addHours(10),
                'reviewed_by' => $admin->user_id,
                'rejection_reason' => 'Pickup warehouse address could not be verified and product scope is incomplete.',
                'step' => OnboardingStep::Review,
            ],
            [
                'username' => 'atelier_draft_applicant',
                'email' => 'atelierdraft.applicant@example.test',
                'store_name' => 'Atelier Draft Supply',
                'status' => VendorApplicationStatus::Draft,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
                'step' => OnboardingStep::Shipping,
            ],
        ];

        foreach ($applicants as $idx => $data) {
            $user = $this->upsertUser(
                email: $data['email'],
                username: $data['username'],
                role: 'customer',
                phone: sprintf('+1555040%04d', $idx + 1),
                gender: Gender::PreferNotToSay,
                dob: '1993-08-10',
                verifiedAt: $anchor->subDays(45),
                passwordHash: $password,
            );

            $application = VendorApplication::updateOrCreate(
                ['user_id' => $user->user_id, 'store_name' => $data['store_name']],
                [
                    'status' => $data['status'],
                    'current_step' => $data['step'],
                    'seller_type' => SellerType::Individual,
                    'seller_country_code' => 'US',
                    'business_name' => $data['store_name'],
                    'contact_name' => ucwords(str_replace('_', ' ', $data['username'])),
                    'contact_email' => $data['email'],
                    'contact_phone' => sprintf('+1555040%04d', $idx + 1),
                    'description' => 'Sample vendor onboarding application in '.$data['status']->value.' status.',
                    'pickup_recipient_name' => $data['store_name'],
                    'pickup_phone_number' => sprintf('+1555041%04d', $idx + 1),
                    'pickup_country_code' => 'US',
                    'pickup_address_line_1' => sprintf('%d Market Street', 210 + $idx * 10),
                    'pickup_city' => 'Seattle',
                    'pickup_region' => 'WA',
                    'pickup_postal_code' => '98101',
                    'delivery_mode' => DeliveryMode::SelectedCountries,
                    'submitted_at' => $data['submitted_at'],
                    'reviewed_at' => $data['reviewed_at'],
                    'reviewed_by' => $data['reviewed_by'],
                    'rejection_reason' => $data['rejection_reason'],
                ]
            );

            $this->syncDestinations($application, ['US', 'CA']);
        }
    }

    /**
     * @param  array<int, string>  $countryCodes
     */
    private function syncDestinations(VendorApplication $application, array $countryCodes): void
    {
        $application->destinations()->whereNotIn('country_code', $countryCodes)->delete();

        foreach ($countryCodes as $code) {
            $application->destinations()->firstOrCreate(['country_code' => $code]);
        }
    }

    private function upsertUser(
        string $email,
        string $username,
        string $role,
        ?string $phone,
        Gender $gender,
        string $dob,
        mixed $verifiedAt,
        string $passwordHash,
    ): User {
        $user = User::query()
            ->where('email', $email)
            ->orWhere('username', $username)
            ->first() ?? new User;

        $user->forceFill([
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash,
            'phone_number' => $phone,
            'role' => $role,
            'gender' => $gender->value,
            'date_of_birth' => $dob,
            'email_verified_at' => $verifiedAt,
        ])->save();

        return $user;
    }
}
