<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Import trait xử lý UUID
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInterestProfile extends Model
{
    use HasFactory, HasUuids;

    /**
     * Tên bảng tương ứng trong DB.
     */
    protected $table = 'user_interest_profiles';

    /**
     * Chỉ định kiểu dữ liệu của khóa chính là string (vì là UUID).
     */
    protected $keyType = 'string';

    /**
     * Khóa chính không tự động tăng (incrementing).
     */
    public $incrementing = false;

    /**
     * Các thuộc tính có thể gán hàng loạt (Mass Assignment).
     */
    protected $fillable = [
        'user_id',
        'tag_name',
        'score',
    ];

    /**
     * Ép kiểu dữ liệu (Casting).
     */
    protected $casts = [
        'score' => 'float',
    ];

    /**
     * Thiết lập mối quan hệ với model User.
     * Giả định bảng users sử dụng 'user_id' làm khóa chính như trong migration của bạn.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}