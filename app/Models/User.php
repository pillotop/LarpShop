<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $primaryKey = 'user_id';

    public function vendor(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Vendor::class, 'user_id', 'user_id');
    }

    public function isVendor(): bool
    {
        return $this->vendor()->where('status', 'approved')->exists() || $this->role === 'vendor';
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
