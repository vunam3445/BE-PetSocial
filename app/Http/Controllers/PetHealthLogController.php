<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PetHealthLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Pet;
use App\Models\PetHealthLog as PetHealthLogModel;
class PetHealthLogController extends Controller
{
    use AuthorizesRequests;
    protected PetHealthLog $petHealthLogService;

    public function __construct(PetHealthLog $petHealthLogService)
    {
        $this->petHealthLogService = $petHealthLogService;
    }



    public function store(Request $request)
    {
        // 1. Định nghĩa thông báo lỗi tiếng Việt
        $messages = [
            'pet_id.required'      => 'Vui lòng chọn thú cưng.',
            'category_id.required' => 'Vui lòng chọn danh mục sức khỏe.',
            'title.required'       => 'Tiêu đề không được để trống.',
            'title.string'         => 'Tiêu đề phải là một chuỗi ký tự.',
            'image_file.image'     => 'Tệp tải lên phải là hình ảnh.',
            'image_file.mimes'     => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_file.max'       => 'Kích thước ảnh tối đa là 2MB.',
        ];

        // 2. Validate với thông báo tùy chỉnh
        $request->validate([
            'pet_id'      => 'required',
            'category_id' => 'required',
            'title'       => 'required|string',
            'image_file'  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5096',
        ], $messages);
        $pet = Pet::findOrFail($request->pet_id);

        //  2. Gọi Policy: Bạn có phải chủ của Pet này không?
        $this->authorize('create', [PetHealthLogModel::class, $pet]);
        $data = $request->all();

        // Xử lý file nếu có
        if ($request->hasFile('image_file')) {
            $data['image_file'] = $request->file('image_file');
        }

        $petHealthLog = $this->petHealthLogService->createPetHealthLog($data);

        return response()->json([
            'message' => 'Lưu nhật ký sức khỏe thành công!',
            'data'    => $petHealthLog
        ], 201);
    }
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $petHealthLog = $this->petHealthLogService->updatePetHealthLog($id, $data);
        return response()->json($petHealthLog);
    }

    public function destroy(string $id)
    {
        $this->petHealthLogService->deletePetHealthLog($id);
        return response()->json(['message' => 'Pet health log deleted successfully']);
    }

    public function getLogsByPetId(string $petId)
    {
        $logs = $this->petHealthLogService->getLogsByPetId($petId);
        return response()->json($logs);
    }
}
