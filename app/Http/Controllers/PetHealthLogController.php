<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PetHealthLog;
class PetHealthLogController extends Controller
{
    protected PetHealthLog $petHealthLogService;

    public function __construct(PetHealthLog $petHealthLogService)
    {
        $this->petHealthLogService = $petHealthLogService;
    }



    public function store(Request $request)
    {
        $data = $request->all();
        $petHealthLog = $this->petHealthLogService->createPetHealthLog($data);
        return response()->json($petHealthLog, 201);
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
