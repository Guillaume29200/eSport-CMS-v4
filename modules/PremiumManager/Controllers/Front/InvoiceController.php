<?php
declare(strict_types=1);

namespace PremiumManager\Controllers\Front;

use Framework\Services\Database;
use Framework\Services\Logger;
use PremiumManager\Services\InvoiceService;

/**
 * Controller InvoiceController (Front)
 * 
 * Affichage et téléchargement des factures
 * 
 * @author Guillaume
 */
class InvoiceController
{
    private Database $db;
    private Logger $logger;
    private InvoiceService $invoiceService;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logger = new Logger($db, []);
        $this->invoiceService = new InvoiceService($db, $this->logger);
    }
    
    /**
     * Télécharger une facture
     * 
     * GET /member/premium/invoices/{id}
     */
    public function download(int $id): void
    {
        $this->requireAuth();
        
        // Récupérer facture
        $invoice = $this->invoiceService->getInvoiceById($id);
        
        if (!$invoice) {
            http_response_code(404);
            die('Invoice not found');
        }
        
        // Vérifier que la facture appartient à l'utilisateur
        if ($invoice->userId !== $_SESSION['user_id']) {
            $this->logger->security("Unauthorized invoice access attempt", [
                'invoice_id' => $id,
                'user_id' => $_SESSION['user_id'],
                'invoice_user_id' => $invoice->userId
            ]);
            
            http_response_code(403);
            die('Access denied');
        }
        
        // Si pas de PDF, le générer
        if (!$invoice->hasPdf()) {
            $this->invoiceService->generatePdf($invoice);
            $invoice = $this->invoiceService->getInvoiceById($id); // Recharger
        }
        
        // Télécharger
        if ($invoice->hasPdf()) {
            $filename = "facture_{$invoice->invoiceNumber}.pdf";
            
            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Content-Length: ' . filesize($invoice->pdfPath));
            
            readfile($invoice->pdfPath);
            
            $this->logger->info("Invoice downloaded", [
                'invoice_id' => $id,
                'user_id' => $_SESSION['user_id']
            ]);
            
            exit;
        }
        
        http_response_code(500);
        die('PDF generation failed');
    }
    
    /**
     * Afficher facture en ligne
     * 
     * GET /member/premium/invoices/{id}/view
     */
    public function view(int $id): void
    {
        $this->requireAuth();
        
        // Récupérer facture
        $invoice = $this->invoiceService->getInvoiceById($id);
        
        if (!$invoice) {
            http_response_code(404);
            die('Invoice not found');
        }
        
        // Vérifier permissions
        if ($invoice->userId !== $_SESSION['user_id']) {
            http_response_code(403);
            die('Access denied');
        }
        
        include __DIR__ . '/../../Views/member/invoices/view.php';
    }
    
    /**
     * Vérifier authentification
     */
    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
}
