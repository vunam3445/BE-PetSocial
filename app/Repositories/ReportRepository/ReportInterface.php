<?php
namespace App\Repositories\ReportRepository;

use App\Repositories\Base\BaseInterface;

interface ReportInterface extends BaseInterface {
    public function getReportOfGroup(string $groupId, int $limit);
}