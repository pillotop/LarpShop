<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'password', 'phone_number', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $primaryKey = 'user_id';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id', 'user_id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'user_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function hasVendors(): bool
    {
        return $this->vendors()->exists();
    }

    public function isVendor(): bool
    {
        return $this->vendors()->active()->exists();
    }

    public function activeVendors(): HasMany
    {
        return $this->vendors()->active()->orderBy('vendor_id');
    }

    public function activeVendor(?int $preferredVendorId = null): ?Vendor
    {
        $vendorId = $preferredVendorId ?? (session()->isStarted() ? session('active_vendor_id') : null);

        if ($vendorId !== null) {
            $selected = $this->vendors()->active()->whereKey((int) $vendorId)->first();
            if ($selected) {
                return $selected;
            }
        }

        return $this->vendors()->active()->orderBy('vendor_id')->first();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
