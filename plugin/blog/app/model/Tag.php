<?php
namespace plugin\blog\app\model;

use think\Model;

class Tag extends Model
{
    protected $table = 'blog_tags';
    protected $autoWriteTimestamp = true;

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'blog_post_tags', 'post_id', 'tag_id');
    }

    public static function makeSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        if (!$slug) {
            $slug = 'tag-' . time();
        }
        $original = $slug;
        $i = 1;
        while (static::where('slug', $slug)->count()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
