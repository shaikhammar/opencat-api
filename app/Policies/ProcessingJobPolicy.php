<?php

namespace App\Policies;

use App\Models\ProcessingJob;
use App\Models\User;

class ProcessingJobPolicy
{
    public function view(User $user, ProcessingJob $job): bool   { return $user->id === $job->user_id; }
    public function delete(User $user, ProcessingJob $job): bool { return $user->id === $job->user_id; }
}
