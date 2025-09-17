<?php
namespace App\Repositories\CommentRepository;
use App\Repositories\Base\BaseInterface;
interface CommentInterface extends BaseInterface{
    public function getParentCommentsByPost(string $postId, int $limit = 20, array $relations = []);
    public function getByFieldWithPagination(string $field, $value, array $relations = [], int $limit);


}