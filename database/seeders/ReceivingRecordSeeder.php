<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReceivingRecord;
use App\Models\User;
use Carbon\Carbon;

class ReceivingRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a user to associate records with (fallback to first user or create one)
        $user = User::first();
        
        if (!$user) {
            echo "No users found. Please create users first.\n";
            return;
        }

        $categories = [
            'Barangay Affairs',
            'Financial Assistance',
            'Social Services',
            'Use of Facilities',
            'Appointment/Meeting',
            'Other Request',
            'Use of Vehicle and Ambulance',
            'E-Concern',
            'FB Pages',
            'Contact'
        ];

        $departments = [
            'Receiving',
            'Barangay Affairs',
            'Financial Assistance',
            'Social Services',
            'Use of Facilities',
            'Appointment/Meeting',
        ];

        $statuses = ['pending', 'approved', 'disapproved', 'served', 'on process', 'for releasing'];

        $districts = ['District 1', 'District 2', 'District 3', 'District 4'];
        
        $names = [
            'Juan Dela Cruz',
            'Maria Santos',
            'Pedro Garcia',
            'Ana Reyes',
            'Jose Torres',
            'Carmen Lopez',
            'Ricardo Ramos',
            'Sofia Martinez',
            'Miguel Fernandez',
            'Isabella Gonzales'
        ];

        echo "Creating sample receiving records...\n";

        // Create records for the past 12 months
        for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
            $recordsThisMonth = rand(15, 40); // Random number of records per month
            
            for ($i = 0; $i < $recordsThisMonth; $i++) {
                $category = $categories[array_rand($categories)];
                $department = $departments[array_rand($departments)];
                $status = $statuses[array_rand($statuses)];
                
                // Random date within the month
                $date = Carbon::now()
                    ->subMonths($monthsAgo)
                    ->setDay(rand(1, 28))
                    ->setHour(rand(8, 17))
                    ->setMinute(rand(0, 59));

                ReceivingRecord::create([
                    'control_no' => 'CTRL-' . str_pad(ReceivingRecord::count() + 1, 6, '0', STR_PAD_LEFT),
                    'date' => $date,
                    'particulars' => 'Sample particulars for ' . $category,
                    'department' => $department,
                    'organization_barangay' => 'Barangay ' . rand(1, 100),
                    'municipality_address' => 'Sample Municipality',
                    'province' => 'Sample Province',
                    'name' => $names[array_rand($names)],
                    'contact' => '09' . rand(100000000, 999999999),
                    'action_taken' => $status === 'pending' ? null : 'Sample action taken',
                    'amount_approved' => in_array($category, ['Financial Assistance']) ? rand(1000, 50000) : null,
                    'status' => $status,
                    'user_id' => $user->id,
                    'district' => $districts[array_rand($districts)],
                    'category' => $category,
                    'type' => 'Type ' . rand(1, 3),
                    'requisitioner' => $names[array_rand($names)],
                    'served_request' => in_array($status, ['served', 'approved']) ? 'Yes' : 'No',
                    'remarks' => 'Sample remarks for this record',
                    'processed_by_user_id' => $status !== 'pending' ? $user->id : null,
                    'processed_at' => $status !== 'pending' ? $date->addHours(rand(1, 48)) : null,
                    'approved_at' => $status === 'approved' ? $date->addHours(rand(1, 24)) : null,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        $totalRecords = ReceivingRecord::count();
        echo "Successfully created {$totalRecords} receiving records!\n";
        echo "\nBreakdown by status:\n";
        foreach ($statuses as $status) {
            $count = ReceivingRecord::where('status', $status)->count();
            echo "  - {$status}: {$count}\n";
        }
        echo "\nBreakdown by category:\n";
        foreach ($categories as $category) {
            $count = ReceivingRecord::where('category', $category)->count();
            echo "  - {$category}: {$count}\n";
        }
    }
}
