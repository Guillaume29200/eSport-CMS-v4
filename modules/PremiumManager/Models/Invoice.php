<?php
declare(strict_types=1);

namespace PremiumManager\Models;

/**
 * Model Invoice
 * 
 * Représente une facture de paiement
 * 
 * @author Guillaume
 */
class Invoice
{
    public int $id;
    public string $invoiceNumber;
    public int $userId;
    public int $transactionId;
    public float $amount;
    public float $taxAmount;
    public float $totalAmount;
    public string $currency;
    public string $status; // draft, sent, paid, cancelled
    public string $issuedAt;
    public ?string $dueAt;
    public ?string $paidAt;
    public ?string $pdfPath;
    public ?string $notes;
    public string $createdAt;
    public string $updatedAt;
    
    // Données supplémentaires (jointures)
    public ?array $user = null;
    public ?array $transaction = null;
    
    /**
     * Créer depuis tableau
     */
    public static function fromArray(array $data): self
    {
        $invoice = new self();
        
        $invoice->id = (int)$data['id'];
        $invoice->invoiceNumber = $data['invoice_number'];
        $invoice->userId = (int)$data['user_id'];
        $invoice->transactionId = (int)$data['transaction_id'];
        $invoice->amount = (float)$data['amount'];
        $invoice->taxAmount = (float)$data['tax_amount'];
        $invoice->totalAmount = (float)$data['total_amount'];
        $invoice->currency = $data['currency'];
        $invoice->status = $data['status'];
        $invoice->issuedAt = $data['issued_at'];
        $invoice->dueAt = $data['due_at'] ?? null;
        $invoice->paidAt = $data['paid_at'] ?? null;
        $invoice->pdfPath = $data['pdf_path'] ?? null;
        $invoice->notes = $data['notes'] ?? null;
        $invoice->createdAt = $data['created_at'];
        $invoice->updatedAt = $data['updated_at'];
        
        return $invoice;
    }
    
    /**
     * Est payée ?
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
    
    /**
     * Est brouillon ?
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
    
    /**
     * Est envoyée ?
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }
    
    /**
     * Est annulée ?
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
    
    /**
     * Est en retard ?
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->isCancelled() || !$this->dueAt) {
            return false;
        }
        
        return strtotime($this->dueAt) < time();
    }
    
    /**
     * Obtenir montant formaté
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
    
    /**
     * Obtenir taxe formatée
     */
    public function getFormattedTax(): string
    {
        return number_format($this->taxAmount, 2) . ' ' . $this->currency;
    }
    
    /**
     * Obtenir total formaté
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->totalAmount, 2) . ' ' . $this->currency;
    }
    
    /**
     * Obtenir statut formaté
     */
    public function getFormattedStatus(): string
    {
        return match($this->status) {
            'draft' => '📝 Brouillon',
            'sent' => '📧 Envoyée',
            'paid' => '✅ Payée',
            'cancelled' => '❌ Annulée',
            default => $this->status
        };
    }
    
    /**
     * Obtenir badge de statut HTML
     */
    public function getStatusBadge(): string
    {
        $badge = match($this->status) {
            'draft' => '<span class="badge badge-secondary">Brouillon</span>',
            'sent' => '<span class="badge badge-info">Envoyée</span>',
            'paid' => '<span class="badge badge-success">Payée</span>',
            'cancelled' => '<span class="badge badge-danger">Annulée</span>',
            default => '<span class="badge badge-light">' . htmlspecialchars($this->status) . '</span>'
        };
        
        if ($this->isOverdue()) {
            $badge .= ' <span class="badge badge-warning">En retard</span>';
        }
        
        return $badge;
    }
    
    /**
     * Obtenir date d'émission formatée
     */
    public function getFormattedIssuedDate(): string
    {
        return date('d/m/Y', strtotime($this->issuedAt));
    }
    
    /**
     * Obtenir date d'échéance formatée
     */
    public function getFormattedDueDate(): ?string
    {
        if (!$this->dueAt) {
            return null;
        }
        
        return date('d/m/Y', strtotime($this->dueAt));
    }
    
    /**
     * Obtenir date de paiement formatée
     */
    public function getFormattedPaidDate(): ?string
    {
        if (!$this->paidAt) {
            return null;
        }
        
        return date('d/m/Y', strtotime($this->paidAt));
    }
    
    /**
     * Obtenir jours avant/après échéance
     */
    public function getDaysUntilDue(): ?int
    {
        if (!$this->dueAt || $this->isPaid() || $this->isCancelled()) {
            return null;
        }
        
        $now = strtotime('today');
        $due = strtotime(date('Y-m-d', strtotime($this->dueAt)));
        
        return (int)floor(($due - $now) / 86400);
    }
    
    /**
     * Obtenir pourcentage de taxe
     */
    public function getTaxPercentage(): float
    {
        if ($this->amount == 0) {
            return 0;
        }
        
        return ($this->taxAmount / $this->amount) * 100;
    }
    
    /**
     * Le PDF existe-t-il ?
     */
    public function hasPdf(): bool
    {
        return $this->pdfPath !== null && file_exists($this->pdfPath);
    }
    
    /**
     * Obtenir URL de téléchargement PDF
     */
    public function getPdfUrl(): ?string
    {
        if (!$this->hasPdf()) {
            return null;
        }
        
        return "/member/premium/invoices/{$this->id}/download";
    }
    
    /**
     * Peut être modifiée ?
     */
    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }
    
    /**
     * Peut être annulée ?
     */
    public function canBeCancelled(): bool
    {
        return !$this->isPaid() && !$this->isCancelled();
    }
    
    /**
     * Peut être envoyée ?
     */
    public function canBeSent(): bool
    {
        return $this->isDraft();
    }
    
    /**
     * Convertir en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoiceNumber,
            'user_id' => $this->userId,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'status' => $this->status,
            'issued_at' => $this->issuedAt,
            'due_at' => $this->dueAt,
            'paid_at' => $this->paidAt,
            'pdf_path' => $this->pdfPath,
            'notes' => $this->notes,
        ];
    }
}
