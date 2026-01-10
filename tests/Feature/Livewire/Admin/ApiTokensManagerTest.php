<?php

use App\Livewire\Admin\ApiTokensManager;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get('/admin/api-tokens')
        ->assertRedirect();
});

it('requires admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/api-tokens')
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/api-tokens')
        ->assertSuccessful()
        ->assertSeeLivewire(ApiTokensManager::class);
});

it('displays existing tokens', function () {
    $admin = User::factory()->admin()->create();
    $admin->createToken('Test Token');

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->assertSee('Test Token');
});

it('shows who created each token', function () {
    $admin = User::factory()->admin()->create(['forenames' => 'Admin', 'surname' => 'User']);
    $admin->createToken('Admin Token');

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->assertSee('Admin Token')
        ->assertSee('A. User');
});

it('can create a token with a name', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->set('tokenName', 'My API Token')
        ->call('createToken')
        ->assertHasNoErrors();

    expect(PersonalAccessToken::where('name', 'My API Token')->exists())->toBeTrue();
});

it('shows the newly created token after creation', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->set('tokenName', 'New Token')
        ->call('createToken');

    expect($component->get('newlyCreatedToken'))->not->toBeNull();
    expect($component->get('newlyCreatedToken'))->toContain('|');
});

it('validates token name is required', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->set('tokenName', '')
        ->call('createToken')
        ->assertHasErrors(['tokenName' => 'required']);

    expect(PersonalAccessToken::count())->toBe(0);
});

it('can create a token with an expiry date', function () {
    $admin = User::factory()->admin()->create();
    $expiryDate = now()->addMonth()->format('Y-m-d');

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->set('tokenName', 'Expiring Token')
        ->set('expiresAt', $expiryDate)
        ->call('createToken')
        ->assertHasNoErrors();

    $token = PersonalAccessToken::where('name', 'Expiring Token')->first();
    expect($token)->not->toBeNull();
    expect($token->expires_at->format('Y-m-d'))->toBe($expiryDate);
});

it('validates expiry must be in the future', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->set('tokenName', 'Past Token')
        ->set('expiresAt', now()->subDay()->format('Y-m-d'))
        ->call('createToken')
        ->assertHasErrors(['expiresAt']);

    expect(PersonalAccessToken::count())->toBe(0);
});

it('can delete a token', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('Delete Me');
    $tokenId = $token->accessToken->id;

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->assertSee('Delete Me')
        ->call('confirmDelete', $tokenId)
        ->call('deleteToken')
        ->assertDontSee('Delete Me');

    expect(PersonalAccessToken::find($tokenId))->toBeNull();
});

it('can cancel delete', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('Keep Me');
    $tokenId = $token->accessToken->id;

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->call('confirmDelete', $tokenId)
        ->call('cancelDelete')
        ->assertSet('deletingTokenId', null);

    expect(PersonalAccessToken::find($tokenId))->not->toBeNull();
});

it('shows empty state when no tokens exist', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->assertSee('No API tokens')
        ->assertSee('Create a token to enable API access');
});

it('resets form when modal is closed', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->set('tokenName', 'Some Token')
        ->set('expiresAt', now()->addMonth()->format('Y-m-d'))
        ->call('resetCreateModal')
        ->assertSet('tokenName', '')
        ->assertSet('expiresAt', '')
        ->assertSet('newlyCreatedToken', null);
});

it('admins can see tokens created by other admins', function () {
    $admin1 = User::factory()->admin()->create(['forenames' => 'First', 'surname' => 'Admin']);
    $admin2 = User::factory()->admin()->create(['forenames' => 'Second', 'surname' => 'Admin']);

    $admin1->createToken('First Admin Token');

    Livewire::actingAs($admin2)
        ->test(ApiTokensManager::class)
        ->assertSee('First Admin Token')
        ->assertSee('F. Admin');
});

it('admins can delete tokens created by other admins', function () {
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();

    $token = $admin1->createToken('Other Admin Token');
    $tokenId = $token->accessToken->id;

    Livewire::actingAs($admin2)
        ->test(ApiTokensManager::class)
        ->call('confirmDelete', $tokenId)
        ->call('deleteToken');

    expect(PersonalAccessToken::find($tokenId))->toBeNull();
});

it('shows usage examples with tabs for curl, python, and powerbi', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ApiTokensManager::class)
        ->assertSee('Usage Examples')
        ->assertSee('cURL')
        ->assertSee('Python')
        ->assertSee('Power BI')
        ->assertSee('/api/users');
});
