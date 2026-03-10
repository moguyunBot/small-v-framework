<?php
namespace plugin\blog\app\model;

use think\Model;

class Post extends Model
{
    protected $table = 'blog_posts';
    protected $autoWriteTimestamp = true;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_post_tags', 'tag_id', 'post_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public static function makeSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        if (!$slug) {
            $slug = 'post-' . time();
        }
        // 如果已存在，加随机后缀
        $original = $slug;
        $i = 1;
        while (static::where('slug', $slug)->count()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
