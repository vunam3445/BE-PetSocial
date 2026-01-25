<?php

namespace App\Services;

use App\Repositories\PetHealthLogRepository\PetHealthLogInterface;
use Illuminate\Support\Facades\Storage;

class PetHealthLog
{
    protected $petHealthLogInterface;

    public function __construct(PetHealthLogInterface $petHealthLogInterface)
    {
        $this->petHealthLogInterface = $petHealthLogInterface;
    }

    public function createPetHealthLog(array $data)
    {
        // 1. Xử lý lưu file ảnh vào storage
        if (isset($data['image_file']) && $data['image_file'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['image_file'];

            $path = $file->store('uploads/healthLogs', 'public');
            $data['image_url'] = url(Storage::url($path));
        }

        // 2. Loại bỏ image_file tạm thời
        unset($data['image_file']);

        // 3. Tạo record trong DB
        return \App\Models\PetHealthLog::create($data);
    }
    public function updatePetHealthLog(string $id, array $data)
{
    $petHealthLog = $this->petHealthLogInterface->find($id);

    // Kiểm tra có file thực sự được gửi lên không
    if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
        // 1. Xóa ảnh cũ
        if ($petHealthLog->image_url) {
            $oldPath = str_replace(url('/storage'), 'public', $petHealthLog->image_url);
            Storage::delete($oldPath);
        }

        // 2. Lưu ảnh mới
        $file = $data['image'];
        $path = $file->store('uploads/healthLogs', 'public');
        
        // Gán URL thật từ server
        $data['image_url'] = url(Storage::url($path));
    } else {
        // Nếu không có ảnh mới, hãy chắc chắn không ghi đè image_url bằng link blob tạm thời
        unset($data['image_url']); 
    }

    unset($data['image']);
    return $this->petHealthLogInterface->update($id, $data);
}
    public function deletePetHealthLog(string $id)
    {
        return $this->petHealthLogInterface->delete($id);
    }
    public function getPetHealthLogsByPetId(string $petId)
    {
        return $this->petHealthLogInterface->getByField('pet_id', $petId);
    }

    public function getLogsByPetId(string $petId)
    {
        return $this->petHealthLogInterface->getLogsByPetId($petId);
    }
}
