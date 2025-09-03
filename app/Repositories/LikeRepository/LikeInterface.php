<?php
namespace App\Repositories\LikeRepository;
use App\Repositories\Base\BaseInterface;
interface LikeInterface extends BaseInterface{
    public function getLikesForPost(string $postId);
}