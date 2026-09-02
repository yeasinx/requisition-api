<?php

namespace Tests\Unit;

use App\Services\RequisitionNumberService;
use App\Services\RequisitionService;
use App\Services\WorkflowService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class RequisitionServiceTest extends TestCase
{
    protected WorkflowService|MockInterface $workflowService;

    protected RequisitionNumberService|MockInterface $requisitionNumberService;

    protected RequisitionService $requisitionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflowService = Mockery::mock(WorkflowService::class);
        $this->requisitionNumberService = Mockery::mock(RequisitionNumberService::class);

        $this->requisitionService = new RequisitionService(
            $this->workflowService,
            $this->requisitionNumberService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_process_items_calculates_individual_and_overall_totals_correctly(): void
    {
        $rawItems = [
            [
                'item_name' => 'MacBook Pro',
                'description' => 'M3 16-inch',
                'quantity' => 2,
                'unit_price' => 2499.99,
            ],
            [
                'item_name' => 'Dell Monitor',
                'description' => '4K 27-inch',
                'quantity' => 3,
                'unit_price' => 350.50,
            ],
        ];

        [$processedItems, $totalPrice] = $this->requisitionService->processItems($rawItems);

        $this->assertCount(2, $processedItems);
        $this->assertSame(4999.98, $processedItems[0]['total_price']);
        $this->assertSame(1051.50, $processedItems[1]['total_price']);
        $this->assertSame(6051.48, $totalPrice);
    }

    public function test_process_items_handles_single_item_and_precision_rounding(): void
    {
        $rawItems = [
            [
                'item_name' => 'Server License',
                'description' => 'Annual subscription',
                'quantity' => 1,
                'unit_price' => 129.999,
            ],
        ];

        [$processedItems, $totalPrice] = $this->requisitionService->processItems($rawItems);

        $this->assertCount(1, $processedItems);
        $this->assertSame(130.00, $processedItems[0]['total_price']);
        $this->assertSame(130.00, $totalPrice);
    }

    public function test_process_items_handles_empty_item_list(): void
    {
        [$processedItems, $totalPrice] = $this->requisitionService->processItems([]);

        $this->assertIsArray($processedItems);
        $this->assertEmpty($processedItems);
        $this->assertSame(0.0, $totalPrice);
    }
}
