<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Report extends Model
{
    use SoftDeletes, HasUuids;

    protected $primaryKey = 'report_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'report_id',
        'reportable_id',   // ID của đối tượng bị báo cáo (Post ID, User ID...)
        'reportable_type', // Class của đối tượng (App\Models\Post, App\Models\User...)
        'reported_by',     // ID người báo cáo
        'reason',
        'description',
        'status',
        'handled_by',
        'handled_at',
    ];

    /**
     * Định nghĩa quan hệ đa hình
     * Giúp lấy ra đối tượng bị báo cáo (Post, User, Group...)
     */
    public function reportable()
    {
        return $this->morphTo();
    }

    /**
     * Quan hệ với người báo cáo
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    /**
     * Quan hệ với người xử lý báo cáo (Admin/Mod)
     */
    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by', 'user_id');
    }
}