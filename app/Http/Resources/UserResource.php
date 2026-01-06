<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_photo_url' => $this->profile_photo_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Include farms with membership details if loaded
        if ($this->relationLoaded('farms')) {
            $data['farms'] = $this->farms->map(function ($farm) {
                return [
                    'id' => $farm->id,
                    'name' => $farm->name,
                    'farm_code' => $farm->farm_code,
                    'membership_status' => $farm->pivot->membership_status,
                    'employment_category' => $farm->pivot->employment_category,
                    'pay_type' => $farm->pivot->pay_type,
                    'pay_rate' => $farm->pivot->pay_rate,
                    'start_date' => $farm->pivot->start_date,
                    'end_date' => $farm->pivot->end_date,
                    'notes' => $farm->pivot->notes,
                ];
            });
        }

        // Include job roles grouped by farm if loaded
        if ($this->relationLoaded('jobRoleAssignments')) {
            $jobRolesByFarm = $this->jobRoleAssignments
                ->whereNull('ended_at') // Only active assignments
                ->groupBy('farm_id')
                ->map(function ($assignments) {
                    return $assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'worker_job_role_id' => $assignment->worker_job_role_id,
                            'job_role_name' => $assignment->workerJobRole->name ?? null,
                            'job_role_code' => $assignment->workerJobRole->code ?? null,
                            'assigned_at' => $assignment->assigned_at?->toIso8601String(),
                            'notes' => $assignment->notes,
                        ];
                    });
                });
            $data['job_roles'] = $jobRolesByFarm;
        }

        // Include permissions only if explicitly requested (to avoid heavy payloads)
        if ($request->has('include_permissions') && $this->relationLoaded('permissions')) {
            $data['permissions'] = $this->permissions->pluck('name');
        }

        return $data;
    }
}
