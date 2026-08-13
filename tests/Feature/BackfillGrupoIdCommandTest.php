<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillGrupoIdCommandTest extends TestCase
{
    use RefreshDatabase;

    private function criarComData(User $user, string $produto, Carbon $createdAt, ?string $grupoId = null): PurchaseRequest
    {
        $registro = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'product_name' => $produto,
            'grupo_id' => $grupoId,
        ]);
        $registro->forceFill(['created_at' => $createdAt])->save();
        return $registro->fresh();
    }

    public function test_groups_records_from_same_user_within_window(): void
    {
        $user = User::factory()->create();
        $base = Carbon::parse('2026-01-01 10:00:00');

        $a = $this->criarComData($user, 'Produto A', $base);
        $b = $this->criarComData($user, 'Produto B', $base->copy()->addSeconds(90));

        $this->artisan('requisicoes:backfill-grupo-id')->assertSuccessful();

        $a->refresh();
        $b->refresh();
        $this->assertNotNull($a->grupo_id);
        $this->assertSame($a->grupo_id, $b->grupo_id);
    }

    public function test_does_not_group_records_from_same_user_beyond_window(): void
    {
        $user = User::factory()->create();
        $base = Carbon::parse('2026-01-01 10:00:00');

        $a = $this->criarComData($user, 'Produto A', $base);
        $b = $this->criarComData($user, 'Produto B', $base->copy()->addMinutes(5));

        $this->artisan('requisicoes:backfill-grupo-id')->assertSuccessful();

        $a->refresh();
        $b->refresh();
        $this->assertNotSame($a->grupo_id, $b->grupo_id);
    }

    public function test_never_groups_records_from_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $base = Carbon::parse('2026-01-01 10:00:00');

        $a = $this->criarComData($user1, 'Produto A', $base);
        $b = $this->criarComData($user2, 'Produto B', $base->copy()->addSeconds(5));

        $this->artisan('requisicoes:backfill-grupo-id')->assertSuccessful();

        $a->refresh();
        $b->refresh();
        $this->assertNotSame($a->grupo_id, $b->grupo_id);
    }

    public function test_chains_consecutive_records_within_window_even_if_total_span_exceeds_it(): void
    {
        $user = User::factory()->create();
        $base = Carbon::parse('2026-01-01 10:00:00');

        $a = $this->criarComData($user, 'Produto A', $base);
        $b = $this->criarComData($user, 'Produto B', $base->copy()->addSeconds(90));
        $c = $this->criarComData($user, 'Produto C', $base->copy()->addSeconds(170));

        $this->artisan('requisicoes:backfill-grupo-id')->assertSuccessful();

        $a->refresh();
        $b->refresh();
        $c->refresh();
        $this->assertSame($a->grupo_id, $b->grupo_id);
        $this->assertSame($b->grupo_id, $c->grupo_id);
    }

    public function test_does_not_overwrite_existing_grupo_id(): void
    {
        $user = User::factory()->create();
        $base = Carbon::parse('2026-01-01 10:00:00');

        $a = $this->criarComData($user, 'Produto A', $base, 'grupo-ja-existente');

        $this->artisan('requisicoes:backfill-grupo-id')->assertSuccessful();

        $a->refresh();
        $this->assertSame('grupo-ja-existente', $a->grupo_id);
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $user = User::factory()->create();
        $base = Carbon::parse('2026-01-01 10:00:00');

        $a = $this->criarComData($user, 'Produto A', $base);

        $this->artisan('requisicoes:backfill-grupo-id', ['--dry-run' => true])->assertSuccessful();

        $a->refresh();
        $this->assertNull($a->grupo_id);
    }
}
