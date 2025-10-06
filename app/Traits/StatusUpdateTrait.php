<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

trait StatusUpdateTrait
{
    /**
     * Update status for any model
     * @param string $id UUID or ID of the model
     * @param string $status New status value
     * @param string $model Fully qualified model class name
     * @return JsonResponse
     */
    // public function updateModelStatus($id, $status, $model): JsonResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         $record = $model::find($id);

    //         if (!$record) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Record not found!'
    //             ], 404);
    //         }

    //         $record->status = $status;
    //         $record->save();

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Status updated successfully',
    //             'data' => $record
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
