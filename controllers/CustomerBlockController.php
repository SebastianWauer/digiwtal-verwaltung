<?php
declare(strict_types=1);

class CustomerBlockController
{
    public function __construct(
        private CustomerRepository $customers,
        private CustomerBlockRepository $blocks,
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
        $available = CustomerBlockRepository::available(trim((string)($customer['frontend_key'] ?? '')));
        $selected = $this->blocks->findByCustomer($customerId) ?? CustomerBlockRepository::defaults();
        $success = $_SESSION['flash_success'] ?? null;
        $errors = $_SESSION['flash_errors'] ?? [];
        unset($_SESSION['flash_success'], $_SESSION['flash_errors']);
        require __DIR__ . '/../views/customer_blocks/index.php';
    }

    public function save(int $customerId): void
    {
        $customer = $this->customer($customerId);
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            exit('Ungueltige Anfrage.');
        }
        $selected = $_POST['blocks'] ?? [];
        try {
            if (!is_array($selected)) throw new InvalidArgumentException('Ungueltige Blockauswahl.');
            $this->blocks->save($customerId, trim((string)($customer['frontend_key'] ?? '')), $selected);
            $this->audit->log('customer.pagebuilder_blocks_updated', 'customer', $customerId, json_encode(array_values($selected), JSON_THROW_ON_ERROR));
            $_SESSION['flash_success'] = 'Blockfreigaben gespeichert. Sie werden beim naechsten CMS-Rollout uebernommen.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_errors'] = [$e->getMessage()];
        }
        header('Location: /admin/customers/' . $customerId . '/blocks');
        exit;
    }
}
