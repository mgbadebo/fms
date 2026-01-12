<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Farm;
use App\Models\GreenhouseProductionCycle;
use App\Models\ProductionCycleDailyLog;
use App\Models\ProductionCycleAlert;
use Carbon\Carbon;

class CheckMissingDailyLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production-cycles:check-missing-daily-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for missing daily logs and create alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for missing daily logs...');
        
        $alertsCreated = 0;
        
        // Process each farm (timezone-aware)
        $farms = Farm::all();
        
        foreach ($farms as $farm) {
            $timezone = $farm->default_timezone ?? config('app.timezone');
            $cutoffTime = $farm->daily_log_cutoff_time ?? '18:00:00';
            
            // Get "today" in farm timezone
            $now = Carbon::now($timezone);
            $today = $now->toDateString();
            $cutoff = Carbon::parse($today . ' ' . $cutoffTime, $timezone);
            
            // Only check if we're past cutoff time
            if ($now->lt($cutoff)) {
                continue; // Too early, skip this farm
            }
            
            // Find cycles with status ACTIVE or HARVESTING
            $activeCycles = GreenhouseProductionCycle::where('farm_id', $farm->id)
                ->whereIn('cycle_status', ['ACTIVE', 'HARVESTING'])
                ->get();
            
            foreach ($activeCycles as $cycle) {
                // Check if a SUBMITTED log exists for today
                $hasSubmittedLog = ProductionCycleDailyLog::where('production_cycle_id', $cycle->id)
                    ->where('log_date', $today)
                    ->where('status', 'SUBMITTED')
                    ->exists();
                
                if (!$hasSubmittedLog) {
                    // Check if alert already exists (prevent duplicates)
                    $alertExists = ProductionCycleAlert::where('production_cycle_id', $cycle->id)
                        ->where('log_date', $today)
                        ->where('alert_type', 'MISSING_DAILY_LOG')
                        ->exists();
                    
                    if (!$alertExists) {
                        // Create alert
                        ProductionCycleAlert::create([
                            'farm_id' => $farm->id,
                            'production_cycle_id' => $cycle->id,
                            'log_date' => $today,
                            'alert_type' => 'MISSING_DAILY_LOG',
                            'message' => "Daily log not submitted for production cycle {$cycle->production_cycle_code} on {$today}",
                            'severity' => 'MEDIUM',
                            'is_resolved' => false,
                        ]);
                        
                        $alertsCreated++;
                        
                        // TODO: Send notification to responsible_supervisor_user_id
                        // This would integrate with your notification system
                    }
                }
            }
        }
        
        $this->info("Created {$alertsCreated} alert(s) for missing daily logs.");
        
        return Command::SUCCESS;
    }
}
