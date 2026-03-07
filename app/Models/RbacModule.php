<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RbacModule extends Model
{
    protected $table = 'rbac_modules';

    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'module_id');
    }
}
