<?php
namespace plugin\blog\app\model;

use think\Model;

class Comment extends Model
{
    protected $table = 'blog_comments';
    protected $autoWriteTimestamp = true;

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
