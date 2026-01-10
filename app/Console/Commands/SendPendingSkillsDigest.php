<?php

namespace App\Console\Commands;

use App\Mail\PendingSkillsDigest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPendingSkillsDigest extends Command
{
    protected $signature = 'skills:send-pending-digest';

    protected $description = 'Send a digest email to admins listing pending skills awaiting approval';

    public function handle(): int
    {
        $pendingSkills = Skill::pending()->with('users')->get();

        if ($pendingSkills->isEmpty()) {
            $this->info('No pending skills to report.');

            return self::SUCCESS;
        }

        $admins = User::where('is_admin', true)->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found to notify.');

            return self::SUCCESS;
        }

        foreach ($admins as $admin) {
            Mail::to($admin)->send(new PendingSkillsDigest($pendingSkills));
        }

        $this->info("Sent pending skills digest to {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
