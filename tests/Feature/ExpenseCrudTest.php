<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // For Str::random if used in descriptions
use Tests\TestCase;

class ExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // Ensure storage/app/public/receipts directory exists for file upload tests later
        Storage::fake('public'); // Fake the public disk
        // The directory is created on-demand by Storage::putFileAs or storeAs
        // No need to makeDirectory here when using Storage::fake with 'public' disk
        // as it simulates the public disk in memory or a temporary location.
        // If not faking and using actual storage, makeDirectory would be relevant.
    }

    // --- CREATE ---

    public function test_unauthenticated_user_cannot_view_create_expense_form(): void
    {
        $response = $this->get(route('expenses.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_cannot_create_expense(): void
    {
        $expenseData = [
            'description' => ['en' => 'Test lunch'],
            'amount' => 12.99,
            'expense_date' => now()->format('Y-m-d'),
            'category' => 'Food',
            'is_business_expense' => false,
        ];
        $response = $this->post(route('expenses.store'), $expenseData);
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_create_expense_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('expenses.create'));
        $response->assertStatus(200);
        $response->assertViewIs('expenses.create');
    }

    public function test_authenticated_user_can_create_valid_expense_without_receipt(): void
    {
        $descriptionEn = 'Lunch meeting with client';
        $descriptionDe = 'Mittagessen mit Kunde';
        $expenseData = [
            'description' => [
                'en' => $descriptionEn,
                'de' => $descriptionDe,
                // 'ar' can be omitted if not required by StoreExpenseRequest for all locales
            ],
            'amount' => 75.50,
            'expense_date' => now()->format('Y-m-d'),
            'category' => 'Business Meals',
            'is_business_expense' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('expenses.store'), $expenseData);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success', __('Expense recorded successfully.'));

        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'amount' => 75.50,
            'expense_date' => now()->format('Y-m-d 00:00:00'), // Match DB storage for datetime with date only
            'category' => 'Business Meals',
            'is_business_expense' => 1, // Match DB storage for boolean true
            // 'receipt_path' => null, // Default for no upload
        ]);

        $createdExpense = Expense::first(); // Get the created expense
        $this->assertNotNull($createdExpense);
        $this->assertEquals($descriptionEn, $createdExpense->getTranslation('description', 'en'));
        $this->assertEquals($descriptionDe, $createdExpense->getTranslation('description', 'de'));
        $this->assertNull($createdExpense->receipt_path);
    }

    // More tests for validation and receipt upload will be added later.

    public function test_create_expense_fails_with_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user)
                         ->post(route('expenses.store'), []); // Empty data

        $response->assertStatus(302); // Redirect back
        $response->assertSessionHasErrors(['description.en', 'description.de', 'description.ar', 'amount', 'expense_date']);
        // Specific locale keys are expected due to 'required_without_all' rules

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_create_expense_fails_with_invalid_amount(): void
    {
        $expenseData = [
            'description' => ['en' => 'Invalid amount test'],
            'amount' => 'not-a-number', // Invalid amount
            'expense_date' => now()->format('Y-m-d'),
        ];
        $response = $this->actingAs($this->user)->post(route('expenses.store'), $expenseData);
        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_create_expense_fails_with_future_expense_date(): void
    {
        $expenseData = [
            'description' => ['en' => 'Future date test'],
            'amount' => 10.00,
            'expense_date' => now()->addDay()->format('Y-m-d'), // Future date
        ];
        $response = $this->actingAs($this->user)->post(route('expenses.store'), $expenseData);
        $response->assertSessionHasErrors(['expense_date']); // Rule is 'before_or_equal:today'
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_authenticated_user_can_create_valid_expense_with_receipt(): void
    {
        Storage::fake('public'); // Ensure 'public' disk is faked for this test too

        $descriptionEn = 'Expense with receipt';
        $file = UploadedFile::fake()->image('receipt.jpg');

        $expenseData = [
            'description' => ['en' => $descriptionEn],
            'amount' => 120.00,
            'expense_date' => now()->format('Y-m-d'),
            'category' => 'Travel',
            'is_business_expense' => true,
            'receipt' => $file,
        ];

        $response = $this->actingAs($this->user)->post(route('expenses.store'), $expenseData);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'amount' => 120.00,
            'expense_date' => now()->format('Y-m-d 00:00:00'),
            'category' => 'Travel',
            'is_business_expense' => 1,
        ]);

        $createdExpense = Expense::where('user_id', $this->user->id)->orderBy('id', 'desc')->first();
        $this->assertNotNull($createdExpense);
        $this->assertNotNull($createdExpense->receipt_path);
        Storage::disk('public')->assertExists($createdExpense->receipt_path);
        $this->assertTrue(Str::startsWith($createdExpense->receipt_path, 'receipts/' . $this->user->id . '/'));
    }

    public function test_create_expense_fails_with_invalid_receipt_file_type(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain'); // Invalid mime type

        $expenseData = [
            'description' => ['en' => 'Invalid receipt type'],
            'amount' => 50.00,
            'expense_date' => now()->format('Y-m-d'),
            'receipt' => $file,
        ];

        $response = $this->actingAs($this->user)->post(route('expenses.store'), $expenseData);
        $response->assertSessionHasErrors(['receipt']);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_create_expense_fails_with_receipt_file_too_large(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('large_receipt.jpg', 3000, 'image/jpeg'); // 3MB, too large

        $expenseData = [
            'description' => ['en' => 'Large receipt'],
            'amount' => 70.00,
            'expense_date' => now()->format('Y-m-d'),
            'receipt' => $file,
        ];

        $response = $this->actingAs($this->user)->post(route('expenses.store'), $expenseData);
        $response->assertSessionHasErrors(['receipt']);
        $this->assertDatabaseCount('expenses', 0);
    }

   // --- READ (INDEX) ---

   public function test_unauthenticated_user_cannot_view_expense_index(): void
   {
       $response = $this->get(route('expenses.index'));
       $response->assertRedirect(route('login'));
   }

   public function test_authenticated_user_can_view_expense_index_with_their_expenses(): void
   {
       $user1 = $this->user; // User from setUp
       $expense1User1 = Expense::factory()->for($user1)->create(['description' => ['en' => 'User1 Expense 1'], 'expense_date' => now()->subDay()]);
       $expense2User1 = Expense::factory()->for($user1)->create(['description' => ['en' => 'User1 Expense 2'], 'expense_date' => now()]);

       $otherUser = User::factory()->create();
       Expense::factory()->for($otherUser)->create(['description' => ['en' => 'Other User Expense']]);

       $response = $this->actingAs($user1)->get(route('expenses.index'));

       $response->assertStatus(200);
       $response->assertViewIs('expenses.index');
       $response->assertSeeText('User1 Expense 2'); // Newer expense, should be first
       $response->assertSeeText('User1 Expense 1');
       $response->assertDontSeeText('Other User Expense');

       // Check order
       $response->assertSeeInOrder([
           'User1 Expense 2', // Most recent
           'User1 Expense 1'  // Older
       ]);
   }

   public function test_expense_index_shows_message_if_no_expenses(): void
   {
       $response = $this->actingAs($this->user)->get(route('expenses.index'));
       $response->assertStatus(200);
       $response->assertSeeText(__("You haven't recorded any expenses yet.")); // Updated text
   }

   public function test_expense_index_pagination_works(): void
   {
        // Create more expenses than items per page (controller uses paginate(10))
        Expense::factory()->count(15)->for($this->user)->create(['expense_date' => now()]);

        $response = $this->actingAs($this->user)->get(route('expenses.index'));
        $response->assertStatus(200);
        $response->assertViewHas('expenses', function ($expenses) {
            return $expenses->count() === 10; // Check if 10 items are on the first page
        });
        $response->assertSee(route('expenses.index', ['page' => 2])); // Check for link to page 2
   }

   // --- READ (SHOW/EDIT as per controller logic) ---

   public function test_unauthenticated_user_cannot_view_single_expense(): void
   {
       $expense = Expense::factory()->for($this->user)->create();
       $response = $this->get(route('expenses.show', $expense));
       $response->assertRedirect(route('login'));
   }

   public function test_authenticated_user_can_view_their_own_expense_via_show_route_loads_edit_view(): void
   {
       $expense = Expense::factory()->for($this->user)->create(['description' => ['en' => 'My viewable Expense']]);
       $response = $this->actingAs($this->user)->get(route('expenses.show', $expense));

       $response->assertStatus(200);
       $response->assertViewIs('expenses.edit'); // Controller's show method loads edit view
       $response->assertViewHas('expense', $expense);
       $response->assertSee('My viewable Expense'); // Check if description is present in the form
   }

   public function test_authenticated_user_cannot_view_others_expense_via_show_route(): void
   {
       $otherUser = User::factory()->create();
       $othersExpense = Expense::factory()->for($otherUser)->create();

       $response = $this->actingAs($this->user)->get(route('expenses.show', $othersExpense));
       $response->assertStatus(403); // Forbidden
   }

   public function test_viewing_non_existent_expense_via_show_route_returns_404(): void
   {
       $nonExistentExpenseId = 99999;
       $response = $this->actingAs($this->user)->get(route('expenses.show', $nonExistentExpenseId));
       $response->assertStatus(404);
   }

   // --- UPDATE ---

   public function test_unauthenticated_user_cannot_view_edit_expense_form(): void
   {
       $expense = Expense::factory()->for($this->user)->create(); // Create an expense for context
       $response = $this->get(route('expenses.edit', $expense));
       $response->assertRedirect(route('login'));
   }

   public function test_unauthenticated_user_cannot_update_expense(): void
   {
       $expense = Expense::factory()->for($this->user)->create();
       $updatedData = ['description' => ['en' => 'Updated by unauth'], 'amount' => 10.00, 'expense_date' => now()->format('Y-m-d')];
       $response = $this->put(route('expenses.update', $expense), $updatedData);
       $response->assertRedirect(route('login'));
   }

   public function test_authenticated_user_can_view_edit_form_for_their_own_expense(): void
   {
       $expense = Expense::factory()->for($this->user)->create(['description' => ['en' => 'My Editable Expense']]);
       $response = $this->actingAs($this->user)->get(route('expenses.edit', $expense));

       $response->assertStatus(200);
       $response->assertViewIs('expenses.edit');
       $response->assertViewHas('expense', $expense);
       $response->assertSee('My Editable Expense');
   }

   public function test_authenticated_user_cannot_view_edit_form_for_others_expense(): void
   {
       $otherUser = User::factory()->create();
       $othersExpense = Expense::factory()->for($otherUser)->create();

       $response = $this->actingAs($this->user)->get(route('expenses.edit', $othersExpense));
       $response->assertStatus(403);
   }

   public function test_authenticated_user_can_update_their_own_expense(): void
   {
       $expense = Expense::factory()->for($this->user)->create([
           'description' => ['en' => 'Original Description'],
           'amount' => 50.00,
           'expense_date' => now()->subDay()->format('Y-m-d'),
           'category' => 'Old Category',
           'is_business_expense' => false,
       ]);

       $updatedData = [
           'description' => ['en' => 'Updated Description', 'de' => 'Aktualisierte Beschreibung'],
           'amount' => 150.75,
           'expense_date' => now()->format('Y-m-d'),
           'category' => 'New Category',
           'is_business_expense' => true,
       ];

       $response = $this->actingAs($this->user)->put(route('expenses.update', $expense), $updatedData);

       $response->assertRedirect(route('expenses.index'));
       $response->assertSessionHas('success', __('Expense updated successfully.'));

       $expense->refresh();
       $this->assertEquals('Updated Description', $expense->getTranslation('description', 'en'));
       $this->assertEquals('Aktualisierte Beschreibung', $expense->getTranslation('description', 'de'));
       $this->assertEquals(150.75, $expense->amount);
       $this->assertEquals(now()->format('Y-m-d'), $expense->expense_date->format('Y-m-d'));
       $this->assertEquals('New Category', $expense->category);
       $this->assertTrue($expense->is_business_expense);
   }

   public function test_authenticated_user_can_update_expense_with_new_receipt(): void
   {
        Storage::fake('public');
        $user = $this->user;
        $oldReceipt = UploadedFile::fake()->image('old_receipt.jpg');
        $expense = Expense::factory()->for($user)->create([
            'receipt_path' => $oldReceipt->store('receipts/' . $user->id, 'public')
        ]);
        $oldReceiptPath = $expense->receipt_path;

        $newReceipt = UploadedFile::fake()->image('new_receipt.png');
        $updatedData = [
            'description' => ['en' => $expense->getTranslation('description', 'en')],
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'receipt' => $newReceipt,
        ];

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), $updatedData);
        $response->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertNotNull($expense->receipt_path);
        $this->assertNotEquals($oldReceiptPath, $expense->receipt_path);
        Storage::disk('public')->assertMissing($oldReceiptPath);
        Storage::disk('public')->assertExists($expense->receipt_path);
   }

   public function test_authenticated_user_can_remove_receipt_during_update(): void
   {
        Storage::fake('public');
        $user = $this->user;
        $receipt = UploadedFile::fake()->image('receipt_to_remove.jpg');
        $expense = Expense::factory()->for($user)->create([
            'receipt_path' => $receipt->store('receipts/' . $user->id, 'public')
        ]);
        $receiptPath = $expense->receipt_path;
        $this->assertNotNull($receiptPath);
        Storage::disk('public')->assertExists($receiptPath);

        $updatedData = [
            'description' => ['en' => $expense->getTranslation('description', 'en')],
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'remove_receipt' => '1',
        ];

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), $updatedData);
        $response->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertNull($expense->receipt_path);
        Storage::disk('public')->assertMissing($receiptPath);
   }


   public function test_update_expense_fails_with_invalid_data(): void
   {
       $expense = Expense::factory()->for($this->user)->create();
       $originalAmount = $expense->amount;

       $invalidData = [
           'description' => ['en' => 'Updated description'],
           'amount' => 'not-a-valid-amount', // Invalid
           'expense_date' => now()->addDays(5)->format('Y-m-d'), // Future date, also invalid
       ];

       $response = $this->actingAs($this->user)->put(route('expenses.update', $expense), $invalidData);

       $response->assertSessionHasErrors(['amount', 'expense_date']);
       $expense->refresh();
       $this->assertEquals($originalAmount, $expense->amount); // Check amount hasn't changed
   }

   public function test_authenticated_user_cannot_update_others_expense(): void
   {
       $otherUser = User::factory()->create();
       $othersExpense = Expense::factory()->for($otherUser)->create(['amount' => 50]);

       $updateData = ['description' => ['en' => 'Attempted Update'], 'amount' => 100, 'expense_date' => now()->format('Y-m-d')];

       $response = $this->actingAs($this->user)->put(route('expenses.update', $othersExpense), $updateData);
       $response->assertStatus(403);
       $this->assertEquals(50, $othersExpense->fresh()->amount); // Ensure data didn't change
   }

   // --- DELETE ---

   public function test_unauthenticated_user_cannot_delete_expense(): void
   {
       $expense = Expense::factory()->for($this->user)->create();
       $response = $this->delete(route('expenses.destroy', $expense));
       $response->assertRedirect(route('login'));
   }

   public function test_authenticated_user_can_delete_their_own_expense_without_receipt(): void
   {
       $expense = Expense::factory()->for($this->user)->create();
       $this->assertDatabaseCount('expenses', 1);

       $response = $this->actingAs($this->user)->delete(route('expenses.destroy', $expense));

       $response->assertRedirect(route('expenses.index'));
       $response->assertSessionHas('success', __('Expense deleted successfully.'));
       $this->assertDatabaseCount('expenses', 0); // Hard delete
   }

   public function test_authenticated_user_can_delete_their_own_expense_with_receipt(): void
   {
        Storage::fake('public');
        $user = $this->user;
        $receipt = UploadedFile::fake()->image('receipt_to_delete.jpg');
        $expense = Expense::factory()->for($user)->create([
            'receipt_path' => $receipt->store('receipts/' . $user->id, 'public')
        ]);
        $receiptPath = $expense->receipt_path;
        Storage::disk('public')->assertExists($receiptPath);
        $this->assertDatabaseCount('expenses', 1);

        $response = $this->actingAs($user)->delete(route('expenses.destroy', $expense));

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('expenses', 0);
        Storage::disk('public')->assertMissing($receiptPath);
   }

   public function test_authenticated_user_cannot_delete_others_expense(): void
   {
       $otherUser = User::factory()->create();
       $othersExpense = Expense::factory()->for($otherUser)->create();
       $this->assertDatabaseCount('expenses', 1); // Assuming only this expense exists for this test context

       $response = $this->actingAs($this->user)->delete(route('expenses.destroy', $othersExpense));

       $response->assertStatus(403);
       $this->assertDatabaseCount('expenses', 1); // Expense should still be there
   }

   public function test_deleting_non_existent_expense_returns_404(): void
   {
       $nonExistentExpenseId = 99999;
       $response = $this->actingAs($this->user)->delete(route('expenses.destroy', $nonExistentExpenseId));
       $response->assertStatus(404);
   }
}
