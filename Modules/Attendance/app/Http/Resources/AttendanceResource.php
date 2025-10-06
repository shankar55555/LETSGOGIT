<?php

namespace Modules\Attendance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'duration' => $this->when($this->logout_time, function () {
                $diffInMinutes = $this->login_time->diffInMinutes($this->logout_time);
                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;
    
                $parts = [];
                if ($hours > 0) {
                    $parts[] = "$hours hour" . ($hours > 1 ? 's' : '');
                }
                if ($minutes > 0 || $hours == 0) {
                    $parts[] = "$minutes minute" . ($minutes > 1 ? 's' : '');
                }
    
                return implode(' ', $parts);
            }),
            
            // Format time fields for display
            'login_time_formatted' => $this->login_time->format('h:i:s A'),
            'logout_time_formatted' => $this->logout_time?->format('h:i:s A'),
        ]);
    }

}
