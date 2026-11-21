<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfilePersonalDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_customer_can_save_personal_details_and_upload_a_photo_through_the_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.update'), [
            '_method' => 'PUT',
            ...$this->payload($user),
            'date_of_birth' => '2000-02-29',
            'gender' => 'female',
            'profile_image' => $this->image(),
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile'));

        $user->refresh();
        $this->assertSame('2000-02-29', $user->date_of_birth->format('Y-m-d'));
        $this->assertSame(Gender::Female, $user->gender);
        $this->assertStringStartsWith('users/profiles/'.$user->getKey().'/', $user->profile_path);
        Storage::disk('public')->assertExists($user->profile_path);
        $this->get(route('profile'))->assertOk()
            ->assertSee($user->profile_image_url)
            ->assertSee('value="2000-02-29"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="gender"', false)
            ->assertSee('id="pane-billing"', false);
    }

    public function test_omitted_optional_fields_keep_existing_data_and_photo(): void
    {
        $user = $this->userWithPhoto();
        $user->update(['date_of_birth' => '1999-03-01', 'gender' => Gender::Other]);
        $path = $user->profile_path;
        $this->actingAs($user)->putJson(route('profile.update'), $this->payload($user))->assertOk();
        $this->assertSame('1999-03-01', $user->fresh()->date_of_birth->format('Y-m-d'));
        $this->assertSame(Gender::Other, $user->fresh()->gender);
        $this->assertSame($path, $user->fresh()->profile_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_optional_details_can_be_cleared(): void
    {
        $user = User::factory()->create(['date_of_birth' => '2000-01-01', 'gender' => Gender::Male]);
        $this->actingAs($user)->putJson(route('profile.update'), [
            ...$this->payload($user), 'date_of_birth' => '', 'gender' => '',
        ])->assertOk();
        $this->assertNull($user->fresh()->date_of_birth);
        $this->assertNull($user->fresh()->gender);
    }

    public function test_replacement_and_removal_delete_only_the_current_users_managed_photo(): void
    {
        $user = $this->userWithPhoto();
        $other = $this->userWithPhoto();
        $oldPath = $user->profile_path;
        $this->actingAs($user)->post(route('profile.update'), [
            '_method' => 'PUT', ...$this->payload($user), 'profile_image' => $this->image(),
        ])->assertSessionHasNoErrors();
        $newPath = $user->fresh()->profile_path;
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
        $this->putJson(route('profile.update'), [
            ...$this->payload($user), 'remove_profile_image' => true,
        ])->assertOk();
        $this->assertNull($user->fresh()->profile_path);
        Storage::disk('public')->assertMissing($newPath);
        Storage::disk('public')->assertExists($other->profile_path);
        $this->assertStringEndsWith('/images/default-user.svg', $user->fresh()->profile_image_url);
    }

    public static function invalidDetails(): array
    {
        return [
            'future birthday' => ['date_of_birth', '2999-01-01'],
            'impossible birthday' => ['date_of_birth', '2001-02-29'],
            'wrong date format' => ['date_of_birth', '29/02/2000'],
            'invalid gender' => ['gender', 'admin'],
        ];
    }

    #[DataProvider('invalidDetails')]
    public function test_invalid_details_leave_the_profile_unchanged(string $field, string $value): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson(route('profile.update'), [
            ...$this->payload($user), $field => $value,
        ])->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertNull($user->fresh()->date_of_birth);
        $this->assertNull($user->fresh()->gender);
    }

    public function test_invalid_uploads_and_conflicting_removal_do_not_replace_the_photo(): void
    {
        $user = $this->userWithPhoto();
        $path = $user->profile_path;
        foreach ([
            UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            $this->image()->size(2049),
        ] as $file) {
            $this->actingAs($user)->postJson(route('profile.update'), [
                '_method' => 'PUT', ...$this->payload($user), 'profile_image' => $file,
            ])->assertUnprocessable()->assertJsonValidationErrors('profile_image');
        }
        $this->postJson(route('profile.update'), [
            '_method' => 'PUT', ...$this->payload($user),
            'profile_image' => $this->image(), 'remove_profile_image' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('profile_image');
        $this->assertSame($path, $user->fresh()->profile_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_profile_update_cannot_modify_role_or_another_accounts_photo(): void
    {
        $user = User::factory()->create();
        $other = $this->userWithPhoto();
        $this->actingAs($user)->putJson(route('profile.update'), [
            ...$this->payload($user), 'role' => 'admin', 'user_id' => $other->getKey(),
            'profile_path' => $other->profile_path, 'date_of_birth' => '2000-01-01',
        ])->assertOk();
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertNull($user->fresh()->profile_path);
        $this->assertNull($other->fresh()->date_of_birth);
        Storage::disk('public')->assertExists($other->profile_path);
    }

    public function test_failed_database_save_cleans_up_the_new_upload_and_keeps_the_old_photo(): void
    {
        $user = $this->userWithPhoto();
        User::updating(fn () => throw new \RuntimeException('Simulated save failure'));
        $this->actingAs($user)->postJson(route('profile.update'), [
            '_method' => 'PUT', ...$this->payload($user), 'profile_image' => $this->image(),
        ])->assertServerError();
        $this->assertSame($user->profile_path, $user->fresh()->profile_path);
        Storage::disk('public')->assertExists($user->profile_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_guests_cannot_update_personal_details(): void
    {
        $this->putJson(route('profile.update'), [
            'date_of_birth' => '2000-01-01', 'gender' => 'male',
        ])->assertUnauthorized();
    }

    private function payload(User $user): array
    {
        return ['username' => $user->username, 'email' => $user->email, 'phone_number' => $user->phone_number];
    }

    private function image(): File
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1sAAAAASUVORK5CYII='
        ));
    }

    private function userWithPhoto(): User
    {
        $user = User::factory()->create();
        $user->profile_path = $this->image()->store('users/profiles/'.$user->getKey(), 'public');
        $user->save();

        return $user;
    }
}
