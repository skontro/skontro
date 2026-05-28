<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
            if (empty($tenant->slug)) {
                $tenant->slug = static::uniqueSlug($tenant->name);
            }
        });
    }

    /**
     * Generate a slug from the name, suffixing -2, -3, … on collision.
     */
    protected static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Bind routes by the public UUID, never the internal id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
