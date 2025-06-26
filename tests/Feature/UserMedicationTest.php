<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserMedicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_medication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Http::fake([
            'rxnav.nlm.nih.gov/REST/rxcui/123/historystatus*' => Http::response([
                'rxcuiStatusHistory' => [
                    'attributes' => [
                        'ingredientAndStrength' => [
                            ['baseName' => 'Test Ingredient']
                        ],
                        'doseFormGroupConcept' => [
                            ['doseFormGroupName' => 'Tablet']
                        ]
                    ]
                ]
            ]),
            'rxnav.nlm.nih.gov/REST/rxcui/123/property*' => Http::response([
                'propConceptGroup' => [
                    'propConcept' => [
                        ['propName' => 'RxNorm Name', 'propValue' => 'Test Drug']
                    ]
                ]
            ])
        ]);

        $response = $this->postJson('/api/user/medications', ['rxcui' => '123']);

        $response->assertStatus(201);
        $this->assertCount(1, $user->medications);
    }

    public function test_get_medications()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $medication = $user->medications()->create([
            'rxcui' => '123',
            'name' => 'Test Drug',
            'base_names' => ['Test Ingredient'],
            'dosage_forms' => ['Tablet']
        ]);

        $response = $this->getJson('/api/user/medications');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'rxcui' => '123',
                'name' => 'Test Drug',
                'base_names' => ['Test Ingredient'],
                'dosage_forms' => ['Tablet']
            ]);
    }




    public function test_delete_medication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a medication with specific rxcui
        $medication = $user->medications()->create([
            'rxcui' => '123',
            'name' => 'Test Drug',
            'base_names' => ['Test Ingredient'],
            'dosage_forms' => ['Tablet']
        ]);

        // Delete using rxcui in the URL
        $response = $this->deleteJson('/api/user/medications/' . $medication->rxcui);

        // Assert the expected response structure and status
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Medication deleted successfully',
                'deleted_rxcui' => '123'
            ]);

        // Verify the medication was actually deleted
        $this->assertCount(0, $user->fresh()->medications);
    }

    public function test_delete_nonexistent_medication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/user/medications/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Medication not found'
            ]);
    }

    
}