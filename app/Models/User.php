<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        // Como o usuário é só "desativado" (SoftDeletes), o cascade do banco não limpa os avisos push dele.
        static::deleting(fn (User $user) => $user->pushSubscriptions()->delete());
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
    ];

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * O super admin e' quem cadastra/remove outros usuarios do sistema.
     * E' um unico e-mail fixo (config/admin.php), nao um papel no banco.
     */
    public function isSuperAdmin(): bool
    {
        $email = config('admin.super_admin_email');

        return $email !== null && strcasecmp($this->email, $email) === 0;
    }

    /** Quem é da Entrada também tem o perfil de Conferência (confere e dá entrada). */
    public function isConferente(): bool
    {
        return in_array($this->role, ['conferente', 'entrada'], true) || $this->isAdmin();
    }

    public function isEntrada(): bool
    {
        return $this->role === 'entrada' || $this->isAdmin();
    }

    /** Setor Financeiro: só enxerga a aba Financeiro (contas a pagar por fornecedor). */
    public function isFinanceiro(): bool
    {
        return $this->role === 'financeiro';
    }

    /** Setor RMA: só leitura, vê o que foi comprado e já chegou (produto, fornecedor, data, quantidade, fotos). */
    public function isRma(): bool
    {
        return $this->role === 'rma';
    }

    /** Quem entra na aba Financeiro: o setor e o super admin (que cria e acompanha os usuários). */
    public function podeVerFinanceiro(): bool
    {
        return $this->isFinanceiro() || $this->isSuperAdmin();
    }

    public function isVendedor(): bool
    {
        return !$this->isAdmin() && $this->role === null;
    }

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Relacionamento: um usuário tem várias requisições
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }
}