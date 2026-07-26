<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::create([
            'name' => 'John Doe',
            'company' => 'Acme Corp',
            'email' => 'john@acme.com',
            'phone' => '+1-555-0199',
            'tax_id' => 'TX-123456-78',
            'billing_address' => '123 Acme Way, Cityville, CA 90210',
            'notes' => 'Primary client for core enterprise solutions. Prefers email updates over phone calls.',
        ]);

        Client::create([
            'name' => 'Pepper Potts',
            'company' => 'Stark Industries',
            'email' => 'pepper@stark.com',
            'phone' => '+1-555-0100',
            'tax_id' => 'TX-987654-32',
            'billing_address' => '10880 Malibu Point, Malibu, CA 90265',
            'notes' => 'Demanding client, requests high security and fast turnaround times. Always billing Stark Industries directly.',
        ]);

        Client::create([
            'name' => 'Bruce Wayne',
            'company' => 'Wayne Enterprises',
            'email' => 'bruce@waynecorp.com',
            'phone' => '+1-555-0112',
            'tax_id' => 'TX-456789-01',
            'billing_address' => 'Wayne Manor, Gotham City',
            'notes' => 'Prefers communication in the evenings. Enjoys dark-themed user interfaces.',
        ]);
    }
}
