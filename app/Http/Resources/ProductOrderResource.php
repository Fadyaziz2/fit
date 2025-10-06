<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $statusKey = 'message.' . $this->status;
        $statusLabel = __($statusKey);

        if ($statusLabel === $statusKey) {
            $statusLabel = ucfirst(str_replace('_', ' ', $this->status));
        }

        $paymentKey = 'message.' . $this->payment_method;
        $paymentLabel = __($paymentKey);

        if ($paymentLabel === $paymentKey) {
            $paymentLabel = ucfirst(str_replace('_', ' ', $this->payment_method));
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'status_label' => $statusLabel,
            'status_comment' => $this->status_comment,
            'unit_price' => (float) ($this->unit_price ?? 0),
            'total_price' => (float) ($this->total_price ?? 0),
            'payment_method' => $this->payment_method,
            'payment_method_label' => $paymentLabel,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'shipping_address' => $this->shipping_address,
            'customer_note' => $this->customer_note,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'created_at_formatted' => optional($this->created_at)->format('d M Y H:i'),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
