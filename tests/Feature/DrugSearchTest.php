<?php


use App\Models\User;
use App\Models\UserMedication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrugSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_drug_search()
    {
        // Mock the HTTP request to RxNorm API
        Http::fake([
            'rxnav.nlm.nih.gov/REST/drugs*' => Http::response([
                'drugGroup' => [
                    'conceptGroup' => [
                        [
                            'conceptProperties' => [
                                ['rxcui' => '123', 'name' => 'Test Drug']
                            ]
                        ]
                    ]
                ]
            ]),
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
            ])
        ]);

        $response = $this->getJson('/api/drugs/search?drug_name=test');

        $response->assertStatus(200)
            ->assertJson([
                [
                    'rxcui' => '123',
                    'name' => 'Test Drug',
                    'base_names' => ['Test Ingredient'],
                    'dosage_forms' => ['Tablet']
                ]
            ]);
    }

    
}