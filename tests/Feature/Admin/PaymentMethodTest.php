<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_payment_method_management(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/payment-methods')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/payment-methods')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/payment-methods')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_payment_methods_list(): void
    {
        $admin = User::factory()->admin()->create();
        PaymentMethod::factory()->create(['name' => 'GoTyme']);

        $this->actingAs($admin)->get('/admin/payment-methods')->assertOk()->assertSee('GoTyme');
    }

    public function test_admin_can_add_a_payment_method_with_a_qr_code(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/payment-methods', [
            'name' => 'Maya',
            'qr_code' => UploadedFile::fake()->image('maya-qr.png'),
            'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.payment-methods.index'));
        $method = PaymentMethod::where('name', 'Maya')->firstOrFail();
        $this->assertTrue($method->is_active);
        Storage::disk('public')->assertExists($method->qr_code_path);
    }

    public function test_a_qr_code_is_required_to_add_a_payment_method(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/payment-methods', [
            'name' => 'Maya',
        ]);

        $response->assertSessionHasErrors('qr_code');
        $this->assertDatabaseMissing('payment_methods', ['name' => 'Maya']);
    }

    public function test_admin_can_update_a_payment_method_without_replacing_its_qr_code(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $method = PaymentMethod::factory()->create(['name' => 'GCash', 'qr_code_path' => 'payment-methods/original.png']);

        $response = $this->actingAs($admin)->put("/admin/payment-methods/{$method->id}", [
            'name' => 'GCash Personal',
        ]);

        $response->assertRedirect(route('admin.payment-methods.index'));
        $method->refresh();
        $this->assertSame('GCash Personal', $method->name);
        $this->assertSame('payment-methods/original.png', $method->qr_code_path);
    }

    public function test_replacing_a_qr_code_deletes_the_old_one(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $method = PaymentMethod::factory()->create();
        $oldPath = $method->qr_code_path;
        Storage::disk('public')->put($oldPath, 'fake-content');

        $this->actingAs($admin)->put("/admin/payment-methods/{$method->id}", [
            'name' => $method->name,
            'qr_code' => UploadedFile::fake()->image('new-qr.png'),
        ]);

        $method->refresh();
        $this->assertNotSame($oldPath, $method->qr_code_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($method->qr_code_path);
    }

    public function test_unchecking_active_deactivates_a_payment_method(): void
    {
        $admin = User::factory()->admin()->create();
        $method = PaymentMethod::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->put("/admin/payment-methods/{$method->id}", [
            'name' => $method->name,
            // is_active deliberately omitted - an unchecked checkbox isn't
            // sent at all, this is what "turn it off" looks like on the wire.
        ]);

        $this->assertFalse($method->fresh()->is_active);
    }

    public function test_admin_can_delete_a_payment_method(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $method = PaymentMethod::factory()->create();
        Storage::disk('public')->put($method->qr_code_path, 'fake-content');

        $this->actingAs($admin)->delete("/admin/payment-methods/{$method->id}")
            ->assertRedirect(route('admin.payment-methods.index'));

        $this->assertDatabaseMissing('payment_methods', ['id' => $method->id]);
        Storage::disk('public')->assertMissing($method->qr_code_path);
    }
}
