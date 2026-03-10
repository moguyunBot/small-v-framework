<?php
namespace plugin\blog\app\model;

use think\Model;

class Category extends Model
{
    protected $table = 'blog_categories';
    protected $autoWriteTimestamp = true;

    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    public static function makeSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        if (!$slug) {
            $slug = 'category-' . time();
        }
        $original = $slug;
        $i = 1;
        while (static::where('slug', $slug)->count()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
