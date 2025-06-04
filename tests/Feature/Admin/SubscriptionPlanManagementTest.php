<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class SubscriptionPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);
    }

    // --- AUTHENTICATION & AUTHORIZATION TESTS ---

    public function test_guest_cannot_access_admin_subscription_plans_index(): void
    {
        $response = $this->get(route('admin.subscription-plans.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_admin_subscription_plans_create_form(): void
    {
        $response = $this->get(route('admin.subscription-plans.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_create_admin_subscription_plan(): void
    {
        $response = $this->post(route('admin.subscription-plans.store'), []);
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_subscription_plans_index(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.subscription-plans.index'));
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_admin_subscription_plans_create_form(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.subscription-plans.create'));
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_submit_create_admin_subscription_plan(): void
    {
        $response = $this->actingAs($this->regularUser)->post(route('admin.subscription-plans.store'), []);
        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_subscription_plans_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.subscription-plans.index'));
        $response->assertStatus(200); // If middleware issue persists, this will fail
        $response->assertViewIs('admin.subscription-plans.index');
    }

    public function test_admin_user_can_access_admin_subscription_plans_create_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.subscription-plans.create'));
        $response->assertStatus(200); // If middleware issue persists, this will fail
        $response->assertViewIs('admin.subscription-plans.create');
    }

    // --- CREATE OPERATION TESTS ---

    public function test_admin_can_create_subscription_plan_with_valid_data(): void
    {
        $planData = [
            'name' => ['en' => 'Gold Plan', 'de' => 'Gold Plan DE', 'ar' => 'الخطة الذهبية'],
            'price' => 19.99,
            'paypal_plan_id' => 'P-GOLD123',
            'features' => ['en' => 'Feature 1, Feature 2', 'de' => 'Merkmal 1, Merkmal 2', 'ar' => 'الميزة 1 ، الميزة 2'],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.subscription-plans.store'), $planData);
        // If middleware issue, this might be a 500 or other error before redirect
        $response->assertRedirect(route('admin.subscription-plans.index'));
        $response->assertSessionHas('success', 'Subscription plan created successfully.');

        $this->assertDatabaseHas('subscription_plans', [
            'price' => 19.99,
            'paypal_plan_id' => 'P-GOLD123',
        ]);
        $createdPlan = SubscriptionPlan::first();
        $this->assertNotNull($createdPlan);
        $this->assertEquals('Gold Plan', $createdPlan->getTranslation('name', 'en'));
        $this->assertEquals('Gold Plan DE', $createdPlan->getTranslation('name', 'de'));
        $this->assertEquals('الخطة الذهبية', $createdPlan->getTranslation('name', 'ar'));
        $this->assertEquals('Feature 1, Feature 2', $createdPlan->getTranslation('features', 'en'));
    }

    public function test_admin_create_subscription_plan_fails_with_missing_name_locale(): void
    {
        $planData = [
            'name' => ['en' => 'Missing DE Name Plan'], // DE and AR names are missing
            'price' => 9.99,
            'paypal_plan_id' => 'P-MISSINGDE123',
        ];
        // StoreSubscriptionPlanRequest requires name.en, name.de, name.ar
        $response = $this->actingAs($this->adminUser)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertSessionHasErrors(['name.de', 'name.ar']);
        $this->assertDatabaseCount('subscription_plans', 0);
    }

    public function test_admin_create_subscription_plan_fails_with_non_unique_paypal_id(): void
    {
        SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-EXISTINGID']);
        $planData = [
            'name' => ['en' => 'Test Plan EN', 'de' => 'Test Plan DE', 'ar' => 'Test Plan AR'],
            'price' => 29.99,
            'paypal_plan_id' => 'P-EXISTINGID', // Duplicate
        ];
        $response = $this->actingAs($this->adminUser)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertSessionHasErrors(['paypal_plan_id']);
        $this->assertDatabaseCount('subscription_plans', 1); // Only the first one
    }

   // --- READ (INDEX & SHOW/EDIT) ---

   public function test_admin_can_view_list_of_subscription_plans(): void
   {
       SubscriptionPlan::factory()->create(['name' => ['en' => 'Bronze Plan']]);
       SubscriptionPlan::factory()->create(['name' => ['en' => 'Silver Plan']]);

       $response = $this->actingAs($this->adminUser)->get(route('admin.subscription-plans.index'));
       $response->assertStatus(200);
       $response->assertViewIs('admin.subscription-plans.index');
       $response->assertSeeText('Bronze Plan');
       $response->assertSeeText('Silver Plan');
   }

   public function test_admin_subscription_plans_index_pagination_works(): void
   {
        SubscriptionPlan::factory()->count(15)->create();

        $response = $this->actingAs($this->adminUser)->get(route('admin.subscription-plans.index'));
        $response->assertStatus(200);
        $response->assertViewHas('subscription_plans', function ($plans) {
            return $plans->count() === 10;
        });
        $response->assertSee(route('admin.subscription-plans.index', ['page' => 2]));
   }


   public function test_admin_can_view_edit_form_for_subscription_plan(): void
   {
       $plan = SubscriptionPlan::factory()->create(['name' => ['en' => 'Editable Gold Plan']]);

       $response = $this->actingAs($this->adminUser)->get(route('admin.subscription-plans.edit', $plan));
       $response->assertStatus(200);
       $response->assertViewIs('admin.subscription-plans.edit');
       $response->assertViewHas('subscription_plan', $plan);
       $response->assertSee('Editable Gold Plan');
   }

    public function test_admin_viewing_non_existent_plan_edit_form_returns_404(): void
    {
        $nonExistentPlanId = 99999;
        $response = $this->actingAs($this->adminUser)->get(route('admin.subscription-plans.edit', $nonExistentPlanId));
        $response->assertStatus(404);
    }

   // --- UPDATE ---
   public function test_admin_can_update_subscription_plan_with_valid_data(): void
   {
       $plan = SubscriptionPlan::factory()->create([
           'name' => ['en' => 'Old Name EN', 'de' => 'Old Name DE', 'ar' => 'Old Name AR'],
           'price' => 10.00,
           'paypal_plan_id' => 'OLD-PAYPAL-ID',
           'features' => ['en' => 'Old Feature EN'],
       ]);

       $updatedData = [
           'name' => ['en' => 'New Name EN', 'de' => 'New Name DE', 'ar' => 'New Name AR'],
           'price' => 25.99,
           'paypal_plan_id' => 'NEW-PAYPAL-ID',
           'features' => ['en' => 'New Feature EN', 'de' => 'New Feature DE', 'ar' => 'New Feature AR'],
       ];

       $response = $this->actingAs($this->adminUser)->put(route('admin.subscription-plans.update', $plan), $updatedData);

       $response->assertRedirect(route('admin.subscription-plans.index'));
       $response->assertSessionHas('success', 'Subscription plan updated successfully.');

       $plan->refresh();
       $this->assertEquals('New Name EN', $plan->getTranslation('name', 'en'));
       $this->assertEquals('New Name DE', $plan->getTranslation('name', 'de'));
       $this->assertEquals(25.99, $plan->price);
       $this->assertEquals('NEW-PAYPAL-ID', $plan->paypal_plan_id);
       $this->assertEquals('New Feature EN', $plan->getTranslation('features', 'en'));
       $this->assertEquals('New Feature AR', $plan->getTranslation('features', 'ar'));
   }

   public function test_admin_update_subscription_plan_fails_with_missing_name_locale(): void
   {
       $plan = SubscriptionPlan::factory()->create();
       $originalPrice = $plan->price;
       $updatedData = [
           'name' => ['en' => 'Updated EN Name'],
           'price' => 12.34,
           'paypal_plan_id' => 'P-UPDATEFAIL123',
       ];
       $response = $this->actingAs($this->adminUser)->put(route('admin.subscription-plans.update', $plan), $updatedData);

       $response->assertSessionHasErrors(['name.de', 'name.ar']);
       $plan->refresh();
       $this->assertEquals($originalPrice, $plan->price);
   }

   public function test_admin_update_subscription_plan_fails_with_non_unique_paypal_id_for_another_plan(): void
   {
       $existingPlan = SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-ALREADYEXISTS']);
       $planToUpdate = SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-TOBEUPDATED']);

       $updatedData = [
           'name' => ['en' => 'Name EN', 'de' => 'Name DE', 'ar' => 'Name AR'],
           'price' => $planToUpdate->price,
           'paypal_plan_id' => 'P-ALREADYEXISTS',
       ];

       $response = $this->actingAs($this->adminUser)->put(route('admin.subscription-plans.update', $planToUpdate), $updatedData);

       $response->assertSessionHasErrors(['paypal_plan_id']);
       $this->assertEquals('P-TOBEUPDATED', $planToUpdate->fresh()->paypal_plan_id);
   }

   public function test_admin_can_update_subscription_plan_with_same_paypal_id(): void
   {
       $plan = SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-SAMEID123']);
       $updatedData = [
           'name' => ['en' => 'Updated Name', 'de' => 'Updated DE', 'ar' => 'Updated AR'],
           'price' => $plan->price + 5,
           'paypal_plan_id' => 'P-SAMEID123',
       ];

       $response = $this->actingAs($this->adminUser)->put(route('admin.subscription-plans.update', $plan), $updatedData);
       $response->assertRedirect(route('admin.subscription-plans.index'));
       $response->assertSessionDoesntHaveErrors(['paypal_plan_id']);
       $this->assertEquals(number_format($plan->price + 5, 2), number_format($plan->fresh()->price, 2));
   }

   public function test_admin_can_update_subscription_plan_to_clear_all_features(): void
   {
       $plan = SubscriptionPlan::factory()->create([
           'name' => ['en' => 'Plan With Features'],
           'price' => 10.00,
           'paypal_plan_id' => 'P-WITHFEAT',
           'features' => ['en' => 'Feature A', 'de' => 'Merkmal A'],
       ]);

       $this->assertEquals('Feature A', $plan->getTranslation('features', 'en'));
       $this->assertEquals('Merkmal A', $plan->getTranslation('features', 'de'));

       $updatedData = [
           'name' => ['en' => 'Plan With Features', 'de' => 'Plan Mit Features DE', 'ar' => 'Plan With Features AR'],
           'price' => 12.00,
           'paypal_plan_id' => 'P-WITHFEAT',
           // 'features' key is intentionally omitted to test forgetAllTranslations
       ];


       $response = $this->actingAs($this->adminUser)->put(route('admin.subscription-plans.update', $plan), $updatedData);

       $response->assertRedirect(route('admin.subscription-plans.index'));
       $response->assertSessionHas('success');

       $plan->refresh();
       // If 'features' key is omitted, UpdateSubscriptionPlanRequest might still provide it as an empty array
       // if rules like 'features.*' are present. If so, isset($validatedData['features']) is true.
       // Then, $validatedData['features']['en'] ?? '' would result in empty strings being set.
       $expectedFeatures = array_filter($plan->getTranslations('features'), fn($value) => !is_null($value) && $value !== '');
       $this->assertEquals([], $expectedFeatures, "Features should be empty or contain only empty/null strings.");
       // A more direct check if all values become empty strings:
       // $this->assertEquals(['en' => '', 'de' => '', 'ar' => ''], $plan->getTranslations('features'));
       // However, the controller's `else { $subscriptionPlan->forgetAllTranslations('features'); }` should lead to an empty array.
       // The current failure suggests the `else` is not hit.
       // This means isset($validatedData['features']) is true.
       // So, the features are being set to their default (empty string from `?? ''`).
       // The `forgetAllTranslations` is intended to make `getTranslations` return [].
       // If the `else` branch is hit, the original assertion `$this->assertEquals([], $plan->getTranslations('features'));` is correct.
       // The failure means the `if (isset($validatedData['features']))` is true.
       // This implies $validatedData['features'] exists, even if it's an empty array from the request,
       // or if the FormRequest adds it because of rules like 'features.en'.
       // If $validatedData['features'] is [], then setTranslation('en', $validatedData['features']['en'] ?? '') becomes setTranslation('en', '').
       // So we'd expect ['en'=>'', 'de'=>'', 'ar'=>''] if $validatedData['features'] was present but empty.
       // The test is specifically omitting the 'features' key from $updatedData.
       // So, UpdateSubscriptionPlanRequest should not include 'features' in $validatedData unless 'features' is a fillable field with a default (not the case here).
       // This suggests the controller's `else` branch for `forgetAllTranslations` should be hit.
       // The failure of `$this->assertEquals([], $plan->getTranslations('features'));` means it's not.
       // The debug log will clarify what $validatedData actually contains.
       // For now, I will keep the original assertion as it reflects the intended logic of the controller.
       // The previous verbose comment block was removed for clarity, the core assertion remains.
       $this->assertEquals([], $plan->getTranslations('features'));
   }

   public function test_admin_can_update_subscription_plan_by_sending_empty_feature_strings(): void
   {
       $plan = SubscriptionPlan::factory()->create([
           'name' => ['en' => 'Plan With Features To Empty'],
           'price' => 10.00,
           'features' => ['en' => 'Feature B', 'de' => 'Merkmal B'],
       ]);

       $updatedData = [
           'name' => ['en' => 'Plan With Features To Empty', 'de' => 'DE Name', 'ar' => 'AR Name'],
           'price' => 12.00,
           'paypal_plan_id' => $plan->paypal_plan_id, // Keep same paypal_plan_id
           'features' => ['en' => '', 'de' => '', 'ar' => ''], // Explicitly empty for all locales
       ];

       $response = $this->actingAs($this->adminUser)->put(route('admin.subscription-plans.update', $plan), $updatedData);
       $response->assertRedirect(route('admin.subscription-plans.index'));
       $response->assertSessionHas('success');

       $plan->refresh();
       $this->assertEquals([], $plan->getTranslations('features'), "Features should be an empty array after update with empty strings.");
   }


   // --- DELETE ---
   public function test_admin_can_soft_delete_subscription_plan(): void
   {
       $plan = SubscriptionPlan::factory()->create();
       $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id, 'deleted_at' => null]);

       $response = $this->actingAs($this->adminUser)->delete(route('admin.subscription-plans.destroy', $plan));

       $response->assertRedirect(route('admin.subscription-plans.index'));
       $response->assertSessionHas('success', 'Subscription plan deleted successfully.');

       $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
   }

   public function test_soft_deleted_plan_not_visible_on_user_facing_plan_list(): void
   {
        $planData = [
            'name' => ['en' => 'User Visible Plan'],
            'price' => 10.00,
            'paypal_plan_id' => 'P-VISIBLEUSER',
        ];
        $plan = SubscriptionPlan::factory()->create($planData);

        $this->actingAs($this->adminUser)->delete(route('admin.subscription-plans.destroy', $plan));
        $this->assertSoftDeleted($plan);

        $regularUser = User::factory()->create();
        $response = $this->actingAs($regularUser)->get(route('subscriptions.index'));

        $response->assertStatus(200);
        $response->assertDontSeeText('User Visible Plan');
        $response->assertViewHas('plans', function($plans) use ($planData) {
            foreach ($plans as $p) {
                if ($p->getTranslation('name', 'en') === $planData['name']['en']) return false;
            }
            return true;
        });
   }

   public function test_admin_deleting_non_existent_plan_returns_404(): void
   {
       $nonExistentPlanId = 99999;
       $response = $this->actingAs($this->adminUser)->delete(route('admin.subscription-plans.destroy', $nonExistentPlanId));
       $response->assertStatus(404);
   }
}
