<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TemplateCategory extends Model
{
    use CrudTrait;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'category',
        'category_slug',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->category_slug = Str::slug($model->category);
        });
    }

    public function templates()
    {
        return $this->hasMany(Template::class, 'category_id', 'category_id');
    }
}
