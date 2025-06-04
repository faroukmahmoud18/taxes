<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash; // Not strictly needed here, but good for consistency if creating users with specific passwords

class SidebarNavigationTest extends TestCase
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

    public function test_admin_user_sees_admin_and_user_links_on_admin_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Admin specific links
        $response->assertSee(route('admin.dashboard'));
        $response->assertSeeText(__('Admin Dashboard'));
        // $response->assertSee(route('admin.users.index')); // Manage Users - route not yet created
        // $response->assertSeeText(__('Manage Users'));
        $response->assertSee(route('admin.subscription-plans.index'));
        $response->assertSeeText(__('Subscription Plans'));
        $response->assertSee(route('admin.static-pages.index'));
        $response->assertSeeText(__('Static Pages'));
        $response->assertSee(route('admin.tax-configuration.index'));
        $response->assertSeeText(__('Tax Configuration'));

        // User specific links (also available to admin)
        $response->assertSee(route('dashboard'));
        // Ensure we differentiate if both are named "Dashboard" in the sidebar text
        // The sidebar design has "Dashboard" for user and "Admin Dashboard" for admin.
        // It also had a "View User Dashboard" link for admins.
        // Let's assume the "Dashboard" link in the user section is for route('dashboard').
        $response->assertSeeTextInOrder([__('Admin Dashboard'), __('Dashboard')]);


        $response->assertSee(route('expenses.index'));
        $response->assertSeeText(__('Expenses'));
        $response->assertSee(route('subscriptions.index'));
        $response->assertSeeText(__('Subscriptions'));
         $response->assertSee(route('tax-estimation.show'));
        $response->assertSeeText(__('Tax Estimation'));


        // Common links
        $response->assertSee(route('profile.edit'));
        $response->assertSeeText(__('Profile'));
        $response->assertSee(route('logout'));
        $response->assertSeeText(__('Log Out'));
    }

    public function test_regular_user_sees_user_links_and_no_admin_links_on_user_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('dashboard'));

        $response->assertStatus(200);

        // User specific links
        $response->assertSee(route('dashboard'));
        $response->assertSeeText(__('Dashboard'));
        $response->assertSee(route('expenses.index'));
        $response->assertSeeText(__('Expenses'));
        $response->assertSee(route('subscriptions.index'));
        $response->assertSeeText(__('Subscriptions'));
        $response->assertSee(route('tax-estimation.show'));
        $response->assertSeeText(__('Tax Estimation'));

        // Common links
        $response->assertSee(route('profile.edit'));
        $response->assertSeeText(__('Profile'));
        $response->assertSee(route('logout'));
        $response->assertSeeText(__('Log Out'));

        // Ensure Admin specific links are NOT present
        $response->assertDontSee(route('admin.dashboard'));
        $response->assertDontSeeText(__('Admin Dashboard'));
        $response->assertDontSeeText(__('Admin Menu')); // Check for the heading too
        $response->assertDontSee(route('admin.subscription-plans.index'));
        // $response->assertDontSee(route('admin.users.index'));
    }

    public function test_admin_user_sees_admin_links_when_viewing_user_dashboard(): void
    {
        // This test ensures that even if an admin visits the user dashboard,
        // their admin capabilities (i.e., seeing admin links in sidebar) persist.
        $response = $this->actingAs($this->adminUser)->get(route('dashboard')); // Admin visits user dashboard

        $response->assertStatus(200);

        // Check for a key admin link
        $response->assertSee(route('admin.dashboard'));
        $response->assertSeeText(__('Admin Dashboard'));
        $response->assertSeeText(__('Admin Menu'));

        // Check for a key user link
        $response->assertSee(route('expenses.index'));
        $response->assertSeeText(__('Expenses'));
    }
}
