<?php
namespace App\Repositories\MessageRepository;
use App\Repositories\Base\BaseInterface;
interface MessageInterface extends BaseInterface{
       public function getDistinctSenders(string $conversationId);

} 