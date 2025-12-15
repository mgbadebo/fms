<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabelTemplate;
use App\Models\PrintedLabel;
use App\Services\Label\MockLabelPrinterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LabelController extends Controller
{
    public function __construct(
        protected MockLabelPrinterService $labelPrinter
    ) {}

    /**
     * Print a label for a target entity.
     */
    public function print(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label_template_id' => 'sometimes|exists:label_templates,id',
            'label_template_code' => 'sometimes|string',
            'target_type' => 'required|string|in:App\Models\HarvestLot,App\Models\StorageUnit,App\Models\SalesOrder',
            'target_id' => 'required|integer',
            'printer_name' => 'sometimes|string|nullable',
        ]);

        // Resolve label template
        if (isset($validated['label_template_id'])) {
            $template = LabelTemplate::findOrFail($validated['label_template_id']);
        } elseif (isset($validated['label_template_code'])) {
            $template = LabelTemplate::where('code', $validated['label_template_code'])->firstOrFail();
        } else {
            throw ValidationException::withMessages([
                'label_template' => 'Either label_template_id or label_template_code is required.',
            ]);
        }

        // Verify target exists
        $targetClass = $validated['target_type'];
        $target = $targetClass::findOrFail($validated['target_id']);

        // Prepare data for template
        $data = $this->prepareLabelData($target, $template->target_type);

        // Render template
        $renderedLabel = $this->labelPrinter->renderTemplate(
            $template->template_body,
            $template->template_engine,
            $data
        );

        // Print (mock)
        $printed = $this->labelPrinter->print($renderedLabel, $validated['printer_name'] ?? null);

        // Store printed label record
        $printedLabel = PrintedLabel::create([
            'farm_id' => $template->farm_id,
            'label_template_id' => $template->id,
            'target_type' => $validated['target_type'],
            'target_id' => $validated['target_id'],
            'printed_at' => now(),
            'printer_name' => $validated['printer_name'] ?? null,
            'payload_sent' => $renderedLabel,
        ]);

        return response()->json([
            'data' => [
                'printed_label' => $printedLabel,
                'rendered_content' => $renderedLabel,
                'success' => $printed,
            ],
        ], 201);
    }

    /**
     * Prepare data for label template based on target type.
     */
    protected function prepareLabelData($target, string $targetType): array
    {
        $data = [];

        if ($target instanceof \App\Models\HarvestLot) {
            $data = [
                'traceability_id' => $target->traceability_id,
                'code' => $target->code,
                'net_weight' => $target->net_weight,
                'gross_weight' => $target->gross_weight,
                'weight_unit' => $target->weight_unit,
                'harvested_at' => $target->harvested_at?->format('Y-m-d H:i'),
                'field_name' => $target->field->name ?? '',
                'zone_name' => $target->zone->name ?? '',
                'quality_grade' => $target->quality_grade ?? '',
                'crop_name' => $target->cropPlan->crop->name ?? '',
            ];
        } elseif ($target instanceof \App\Models\StorageUnit) {
            $data = [
                'code' => $target->code,
                'type' => $target->type,
                'capacity_value' => $target->capacity_value,
                'capacity_unit' => $target->capacity_unit,
                'location_name' => $target->inventoryLocation->name ?? '',
            ];
        } elseif ($target instanceof \App\Models\SalesOrder) {
            $data = [
                'order_number' => $target->order_number,
                'order_date' => $target->order_date->format('Y-m-d'),
                'customer_name' => $target->customer->name ?? '',
                'status' => $target->status,
            ];
        }

        return $data;
    }
}
