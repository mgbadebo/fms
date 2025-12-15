<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Farm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create 
                            {--name= : User name}
                            {--email= : User email}
                            {--password= : User password}
                            {--role= : User role (OWNER, MANAGER, WORKER, FINANCE, AUDITOR, ADMIN)}
                            {--farm= : Farm ID to attach user to}
                            {--interactive : Run in interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('interactive') || !$this->option('name')) {
            return $this->interactiveMode();
        }

        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');
        $role = $this->option('role');
        $farmId = $this->option('farm');

        if (!$email) {
            $this->error('Email is required. Use --email or --interactive mode.');
            return 1;
        }

        if (!$password) {
            $password = $this->secret('Enter password:');
            if (!$password) {
                $this->error('Password is required.');
                return 1;
            }
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return 1;
        }

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("User created successfully!");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");

        // Assign role if provided
        if ($role) {
            $roleModel = Role::where('name', strtoupper($role))->first();
            if ($roleModel) {
                $user->assignRole($roleModel);
                $this->info("Role '{$role}' assigned to user.");
            } else {
                $this->warn("Role '{$role}' not found. Available roles: " . implode(', ', Role::pluck('name')->toArray()));
            }
        }

        // Attach to farm if provided
        if ($farmId) {
            $farm = Farm::find($farmId);
            if ($farm) {
                $farmRole = $role ? strtoupper($role) : 'WORKER';
                $farm->users()->attach($user->id, ['role' => $farmRole]);
                $this->info("User attached to farm: {$farm->name}");
            } else {
                $this->warn("Farm with ID {$farmId} not found.");
            }
        }

        return 0;
    }

    /**
     * Run the command in interactive mode.
     */
    protected function interactiveMode()
    {
        $this->info('Create a new user account');
        $this->newLine();

        $name = $this->ask('Name', $this->option('name'));
        $email = $this->ask('Email', $this->option('email'));
        $password = $this->secret('Password (min 8 characters)');
        $passwordConfirm = $this->secret('Confirm Password');

        if ($password !== $passwordConfirm) {
            $this->error('Passwords do not match.');
            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return 1;
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return 1;
        }

        // Show available roles
        $roles = Role::pluck('name')->toArray();
        $this->line('Available roles: ' . implode(', ', $roles));
        $role = $this->ask('Role (optional)', $this->option('role'));

        // Show available farms
        $farms = Farm::all();
        if ($farms->count() > 0) {
            $this->newLine();
            $this->line('Available farms:');
            foreach ($farms as $farm) {
                $this->line("  [{$farm->id}] {$farm->name}");
            }
            $farmId = $this->ask('Farm ID (optional)', $this->option('farm'));
        } else {
            $farmId = null;
        }

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->newLine();
        $this->info("✓ User created successfully!");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");

        // Assign role if provided
        if ($role) {
            $roleModel = Role::where('name', strtoupper($role))->first();
            if ($roleModel) {
                $user->assignRole($roleModel);
                $this->info("  Role: {$role}");
            } else {
                $this->warn("  Role '{$role}' not found.");
            }
        }

        // Attach to farm if provided
        if ($farmId) {
            $farm = Farm::find($farmId);
            if ($farm) {
                $farmRole = $role ? strtoupper($role) : 'WORKER';
                $farm->users()->attach($user->id, ['role' => $farmRole]);
                $this->info("  Farm: {$farm->name}");
            } else {
                $this->warn("  Farm with ID {$farmId} not found.");
            }
        }

        $this->newLine();
        $this->line("Login credentials:");
        $this->line("  Email: {$email}");
        $this->line("  Password: [the password you entered]");

        return 0;
    }
}

