<?php
declare(strict_types=1);

class CustomerWebsiteController
{
    public function __construct(
        private CustomerRepository $customers,
        private CustomerWebsiteRepository $websites,
        private AuditLogger $audit
    ) {}

    private function customer(int $id): array
    {
        AdminAuth::requireAuth();
        if (($_SESSION['admin_role'] ?? '') !== 'superadmin') {
            http_response_code(403);
            exit('Zugriff verweigert.');
        }
        $customer = $this->customers->findById($id);
        if ($customer === null) {
            http_response_code(404);
            exit('Kunde nicht gefunden.');
        }
        return $customer;
    }

    public function index(int $customerId): void
    {
        $customer = $this->customer($customerId);
        $mode = $this->websites->findMode($customerId);
        $success = $_SESSION['flash_success'] ?? null;
        $errors = $_SESSION['flash_errors'] ?? [];
        unset($_SESSION['flash_success'], $_SESSION['flash_errors']);
        require __DIR__ . '/../views/customer_website/index.php';
    }

    public function save(int $customerId): void
    {
        $this->customer($customerId);
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            exit('Ungueltige Anfrage.');
        }
        try {
            $mode = $_POST['website_mode'] ?? null;
            if (!is_string($mode)) throw new InvalidArgumentException('Ungueltiger Website-Modus.');
            $this->websites->save($customerId, $mode);
            $this->audit->log('customer.website_mode_updated', 'customer', $customerId, $mode);
            $_SESSION['flash_success'] = 'Website-Darstellung gespeichert. Sie wird beim naechsten Kunden-CMS-Rollout uebernommen.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_errors'] = [$e->getMessage()];
        }
        header('Location: /admin/customers/' . $customerId . '/website');
        exit;
    }
}
