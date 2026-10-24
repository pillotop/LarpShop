<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCategory(string $name, string $slug, ?int $parentId = null): Category
    {
        return Category::create(['name' => $name, 'slug' => $slug, 'parent_id' => $parentId]);
    }

    public function test_guest_and_customer_cannot_access_category_management(): void
    {
        $this->get('/admin/categories')->assertRedirect('/login');

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/categories')->assertForbidden();
        $this->actingAs($customer)->post('/admin/categories', ['name' => 'Hack'])->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Hack']);
    }

    public function test_admin_can_list_and_search_categories(): void
    {
        $this->makeCategory('Armor', 'armor');
        $this->makeCategory('Swords', 'swords');

        $this->actingAs($this->admin())->get('/admin/categories')
            ->assertOk()->assertSee('<code>armor</code>', false)->assertSee('<code>swords</code>', false);

        $this->actingAs($this->admin())->get('/admin/categories?q=sword')
            ->assertOk()->assertSee('<code>swords</code>', false)->assertDontSee('<code>armor</code>', false);
    }

    public function test_admin_can_create_category_with_generated_slug(): void
    {
        $parent = $this->makeCategory('Weapons', 'weapons');

        $this->actingAs($this->admin())->post('/admin/categories', [
            'name' => 'Foam Swords',
            'parent_id' => $parent->category_id,
        ])->assertRedirect('/admin/categories')->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Foam Swords',
            'slug' => 'foam-swords',
            'parent_id' => $parent->category_id,
        ]);
    }

    public function test_category_creation_validates_input_and_unique_slug(): void
    {
        $this->makeCategory('Armor', 'armor');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/categories', ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post('/admin/categories', ['name' => 'Other', 'slug' => 'armor'])->assertSessionHasErrors('slug');
        $this->actingAs($admin)->post('/admin/categories', ['name' => 'X', 'parent_id' => 9999])->assertSessionHasErrors('parent_id');
        $this->actingAs($admin)->post('/admin/categories', ['name' => str_repeat('a', 101)])->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_category(): void
    {
        $category = $this->makeCategory('Armour', 'armour');

        $this->actingAs($this->admin())->put("/admin/categories/{$category->category_id}", [
            'name' => 'Armor',
            'slug' => 'armor',
            'description' => 'Protective gear',
        ])->assertRedirect('/admin/categories')->assertSessionHas('success');

        $category->refresh();
        $this->assertSame('Armor', $category->name);
        $this->assertSame('armor', $category->slug);
        $this->assertSame('Protective gear', $category->description);
    }

    public function test_category_can_keep_its_own_slug_on_update(): void
    {
        $category = $this->makeCategory('Armor', 'armor');

        $this->actingAs($this->admin())->put("/admin/categories/{$category->category_id}", [
            'name' => 'Armor Renamed',
            'slug' => 'armor',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Armor Renamed', $category->refresh()->name);
    }

    public function test_category_cannot_be_its_own_parent_or_move_under_descendant(): void
    {
        $root = $this->makeCategory('Root', 'root');
        $child = $this->makeCategory('Child', 'child', $root->category_id);
        $grandchild = $this->makeCategory('Grandchild', 'grandchild', $child->category_id);
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/categories/{$root->category_id}", [
            'name' => 'Root', 'slug' => 'root', 'parent_id' => $root->category_id,
        ])->assertSessionHasErrors('parent_id');

        $this->actingAs($admin)->put("/admin/categories/{$root->category_id}", [
            'name' => 'Root', 'slug' => 'root', 'parent_id' => $grandchild->category_id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($root->refresh()->parent_id);
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $category = $this->makeCategory('Obsolete', 'obsolete');

        $this->actingAs($this->admin())->delete("/admin/categories/{$category->category_id}")
            ->assertRedirect('/admin/categories')->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['category_id' => $category->category_id]);
    }

    public function test_category_with_children_or_products_cannot_be_deleted(): void
    {
        $parent = $this->makeCategory('Parent', 'parent');
        $this->makeCategory('Child', 'child', $parent->category_id);
        $admin = $this->admin();

        $this->actingAs($admin)->delete("/admin/categories/{$parent->category_id}")
            ->assertSessionHas('warning');
        $this->assertDatabaseHas('categories', ['category_id' => $parent->category_id]);

        $withProduct = $this->makeCategory('Stocked', 'stocked');
        $owner = User::factory()->create();
        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $owner->user_id, 'store_name' => 'Shop', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ], 'vendor_id');
        DB::table('products')->insert([
            'category_id' => $withProduct->category_id, 'vendor_id' => $vendorId, 'name' => 'Item',
            'slug' => 'item', 'price' => 10, 'stock' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->delete("/admin/categories/{$withProduct->category_id}")
            ->assertSessionHas('warning');
        $this->assertDatabaseHas('categories', ['category_id' => $withProduct->category_id]);
    }
}
