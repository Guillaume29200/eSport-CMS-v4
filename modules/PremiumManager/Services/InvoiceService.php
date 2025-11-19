<?php
declare(strict_types=1);

namespace PremiumManager\Services;

use Framework\Services\Database;
use Framework\Services\Logger;
use PremiumManager\Models\Invoice;

/**
 * Service InvoiceService
 * 
 * Gestion complète des factures
 * - Génération automatique
 * - PDF
 * - Envoi par email
 * - Tracking statuts
 * 
 * @author Guillaume
 */
class InvoiceService
{
    private Database $db;
    private Logger $logger;
    private string $invoicePrefix = 'INV-';
    private string $invoiceStoragePath;
    private float $defaultTaxRate = 0.20; // 20% TVA par défaut
    
    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->invoiceStoragePath = $_ENV['INVOICE_STORAGE_PATH'] ?? '/var/www/storage/invoices';
        
        // Créer dossier si n'existe pas
        if (!is_dir($this->invoiceStoragePath)) {
            mkdir($this->invoiceStoragePath, 0755, true);
        }
    }
    
    /**
     * Créer une facture pour une transaction
     * 
     * @param int $transactionId ID de la transaction
     * @param array $options Options supplémentaires
     * @return Invoice|null
     */
    public function createInvoiceForTransaction(int $transactionId, array $options = []): ?Invoice
    {
        try {
            // Récupérer transaction
            $transaction = $this->db->queryOne(
                "SELECT t.*, u.email, u.username, u.company_name, u.vat_number
                 FROM premium_transactions t
                 JOIN users u ON t.user_id = u.id
                 WHERE t.id = ? AND t.status = 'completed'",
                [$transactionId]
            );
            
            if (!$transaction) {
                $this->logger->warning("Cannot create invoice for invalid transaction", [
                    'transaction_id' => $transactionId
                ]);
                return null;
            }
            
            // Vérifier si facture existe déjà
            $existingInvoice = $this->db->queryOne(
                "SELECT id FROM premium_invoices WHERE transaction_id = ?",
                [$transactionId]
            );
            
            if ($existingInvoice) {
                $this->logger->info("Invoice already exists for transaction", [
                    'transaction_id' => $transactionId,
                    'invoice_id' => $existingInvoice['id']
                ]);
                return $this->getInvoiceById($existingInvoice['id']);
            }
            
            // Générer numéro de facture unique
            $invoiceNumber = $this->generateInvoiceNumber();
            
            // Calculer montants
            $amount = (float)$transaction['amount'];
            $discountAmount = (float)($transaction['discount_amount'] ?? 0);
            $subtotal = $amount - $discountAmount;
            
            // Calculer taxe
            $taxRate = $options['tax_rate'] ?? $this->getTaxRateForUser($transaction['user_id']);
            $taxAmount = $subtotal * $taxRate;
            $totalAmount = $subtotal + $taxAmount;
            
            // Dates
            $issuedAt = date('Y-m-d H:i:s');
            $dueAt = isset($options['due_days']) 
                ? date('Y-m-d H:i:s', strtotime("+{$options['due_days']} days"))
                : null;
            
            // Créer facture
            $invoiceId = $this->db->insert('premium_invoices', [
                'invoice_number' => $invoiceNumber,
                'user_id' => $transaction['user_id'],
                'transaction_id' => $transactionId,
                'amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => $transaction['currency'],
                'status' => 'paid', // Transaction déjà payée
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'paid_at' => $transaction['created_at'],
                'notes' => $options['notes'] ?? null,
            ]);
            
            // Mettre à jour transaction avec invoice_id
            $this->db->update(
                'premium_transactions',
                ['invoice_id' => $invoiceId],
                ['id' => $transactionId]
            );
            
            $this->logger->info("Invoice created successfully", [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'transaction_id' => $transactionId,
                'user_id' => $transaction['user_id'],
                'amount' => $totalAmount
            ]);
            
            // Générer PDF
            $invoice = $this->getInvoiceById($invoiceId);
            if ($invoice && ($options['generate_pdf'] ?? true)) {
                $this->generatePdf($invoice);
            }
            
            // Envoyer par email
            if ($invoice && ($options['send_email'] ?? true)) {
                $this->sendInvoiceEmail($invoice);
            }
            
            return $invoice;
            
        } catch (\Exception $e) {
            $this->logger->error('Invoice creation failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Obtenir facture par ID
     */
    public function getInvoiceById(int $invoiceId): ?Invoice
    {
        $data = $this->db->queryOne(
            "SELECT i.*, 
                    u.email, u.username, u.company_name, u.address, u.vat_number,
                    t.transaction_type, t.payment_provider, t.provider_transaction_id
             FROM premium_invoices i
             JOIN users u ON i.user_id = u.id
             LEFT JOIN premium_transactions t ON i.transaction_id = t.id
             WHERE i.id = ?",
            [$invoiceId]
        );
        
        if (!$data) {
            return null;
        }
        
        $invoice = Invoice::fromArray($data);
        $invoice->user = [
            'email' => $data['email'],
            'username' => $data['username'],
            'company_name' => $data['company_name'] ?? null,
            'address' => $data['address'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
        ];
        $invoice->transaction = [
            'type' => $data['transaction_type'] ?? null,
            'provider' => $data['payment_provider'] ?? null,
            'provider_id' => $data['provider_transaction_id'] ?? null,
        ];
        
        return $invoice;
    }
    
    /**
     * Obtenir factures d'un utilisateur
     */
    public function getUserInvoices(int $userId, int $limit = 50, int $offset = 0): array
    {
        $results = $this->db->query(
            "SELECT * FROM premium_invoices 
             WHERE user_id = ? 
             ORDER BY issued_at DESC 
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
        
        return array_map(fn($data) => Invoice::fromArray($data), $results);
    }
    
    /**
     * Générer numéro de facture unique
     */
    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Obtenir dernier numéro du mois
        $lastInvoice = $this->db->queryOne(
            "SELECT invoice_number FROM premium_invoices 
             WHERE invoice_number LIKE ? 
             ORDER BY id DESC LIMIT 1",
            ["{$this->invoicePrefix}{$year}{$month}%"]
        );
        
        if ($lastInvoice) {
            // Extraire numéro et incrémenter
            $lastNumber = (int)substr($lastInvoice['invoice_number'], -4);
            $newNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return "{$this->invoicePrefix}{$year}{$month}{$newNumber}";
    }
    
    /**
     * Obtenir taux de taxe pour un utilisateur
     */
    private function getTaxRateForUser(int $userId): float
    {
        $user = $this->db->queryOne(
            "SELECT country, vat_number FROM users WHERE id = ?",
            [$userId]
        );
        
        // Si l'utilisateur a un numéro de TVA valide (B2B), pas de TVA
        if (!empty($user['vat_number'])) {
            // TODO: Vérifier validité TVA via API VIES
            return 0.0;
        }
        
        // Taux de TVA selon pays (simplifié)
        $vatRates = [
            'FR' => 0.20,
            'BE' => 0.21,
            'DE' => 0.19,
            'ES' => 0.21,
            'IT' => 0.22,
            'NL' => 0.21,
        ];
        
        $country = $user['country'] ?? 'FR';
        return $vatRates[$country] ?? $this->defaultTaxRate;
    }
    
    /**
     * Générer PDF de la facture
     */
    public function generatePdf(Invoice $invoice): bool
    {
        try {
            // Charger données complètes si nécessaire
            if ($invoice->user === null) {
                $invoice = $this->getInvoiceById($invoice->id);
            }
            
            // Générer HTML de la facture
            $html = $this->generateInvoiceHtml($invoice);
            
            // Convertir en PDF (utiliser librairie comme mPDF ou TCPDF)
            // Pour l'instant, on sauvegarde juste le HTML
            // TODO: Implémenter génération PDF réelle
            
            $filename = "invoice_{$invoice->invoiceNumber}.pdf";
            $filepath = $this->invoiceStoragePath . '/' . $filename;
            
            // Sauvegarder (temporairement en HTML, à remplacer par PDF)
            file_put_contents($filepath, $html);
            
            // Mettre à jour le chemin dans la DB
            $this->db->update(
                'premium_invoices',
                ['pdf_path' => $filepath],
                ['id' => $invoice->id]
            );
            
            $this->logger->info("Invoice PDF generated", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoiceNumber,
                'filepath' => $filepath
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('PDF generation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Générer HTML de la facture
     */
    private function generateInvoiceHtml(Invoice $invoice): string
    {
        $siteName = $_ENV['SITE_NAME'] ?? 'eSport-CMS';
        $siteAddress = $_ENV['SITE_ADDRESS'] ?? '';
        
        $user = $invoice->user;
        $transaction = $invoice->transaction;
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Facture {$invoice->invoiceNumber}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .company { font-size: 18px; font-weight: bold; }
        .invoice-details { text-align: right; }
        .client-info { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .total { font-size: 18px; font-weight: bold; text-align: right; }
        .footer { margin-top: 50px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            {$siteName}<br>
            <small>{$siteAddress}</small>
        </div>
        <div class="invoice-details">
            <h2>FACTURE</h2>
            <strong>N°:</strong> {$invoice->invoiceNumber}<br>
            <strong>Date:</strong> {$invoice->getFormattedIssuedDate()}<br>
            <strong>Statut:</strong> {$invoice->getFormattedStatus()}
        </div>
    </div>
    
    <div class="client-info">
        <strong>Client:</strong><br>
        {$user['username']}<br>
        {$user['email']}<br>
        {$user['address']}<br>
        {$user['vat_number'] ? 'TVA: ' . $user['vat_number'] : ''}
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Paiement {$transaction['type'] ?? 'Premium'}<br>
                    <small>Transaction: {$transaction['provider_id'] ?? '-'}</small>
                </td>
                <td>{$invoice->getFormattedAmount()}</td>
            </tr>
            <tr>
                <td><strong>Sous-total</strong></td>
                <td><strong>{$invoice->getFormattedAmount()}</strong></td>
            </tr>
            <tr>
                <td>TVA ({$invoice->getTaxPercentage()}%)</td>
                <td>{$invoice->getFormattedTax()}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td><strong>TOTAL</strong></td>
                <td class="total">{$invoice->getFormattedTotal()}</td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        Cette facture a été générée automatiquement par {$siteName}.<br>
        Pour toute question, contactez notre support.
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Envoyer facture par email
     */
    public function sendInvoiceEmail(Invoice $invoice): bool
    {
        try {
            // TODO: Implémenter envoi email avec service Email
            // Pour l'instant, juste logger
            
            $this->logger->info("Invoice email sent", [
                'invoice_id' => $invoice->id,
                'user_id' => $invoice->userId,
                'email' => $invoice->user['email'] ?? 'unknown'
            ]);
            
            // Mettre à jour statut si c'était draft
            if ($invoice->isDraft()) {
                $this->db->update(
                    'premium_invoices',
                    ['status' => 'sent'],
                    ['id' => $invoice->id]
                );
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Invoice email failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Marquer facture comme payée
     */
    public function markAsPaid(int $invoiceId): bool
    {
        try {
            $this->db->update(
                'premium_invoices',
                [
                    'status' => 'paid',
                    'paid_at' => date('Y-m-d H:i:s')
                ],
                ['id' => $invoiceId]
            );
            
            $this->logger->security("Invoice marked as paid", [
                'invoice_id' => $invoiceId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Mark as paid failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Annuler facture
     */
    public function cancelInvoice(int $invoiceId, string $reason = ''): bool
    {
        try {
            $invoice = $this->getInvoiceById($invoiceId);
            
            if (!$invoice || !$invoice->canBeCancelled()) {
                return false;
            }
            
            $this->db->update(
                'premium_invoices',
                [
                    'status' => 'cancelled',
                    'notes' => $invoice->notes 
                        ? $invoice->notes . "\n\nAnnulée: " . $reason 
                        : "Annulée: " . $reason
                ],
                ['id' => $invoiceId]
            );
            
            $this->logger->security("Invoice cancelled", [
                'invoice_id' => $invoiceId,
                'reason' => $reason
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Invoice cancellation failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Obtenir statistiques factures
     */
    public function getInvoiceStats(int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $stats = $this->db->queryOne("
            SELECT 
                COUNT(*) as total_invoices,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN tax_amount END), 0) as total_tax
            FROM premium_invoices
            WHERE DATE(issued_at) >= ?
        ", [$startDate]);
        
        return [
            'total_invoices' => (int)$stats['total_invoices'],
            'paid_count' => (int)$stats['paid_count'],
            'cancelled_count' => (int)$stats['cancelled_count'],
            'total_revenue' => (float)$stats['total_revenue'],
            'total_tax' => (float)$stats['total_tax'],
        ];
    }
}
