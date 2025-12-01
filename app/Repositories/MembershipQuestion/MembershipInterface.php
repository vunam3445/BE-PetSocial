<?php
namespace App\Repositories\MembershipQuestion;

use App\Repositories\Base\BaseInterface;

interface MembershipInterface extends BaseInterface {
    public function createJoinAnswer(array $data);

}