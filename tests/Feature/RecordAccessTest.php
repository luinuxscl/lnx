<?php

use App\Enums\Role;
use App\Models\RecordAccess;
use App\Models\User;
use App\Support\RecordAccess\AccessControlled;
use App\Support\RecordAccess\GrantsRecordAccess;
use App\Support\RecordAccess\RecordAccessAbility;
use App\Support\RecordAccess\RecordAccessRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    Schema::create('record_access_test_patients', function ($table) {
        $table->id();
        $table->foreignId('owner_id')->nullable();
        $table->string('name')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('record_access_test_patients');
});

it('accepts an access-controlled model', function () {
    $registry = new RecordAccessRegistry;

    $registry->register('luz', [RecordAccessTestPatient::class]);

    expect($registry->all())->toBe([RecordAccessTestPatient::class]);
});

it('rejects a model that does not implement the contract', function () {
    (new RecordAccessRegistry)->register('luz', [User::class]);
})->throws(InvalidArgumentException::class);

it('rejects a class that is not an eloquent model', function () {
    (new RecordAccessRegistry)->register('luz', [stdClass::class]);
})->throws(InvalidArgumentException::class);

it('filters records by the access scope without bypassing for admin', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    $mine = makeRecordAccessTestPatient(['owner_id' => $owner->id, 'name' => 'Mine']);
    makeRecordAccessTestPatient(['owner_id' => $other->id, 'name' => 'Theirs']);

    expect(RecordAccessTestPatient::accessibleTo($owner)->pluck('id')->all())->toBe([$mine->id])
        ->and(RecordAccessTestPatient::accessibleTo($admin)->pluck('id')->all())->toBe([]);
});

it('lets root see every record', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    makeRecordAccessTestPatient(['name' => 'A']);
    makeRecordAccessTestPatient(['name' => 'B']);

    expect(RecordAccessTestPatient::accessibleTo($root)->count())->toBe(2);
});

it('grants access through the polymorphic relation', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $patient = makeRecordAccessTestPatient(['owner_id' => $owner->id, 'name' => 'Shared']);

    $patient->grantAccessTo($viewer);

    expect($patient->hasRecordAccess($viewer))->toBeTrue()
        ->and(RecordAccessTestPatient::accessibleTo($viewer)->pluck('id')->all())->toBe([$patient->id]);
});

it('ignores an expired grant', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $patient = makeRecordAccessTestPatient(['owner_id' => $owner->id]);

    $patient->grantAccessTo($viewer, expiresAt: now()->subDay());

    expect($patient->hasRecordAccess($viewer))->toBeFalse()
        ->and(RecordAccessTestPatient::accessibleTo($viewer)->count())->toBe(0);
});

it('honours the ability carried by the grant', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $patient = makeRecordAccessTestPatient(['owner_id' => $owner->id]);

    $patient->grantAccessTo($viewer, RecordAccessAbility::View);

    expect($patient->hasRecordAccess($viewer, RecordAccessAbility::View))->toBeTrue()
        ->and($patient->hasRecordAccess($viewer, RecordAccessAbility::Manage))->toBeFalse();
});

it('merges abilities when granting twice', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $patient = makeRecordAccessTestPatient(['owner_id' => $owner->id]);

    $patient->grantAccessTo($viewer, RecordAccessAbility::View);
    $patient->grantAccessTo($viewer, RecordAccessAbility::Manage);

    expect(RecordAccess::count())->toBe(1)
        ->and($patient->hasRecordAccess($viewer, RecordAccessAbility::Manage))->toBeTrue();
});

function makeRecordAccessTestPatient(array $attributes = []): RecordAccessTestPatient
{
    return RecordAccessTestPatient::create($attributes);
}

class RecordAccessTestPatient extends Model implements AccessControlled
{
    use GrantsRecordAccess;

    protected $table = 'record_access_test_patients';

    protected $guarded = [];

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(Role::Root)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('owner_id', $user->id)
                ->orWhereHas('accessGrants', function ($grant) use ($user): void {
                    $grant->where('user_id', $user->id)
                        ->where(fn ($expiry) => $expiry
                            ->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now()));
                });
        });
    }
}
