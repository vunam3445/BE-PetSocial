<?php
namespace App\Repositories\PostRepository;
use App\Repositories\Base\BaseInterface;
interface PostInterface extends BaseInterface{
    public function getQuery();
    
}