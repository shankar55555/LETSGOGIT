<?php

namespace App\Http\Controllers;

use App\Traits\StatusUpdateTrait;
use App\Models\User; // Example model

class ExampleController extends Controller
{
    use StatusUpdateTrait;

    // public function updateStatus($id)
    // {
    //     // Example usage of the trait
    //     // Parameters: id, new status value, model class
    //     return $this->updateModelStatus($id, request('status'), User::class);
    // }
}
